<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JwtService;
use App\Services\TenantManager;
use App\Models\System\Tenant;

class EnsureTenantJwtIsValid
{
    protected JwtService $jwtService;
    protected TenantManager $tenantManager;

    public function __construct(JwtService $jwtService, TenantManager $tenantManager)
    {
        $this->jwtService = $jwtService;
        $this->tenantManager = $tenantManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            // Decode and validate JWT token
            $claims = $this->jwtService->decode($token);
            
            if (!$this->jwtService->validateClaims($claims)) {
                return response()->json(['message' => 'Invalid token claims'], 401);
            }

            $tenantId = $this->jwtService->getTenantId($claims);
            
            // Check if tenant exists and is active
            $tenant = Tenant::find($tenantId);
            
            if (!$tenant) {
                return response()->json(['message' => 'Tenant not found'], 404);
            }

            if (!$tenant->isActive()) {
                return response()->json(['message' => 'Tenant is suspended'], 403);
            }

            // Switch to tenant database
            $this->tenantManager->switch($tenantId);

            // Store JWT claims in request for later use
            $request->attributes->set('jwt', $claims);
            $request->attributes->set('tenant', $tenant);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
    }
}
