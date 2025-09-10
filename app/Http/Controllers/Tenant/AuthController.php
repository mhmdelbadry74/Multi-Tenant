<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\LoginRequest;
use App\Http\Resources\Tenant\UserResource;
use App\Services\JwtService;
use App\Services\TenantManager;
use App\Models\Tenant\User;
use App\Exceptions\Custom\TenantNotFoundException;
use App\Exceptions\Custom\TenantSuspendedException;
use App\Exceptions\Custom\DatabaseConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected JwtService $jwtService;
    protected TenantManager $tenantManager;

    public function __construct(JwtService $jwtService, TenantManager $tenantManager)
    {
        $this->jwtService = $jwtService;
        $this->tenantManager = $tenantManager;
    }

    /**
     * Login user and return JWT token
     */
    public function login(LoginRequest $request)
    {
        $tenantId = (int) $request->input('tenant_id');
        
        try {
            // Switch to tenant database
            $this->tenantManager->switch($tenantId);

            // Find user by email
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'error' => 'INVALID_CREDENTIALS',
                    'status_code' => 401,
                    'timestamp' => now()->toISOString(),
                ], 401);
            }

            // Generate JWT token
            $token = $this->jwtService->issue([
                'sub' => $user->id,
                'tenant_id' => $tenantId,
                'role' => $user->role,
                'exp' => now()->addHours(4)->timestamp,
            ]);

            return response()->json([
                'success' => true,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => 4 * 60 * 60, // 4 hours in seconds
                'user' => new UserResource($user),
                'timestamp' => now()->toISOString(),
            ]);
        } catch (TenantNotFoundException | TenantSuspendedException | DatabaseConnectionException $e) {
            throw $e; // Re-throw custom exceptions
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
                'error' => 'AUTHENTICATION_FAILED',
                'status_code' => 401,
                'details' => app()->environment('local') ? $e->getMessage() : null,
                'timestamp' => now()->toISOString(),
            ], 401);
        }
    }

    /**
     * Get current user info
     */
    public function me(Request $request)
    {
        $jwt = $request->attributes->get('jwt');
        $tenant = $request->attributes->get('tenant');

        $user = User::find($jwt['sub']);

        return response()->json([
            'user' => new UserResource($user),
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ]
        ]);
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout(Request $request)
    {
        // For now, we'll just return success
        // In a real implementation, you might want to blacklist the token
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
