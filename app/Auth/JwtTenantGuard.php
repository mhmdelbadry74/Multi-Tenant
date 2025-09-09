<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use App\Services\JwtService;
use App\Services\TenantManager;
use App\Models\Tenant\User;
use Illuminate\Contracts\Auth\Authenticatable;

class JwtTenantGuard implements Guard
{
    protected UserProvider $userProvider;
    protected Request $request;
    protected JwtService $jwtService;
    protected TenantManager $tenantManager;
    protected ?Authenticatable $user = null;

    public function __construct(
        UserProvider $userProvider,
        Request $request,
        JwtService $jwtService,
        TenantManager $tenantManager
    ) {
        $this->userProvider = $userProvider;
        $this->request = $request;
        $this->jwtService = $jwtService;
        $this->tenantManager = $tenantManager;
    }

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->request->bearerToken();
        
        if (!$token) {
            return null;
        }

        try {
            $claims = $this->jwtService->decode($token);
            
            if (!$this->jwtService->validateClaims($claims)) {
                return null;
            }

            $tenantId = $this->jwtService->getTenantId($claims);
            $userId = $this->jwtService->getUserId($claims);

            // Switch to tenant database
            $this->tenantManager->switch($tenantId);

            // Get user from tenant database
            $user = $this->userProvider->retrieveById($userId);
            
            if ($user) {
                $this->user = $user;
            }

            return $this->user;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the ID for the currently authenticated user.
     */
    public function id(): ?int
    {
        $user = $this->user();
        return $user ? $user->getAuthIdentifier() : null;
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool
    {
        if (empty($credentials['email']) || empty($credentials['password'])) {
            return false;
        }

        $user = $this->userProvider->retrieveByCredentials($credentials);
        
        if (!$user) {
            return false;
        }

        return $this->userProvider->validateCredentials($user, $credentials);
    }

    /**
     * Set the current user.
     */
    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }

    /**
     * Determine if the current user is authenticated.
     */
    public function check(): bool
    {
        return !is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Get the user provider used by the guard.
     */
    public function getProvider(): UserProvider
    {
        return $this->userProvider;
    }

    /**
     * Set the user provider used by the guard.
     */
    public function setProvider(UserProvider $provider): void
    {
        $this->userProvider = $provider;
    }
}
