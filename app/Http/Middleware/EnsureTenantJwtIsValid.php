<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JwtService;
use App\Services\TenantManager;
use App\Models\System\Tenant;
use App\Exceptions\Custom\JwtException;
use App\Exceptions\Custom\TenantNotFoundException;
use App\Exceptions\Custom\TenantSuspendedException;

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
            throw new JwtException('No authentication token provided', 401, 'JWT_MISSING');
        }

        try {
            // Decode and validate JWT token
            $claims = $this->jwtService->decode($token);
            $this->jwtService->validateClaims($claims);

            $tenantId = $this->jwtService->getTenantId($claims);
            
            // Check if tenant exists and is active
            $tenant = Tenant::find($tenantId);
            
            if (!$tenant) {
                throw new TenantNotFoundException("Tenant with ID {$tenantId} not found");
            }

            if (!$tenant->isActive()) {
                throw new TenantSuspendedException("Tenant '{$tenant->name}' is suspended");
            }

            // Switch to tenant database
            $this->tenantManager->switch($tenantId);

            // Store JWT claims in request for later use
            $request->attributes->set('jwt', $claims);
            $request->attributes->set('tenant', $tenant);

            return $next($request);
        } catch (JwtException | TenantNotFoundException | TenantSuspendedException $e) {
            throw $e; // Re-throw custom exceptions
        } catch (\Exception $e) {
            throw new JwtException('Invalid token: ' . $e->getMessage(), 401, 'JWT_INVALID');
        }
    }
}
