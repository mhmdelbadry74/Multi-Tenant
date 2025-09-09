<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\System\Tenant;
use App\Jobs\ProvisionTenant;

class ProvisionTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:provision {tenant_id : The ID of the tenant to provision}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision a tenant database and run migrations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');
        
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");
            return 1;
        }

        $this->info("Provisioning tenant: {$tenant->name} (ID: {$tenantId})");
        
        // Dispatch the job
        ProvisionTenant::dispatch($tenantId);
        
        $this->info("Provisioning job dispatched for tenant: {$tenant->name}");
        $this->line("Run 'php artisan queue:work' to process the job.");
        
        return 0;
    }
}
