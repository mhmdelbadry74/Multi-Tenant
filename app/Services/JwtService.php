<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use App\Exceptions\Custom\JwtException;

class JwtService
{
    private string $key;

    public function __construct()
    {
        $this->key = config('app.key');
    }

    /**
     * Issue a new JWT token
     */
    public function issue(array $claims): string
    {
        $payload = array_merge([
            'iat' => time(),
            'iss' => config('app.url'),
        ], $claims);

        return JWT::encode($payload, $this->key, 'HS256');
    }

    /**
     * Decode and validate JWT token
     */
    public function decode(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->key, 'HS256'));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new JwtException('Token has expired', 401, 'JWT_EXPIRED');
        } catch (SignatureInvalidException $e) {
            throw new JwtException('Invalid token signature', 401, 'JWT_INVALID_SIGNATURE');
        } catch (\Exception $e) {
            throw new JwtException('Invalid token', 401, 'JWT_INVALID');
        }
    }

    /**
     * Validate token claims
     */
    public function validateClaims(array $claims): bool
    {
        $required = ['sub', 'tenant_id'];
        
        foreach ($required as $claim) {
            if (!isset($claims[$claim]) || empty($claims[$claim])) {
                throw new JwtException("Missing required claim: {$claim}", 401, 'JWT_MISSING_CLAIM');
            }
        }

        // Check expiration
        if (isset($claims['exp']) && $claims['exp'] < time()) {
            throw new JwtException('Token has expired', 401, 'JWT_EXPIRED');
        }

        return true;
    }

    /**
     * Extract user ID from claims
     */
    public function getUserId(array $claims): int
    {
        return (int) $claims['sub'];
    }

    /**
     * Extract tenant ID from claims
     */
    public function getTenantId(array $claims): int
    {
        return (int) $claims['tenant_id'];
    }

    /**
     * Extract role from claims
     */
    public function getRole(array $claims): ?string
    {
        return $claims['role'] ?? null;
    }
}
