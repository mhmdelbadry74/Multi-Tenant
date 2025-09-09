<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\System\Tenant;
use App\Models\Tenant\User;
use App\Services\JwtService;
use App\Services\TenantManager;
use Illuminate\Support\Facades\Hash;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    protected JwtService $jwtService;
    protected TenantManager $tenantManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = app(JwtService::class);
        $this->tenantManager = app(TenantManager::class);
    }

    /**
     * Test tenant creation via admin API
     */
    public function test_can_create_tenant(): void
    {
        $tenantData = [
            'name' => 'Test Corporation',
            'slug' => 'test-corp',
            'db_name' => 'tenant_test',
            'db_user' => 'test_user',
            'db_pass' => 'test_password',
        ];

        $response = $this->postJson('/api/admin/tenants', $tenantData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => ['id', 'name', 'slug', 'status'],
                    'message'
                ]);

        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Corporation',
            'slug' => 'test-corp',
            'status' => 'active'
        ]);
    }

    /**
     * Test tenant login and JWT token generation
     */
    public function test_can_login_tenant_user(): void
    {
        // Create a tenant
        $tenant = Tenant::create([
            'name' => 'Test Corporation',
            'slug' => 'test-corp',
            'db_name' => 'tenant_test',
            'db_user' => 'test_user',
            'db_pass' => 'test_password',
        ]);

        // Create tenant database and user
        $this->tenantManager->switch($tenant->id);
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $loginData = [
            'tenant_id' => $tenant->id,
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $response = $this->postJson('/api/tenant/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user' => ['id', 'name', 'email', 'role']
                ]);
    }

    /**
     * Test JWT token validation
     */
    public function test_jwt_token_validation(): void
    {
        // Create a tenant
        $tenant = Tenant::create([
            'name' => 'Test Corporation',
            'slug' => 'test-corp',
            'db_name' => 'tenant_test',
            'db_user' => 'test_user',
            'db_pass' => 'test_password',
        ]);

        // Create tenant database and user
        $this->tenantManager->switch($tenant->id);
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Generate JWT token
        $token = $this->jwtService->issue([
            'sub' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => $user->role,
            'exp' => now()->addHours(4)->timestamp,
        ]);

        // Test protected endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/tenant/me');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => ['id', 'name', 'email', 'role'],
                    'tenant' => ['id', 'name', 'slug']
                ]);
    }

    /**
     * Test tenant suspension blocks access
     */
    public function test_suspended_tenant_blocks_access(): void
    {
        // Create a suspended tenant
        $tenant = Tenant::create([
            'name' => 'Suspended Corporation',
            'slug' => 'suspended-corp',
            'db_name' => 'tenant_suspended',
            'db_user' => 'suspended_user',
            'db_pass' => 'suspended_password',
            'status' => 'suspended',
        ]);

        // Generate JWT token for suspended tenant
        $token = $this->jwtService->issue([
            'sub' => 1,
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'exp' => now()->addHours(4)->timestamp,
        ]);

        // Test that suspended tenant is blocked
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/tenant/me');

        $response->assertStatus(403)
                ->assertJson(['message' => 'Tenant is suspended']);
    }

    /**
     * Test invalid JWT token
     */
    public function test_invalid_jwt_token(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token'
        ])->getJson('/api/tenant/me');

        $response->assertStatus(401)
                ->assertJson(['message' => 'Invalid token']);
    }

    /**
     * Test missing JWT token
     */
    public function test_missing_jwt_token(): void
    {
        $response = $this->getJson('/api/tenant/me');

        $response->assertStatus(401)
                ->assertJson(['message' => 'Unauthenticated']);
    }

    /**
     * Test tenant isolation - user from tenant A cannot access tenant B data
     */
    public function test_tenant_isolation(): void
    {
        // Create two tenants
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'db_name' => 'tenant_a',
            'db_user' => 'tenant_a_user',
            'db_pass' => 'tenant_a_pass',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'db_name' => 'tenant_b',
            'db_user' => 'tenant_b_user',
            'db_pass' => 'tenant_b_pass',
        ]);

        // Create users in both tenants
        $this->tenantManager->switch($tenantA->id);
        $userA = User::create([
            'name' => 'User A',
            'email' => 'user-a@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->tenantManager->switch($tenantB->id);
        $userB = User::create([
            'name' => 'User B',
            'email' => 'user-b@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Generate token for tenant A user
        $tokenA = $this->jwtService->issue([
            'sub' => $userA->id,
            'tenant_id' => $tenantA->id,
            'role' => $userA->role,
            'exp' => now()->addHours(4)->timestamp,
        ]);

        // Try to access tenant B data with tenant A token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokenA
        ])->getJson('/api/tenant/me');

        // Should get tenant A data, not tenant B
        $response->assertStatus(200)
                ->assertJson([
                    'user' => ['id' => $userA->id, 'name' => 'User A'],
                    'tenant' => ['id' => $tenantA->id, 'name' => 'Tenant A']
                ]);
    }
}
