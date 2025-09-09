<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\System\TenantSeeder;

class TenantsSeedDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:seed-demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed demo tenants (ACME and Globex)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding demo tenants...');
        
        $seeder = new TenantSeeder();
        $seeder->run();
        
        $this->info('Demo tenants seeded successfully!');
        $this->line('Created tenants: ACME Corporation and Globex Corporation');
    }
}
