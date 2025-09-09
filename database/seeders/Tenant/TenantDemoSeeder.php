<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant\User;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Deal;
use App\Models\Tenant\Activity;
use Illuminate\Support\Facades\Hash;

class TenantDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@tenant.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create manager user
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@tenant.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);

        // Create regular user
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@tenant.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        // Create sample contacts
        $contact1 = Contact::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'company' => 'Example Corp',
            'notes' => 'Potential customer',
            'created_by' => $admin->id,
        ]);

        $contact2 = Contact::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+0987654321',
            'company' => 'Smith Industries',
            'notes' => 'Existing customer',
            'created_by' => $manager->id,
        ]);

        // Create sample deals
        $deal1 = Deal::create([
            'title' => 'Website Development',
            'amount' => 5000.00,
            'status' => 'open',
            'contact_id' => $contact1->id,
            'assigned_to' => $manager->id,
            'description' => 'Custom website development project',
        ]);

        $deal2 = Deal::create([
            'title' => 'Mobile App',
            'amount' => 10000.00,
            'status' => 'won',
            'closed_at' => now()->subDays(5),
            'contact_id' => $contact2->id,
            'assigned_to' => $admin->id,
            'description' => 'iOS and Android mobile application',
        ]);

        // Create sample activities
        Activity::create([
            'type' => 'call',
            'subject' => 'Initial consultation call',
            'description' => 'Discussed project requirements',
            'happened_at' => now()->subDays(10),
            'contact_id' => $contact1->id,
            'deal_id' => $deal1->id,
            'user_id' => $manager->id,
        ]);

        Activity::create([
            'type' => 'meeting',
            'subject' => 'Project kickoff meeting',
            'description' => 'Project kickoff and timeline discussion',
            'happened_at' => now()->subDays(7),
            'contact_id' => $contact2->id,
            'deal_id' => $deal2->id,
            'user_id' => $admin->id,
        ]);

        Activity::create([
            'type' => 'note',
            'subject' => 'Follow-up notes',
            'description' => 'Customer showed interest in additional features',
            'happened_at' => now()->subDays(3),
            'contact_id' => $contact1->id,
            'user_id' => $user->id,
        ]);
    }
}
