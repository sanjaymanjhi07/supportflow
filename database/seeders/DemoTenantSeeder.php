<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        // RoleSeeder must run after tenants exist, so seed roles here too
        // for this specific tenant.
        (new RoleSeeder())->run();

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sanjay Manjhi',
            'email' => 'owner@acme.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('owner');

        $agent = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Alex Agent',
            'email' => 'agent@acme.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $agent->assignRole('agent');

        $customer = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Casey Customer',
            'email' => 'customer@acme.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $customer->assignRole('customer');

        $category = Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Billing',
            'description' => 'Invoices, refunds, and payment issues.',
        ]);

        Ticket::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'requester_id' => $customer->id,
            'assigned_to' => $agent->id,
            'subject' => 'Unable to update payment method',
            'description' => 'I keep getting an error when trying to update my card on file.',
            'priority' => 'high',
        ]);

        $this->command?->info('Demo tenant "acme" seeded. Login: owner@acme.test / password');
    }
}
