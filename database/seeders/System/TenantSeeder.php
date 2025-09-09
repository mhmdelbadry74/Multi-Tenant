<?php

namespace Database\Seeders\System;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\System\Tenant;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'ACME Corporation',
                'slug' => 'acme',
                'db_name' => 'tenant_acme',
                'db_user' => 'acme_user',
                'db_pass' => 'acme_password',
                'status' => 'active',
            ],
            [
                'name' => 'Globex Corporation',
                'slug' => 'globex',
                'db_name' => 'tenant_globex',
                'db_user' => 'globex_user',
                'db_pass' => 'globex_password',
                'status' => 'active',
            ],
        ];

        foreach ($tenants as $tenantData) {
            Tenant::updateOrCreate(
                ['slug' => $tenantData['slug']],
                $tenantData
            );
        }
    }
}
