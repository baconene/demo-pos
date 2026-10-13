<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RoyaltyRule;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Protects Laravel route introspection and the existing royalty rule API from missing classes. */
class RoyaltyRulesRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_setup_and_royalty_routes_can_be_listed(): void
    {
        $this->artisan('route:list --path=setup/admin')->assertSuccessful();
        $this->artisan('route:list --path=api/v1/royalty-rules')->assertSuccessful();
    }

    public function test_admin_can_manage_royalty_rules_but_guest_cannot(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->getJson('/api/v1/royalty-rules')->assertUnauthorized();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin', 'web'));
        $this->actingAs($admin);
        $category = Category::create(['name' => 'Example', 'slug' => 'example']);
        $response = $this->postJson('/api/v1/royalty-rules', [
            'scope' => 'category', 'category_id' => $category->id,
            'recipient_name' => 'Training Partner', 'royalty_percentage' => 5.5,
            'effective_date' => '2026-07-01', 'is_active' => true,
        ]);
        $response->assertCreated();
        $this->assertSame(1, RoyaltyRule::count());
        $id = RoyaltyRule::firstOrFail()->id;
        $this->putJson('/api/v1/royalty-rules/'.$id, [
            'scope' => 'category', 'category_id' => $category->id,
            'recipient_name' => 'Training Partner', 'royalty_percentage' => 6.0,
            'effective_date' => '2026-07-01', 'is_active' => true,
        ])->assertOk();
        $this->getJson('/api/v1/royalty-rules')->assertOk()->assertJsonCount(1);
        $this->deleteJson('/api/v1/royalty-rules/'.$id)->assertOk();
        $this->assertSame(0, RoyaltyRule::count());
    }
}
