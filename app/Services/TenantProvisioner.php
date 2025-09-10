<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\System\Tenant;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\Custom\ProvisioningException;
use App\Exceptions\Custom\DatabaseConnectionException;

class TenantProvisioner
{
    protected TenantManager $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Provision a new tenant database
     */
    public function provision(Tenant $tenant): bool
    {
        try {
            // Create database
            $this->createDatabase($tenant);

            // Create database user
            $this->createDatabaseUser($tenant);

            // Switch to tenant connection
            $this->tenantManager->switch($tenant->id);

            // Run tenant migrations
            $this->runTenantMigrations();

            // Create default admin user
            $this->createDefaultAdminUser($tenant);

            // Run tenant seeders (optional)
            $this->runTenantSeeders();

            return true;
        } catch (ProvisioningException $e) {
            throw $e; // Re-throw provisioning exceptions
        } catch (DatabaseConnectionException $e) {
            throw new ProvisioningException("Database connection failed during provisioning: " . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error("Failed to provision tenant {$tenant->id}: " . $e->getMessage());
            throw new ProvisioningException("Failed to provision tenant: " . $e->getMessage());
        }
    }

    /**
     * Create database for tenant
     */
    protected function createDatabase(Tenant $tenant): void
    {
        try {
            // Use system connection to create database
            $connection = DB::connection('mysql');
            
            // Create MySQL database
            $connection->statement("CREATE DATABASE IF NOT EXISTS `{$tenant->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Exception $e) {
            throw new ProvisioningException("Failed to create database for tenant: " . $e->getMessage());
        }
    }

    /**
     * Create database user (for MySQL)
     */
    protected function createDatabaseUser(Tenant $tenant): void
    {
        try {
            $connection = DB::connection('mysql');
            
            // Create user if it doesn't exist
            $connection->statement("CREATE USER IF NOT EXISTS '{$tenant->db_user}'@'%' IDENTIFIED BY '{$tenant->db_pass}'");
            
            // Grant privileges on the tenant database
            $connection->statement("GRANT ALL PRIVILEGES ON `{$tenant->db_name}`.* TO '{$tenant->db_user}'@'%'");
            
            // Flush privileges
            $connection->statement("FLUSH PRIVILEGES");
        } catch (\Exception $e) {
            throw new ProvisioningException("Failed to create database user for tenant: " . $e->getMessage());
        }
    }

    /**
     * Run tenant migrations
     */
    protected function runTenantMigrations(): void
    {
        // Run migrations on tenant connection
        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--database' => 'tenant',
            '--force' => true,
        ]);
    }

    /**
     * Create default admin user
     */
    protected function createDefaultAdminUser(Tenant $tenant): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@' . $tenant->slug . '.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }

    /**
     * Run tenant seeders
     */
    protected function runTenantSeeders(): void
    {
        // Run demo seeder on tenant connection
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\Tenant\\TenantDemoSeeder',
            '--database' => 'tenant',
            '--force' => true,
        ]);
    }

    /**
     * Check if tenant is already provisioned
     */
    public function isProvisioned(Tenant $tenant): bool
    {
        try {
            $connection = DB::connection('mysql');
            $result = $connection->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$tenant->db_name]);
            return count($result) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Deprovision tenant (remove database)
     */
    public function deprovision(Tenant $tenant): bool
    {
        try {
            $connection = DB::connection('mysql');
            
            // Drop the database
            $connection->statement("DROP DATABASE IF EXISTS `{$tenant->db_name}`");
            
            // Drop the user
            $connection->statement("DROP USER IF EXISTS '{$tenant->db_user}'@'%'");
            
            // Flush privileges
            $connection->statement("FLUSH PRIVILEGES");

            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to deprovision tenant {$tenant->id}: " . $e->getMessage());
            return false;
        }
    }
}
