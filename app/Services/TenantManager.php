<?php

namespace App\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\DB;
use App\Models\System\Tenant;
use App\Exceptions\Custom\TenantNotFoundException;
use App\Exceptions\Custom\TenantSuspendedException;
use App\Exceptions\Custom\DatabaseConnectionException;

class TenantManager
{
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Switch to tenant database connection
     */
    public function switch(int $tenantId): Tenant
    {
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            throw new TenantNotFoundException("Tenant with ID {$tenantId} not found");
        }
        
        if ($tenant->status !== 'active') {
            throw new TenantSuspendedException("Tenant '{$tenant->name}' is suspended");
        }

        try {
            // Update tenant connection configuration for MySQL
            $this->config->set([
                'database.connections.tenant.driver' => 'mysql',
                'database.connections.tenant.database' => $tenant->db_name,
                'database.connections.tenant.username' => $tenant->db_user,
                'database.connections.tenant.password' => $tenant->db_pass,
            ]);

            // Purge and reconnect to ensure new configuration is used
            DB::purge('tenant');
            DB::reconnect('tenant');

            // Test the connection
            DB::connection('tenant')->getPdo();

            return $tenant;
        } catch (\Exception $e) {
            throw new DatabaseConnectionException(
                "Failed to connect to tenant database: " . $e->getMessage()
            );
        }
    }

    /**
     * Get current tenant from JWT claims
     */
    public function getCurrentTenant(): ?Tenant
    {
        // This will be used later when we have JWT middleware
        return null;
    }

    /**
     * Check if tenant is active
     */
    public function isTenantActive(int $tenantId): bool
    {
        try {
            $tenant = Tenant::find($tenantId);
            return $tenant && $tenant->status === 'active';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get tenant by ID with proper error handling
     */
    public function getTenant(int $tenantId): Tenant
    {
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            throw new TenantNotFoundException("Tenant with ID {$tenantId} not found");
        }
        
        return $tenant;
    }
}
