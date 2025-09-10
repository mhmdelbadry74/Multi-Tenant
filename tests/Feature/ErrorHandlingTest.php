<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\System\Tenant;
use App\Exceptions\Custom\TenantNotFoundException;
use App\Exceptions\Custom\TenantSuspendedException;
use App\Exceptions\Custom\JwtException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test JWT authentication error handling
     */
    public function test_jwt_authentication_error_handling()
    {
        $response = $this->getJson('/api/tenant/me', [
            'Authorization' => 'Bearer invalid-token'
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'error' => 'JWT_INVALID'
                 ]);
    }

    /**
     * Test missing JWT token error handling
     */
    public function test_missing_jwt_token_error_handling()
    {
        $response = $this->getJson('/api/tenant/me');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'error' => 'JWT_MISSING'
                 ]);
    }

    /**
     * Test tenant not found error handling
     */
    public function test_tenant_not_found_error_handling()
    {
        $response = $this->postJson('/api/tenant/login', [
            'tenant_id' => 999,
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'error' => 'TENANT_NOT_FOUND'
                 ]);
    }

    /**
     * Test tenant suspended error handling
     */
    public function test_tenant_suspended_error_handling()
    {
        // Create a suspended tenant
        $tenant = Tenant::factory()->create([
            'status' => 'suspended'
        ]);

        $response = $this->postJson('/api/tenant/login', [
            'tenant_id' => $tenant->id,
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'error' => 'TENANT_SUSPENDED'
                 ]);
    }

    /**
     * Test validation error handling
     */
    public function test_validation_error_handling()
    {
        $response = $this->postJson('/api/tenant/login', [
            'tenant_id' => 1,
            // Missing email and password
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'error' => 'VALIDATION_ERROR'
                 ])
                 ->assertJsonStructure([
                     'errors' => [
                         'email',
                         'password'
                     ]
                 ]);
    }

    /**
     * Test invalid credentials error handling
     */
    public function test_invalid_credentials_error_handling()
    {
        // Create an active tenant
        $tenant = Tenant::factory()->create([
            'status' => 'active'
        ]);

        $response = $this->postJson('/api/tenant/login', [
            'tenant_id' => $tenant->id,
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'error' => 'INVALID_CREDENTIALS'
                 ]);
    }

    /**
     * Test not found route error handling
     */
    public function test_not_found_route_error_handling()
    {
        $response = $this->getJson('/api/nonexistent-route');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'error' => 'RESOURCE_NOT_FOUND'
                 ]);
    }

    /**
     * Test method not allowed error handling
     */
    public function test_method_not_allowed_error_handling()
    {
        $response = $this->putJson('/api/tenant/login', [
            'tenant_id' => 1,
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertStatus(405)
                 ->assertJson([
                     'success' => false,
                     'error' => 'METHOD_NOT_ALLOWED'
                 ]);
    }

    /**
     * Test error response structure
     */
    public function test_error_response_structure()
    {
        $response = $this->getJson('/api/nonexistent-route');

        $response->assertJsonStructure([
            'success',
            'message',
            'error',
            'status_code',
            'timestamp',
            'meta' => [
                'version',
                'environment'
            ]
        ]);
    }
}
