<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Http\Resources\Admin\TenantResource;
use App\Models\System\Tenant;
use App\Jobs\ProvisionTenant;
use App\Exceptions\Custom\TenantException;
use App\Exceptions\Custom\DatabaseConnectionException;
use App\Services\DatabaseConnectionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    protected DatabaseConnectionService $dbService;

    public function __construct(DatabaseConnectionService $dbService)
    {
        $this->dbService = $dbService;
    }
    /**
     * Get all tenants
     */
    public function index()
    {
        try {
            $tenants = Tenant::all();

            return response()->json([
                'data' => TenantResource::collection($tenants),
                'message' => 'Tenants retrieved successfully'
            ]);
        } catch (QueryException $e) {
            Log::error('Failed to retrieve tenants: ' . $e->getMessage());
            throw new DatabaseConnectionException('Unable to retrieve tenants from database');
        } catch (\Exception $e) {
            Log::error('Unexpected error retrieving tenants: ' . $e->getMessage());
            throw new TenantException('Failed to retrieve tenants');
        }
    }

    /**
     * Create a new tenant
     */
    public function store(CreateTenantRequest $request)
    {
        try {
            // Test database connection before creating tenant
            $this->dbService->testConnection($request->validated());

            $tenant = Tenant::create($request->validated());

            // Dispatch provisioning job
            ProvisionTenant::dispatch($tenant->id);

            return response()->json([
                'data' => new TenantResource($tenant),
                'message' => 'Tenant created successfully and provisioning started'
            ], 201);
        } catch (QueryException $e) {
            Log::error('Failed to create tenant: ' . $e->getMessage());

            // Handle specific database errors
            if ($e->getCode() == 1062) {
                throw new TenantException('A tenant with this slug already exists', 422, 'DUPLICATE_SLUG');
            }

            throw new DatabaseConnectionException('Unable to create tenant in database');
        } catch (\Exception $e) {
            Log::error('Unexpected error creating tenant: ' . $e->getMessage());
            throw new TenantException('Failed to create tenant');
        }
    }

    /**
     * Get a specific tenant
     */
    public function show(Tenant $tenant)
    {
        return response()->json([
            'data' => new TenantResource($tenant),
            'message' => 'Tenant retrieved successfully'
        ]);
    }

    /**
     * Update a tenant
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $tenant->update($request->validated());

        return response()->json([
            'data' => new TenantResource($tenant),
            'message' => 'Tenant updated successfully'
        ]);
    }

    /**
     * Suspend a tenant
     */
    public function suspend(Tenant $tenant)
    {
        $tenant->update(['status' => 'suspended']);

        return response()->json([
            'data' => new TenantResource($tenant),
            'message' => 'Tenant suspended successfully'
        ]);
    }

    /**
     * Activate a tenant
     */
    public function activate(Tenant $tenant)
    {
        $tenant->update(['status' => 'active']);

        return response()->json([
            'data' => new TenantResource($tenant),
            'message' => 'Tenant activated successfully'
        ]);
    }

    /**
     * Delete a tenant
     */
    public function destroy(Tenant $tenant)
    {
        try {
            $tenant->delete();

            return response()->json([
                'message' => 'Tenant deleted successfully'
            ]);
        } catch (QueryException $e) {
            Log::error('Failed to delete tenant: ' . $e->getMessage());
            throw new DatabaseConnectionException('Unable to delete tenant from database');
        } catch (\Exception $e) {
            Log::error('Unexpected error deleting tenant: ' . $e->getMessage());
            throw new TenantException('Failed to delete tenant');
        }
    }

    /**
     * Get database connection status
     */
    public function databaseStatus()
    {
        try {
            $status = $this->dbService->getConnectionStatus();

            return response()->json([
                'data' => $status,
                'message' => 'Database status retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get database status: ' . $e->getMessage());
            throw new DatabaseConnectionException('Unable to retrieve database status');
        }
    }
}
