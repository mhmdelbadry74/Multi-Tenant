<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\System\Tenant;
use App\Services\TenantProvisioner;
use Illuminate\Support\Facades\Log;

class ProvisionTenant implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     */
    public function handle(TenantProvisioner $provisioner): void
    {
        try {
            $tenant = Tenant::findOrFail($this->tenantId);
            
            Log::info("Starting provisioning for tenant: {$tenant->name} (ID: {$this->tenantId})");

            $success = $provisioner->provision($tenant);

            if ($success) {
                Log::info("Successfully provisioned tenant: {$tenant->name} (ID: {$this->tenantId})");
            } else {
                Log::error("Failed to provision tenant: {$tenant->name} (ID: {$this->tenantId})");
                $this->fail(new \Exception("Failed to provision tenant {$this->tenantId}"));
            }
        } catch (\Exception $e) {
            Log::error("Error provisioning tenant {$this->tenantId}: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProvisionTenant job failed for tenant {$this->tenantId}: " . $exception->getMessage());
    }
}
