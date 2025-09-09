<?php

namespace App\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\DB;
use App\Models\System\Tenant;

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
        $tenant = Tenant::findOrFail($tenantId);
        
        if ($tenant->status !== 'active') {
            throw new \Exception('Tenant is suspended');
        }

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

        return $tenant;
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
            $tenant = Tenant::findOrFail($tenantId);
            return $tenant->status === 'active';
        } catch (\Exception $e) {
            return false;
        }
    }
}
