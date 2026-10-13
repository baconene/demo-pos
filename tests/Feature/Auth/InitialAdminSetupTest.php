<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableJson as Assert;
use Tests\TestCase;

class InitialAdminSetupTest extends TestCase
{
    use RefreshDatabase;

    private const SETUP_CODE = 'example-setup-token-not-for-production-0123456789abcdef';
    private const SECURE_PASSWORD = 'StrongPassphrase!2026';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.initial_admin_setup_token', self::SETUP_CODE);
        $this->seed(RolePermissionSeeder::class);
    }

    private function details(array $overrides = []): array
    {
        return array_merge([
            'name' => 'DEMO POS Administrator',
            'email' => 'owner@example.com',
            'password' => self::SECURE_PASSWORD,
            'password_confirmation' => self::SECURE_PASSWORD,
            'setup_token' => self::SETUP_CODE,
        ], $overrides);
    }

    public function test_setup_page_is_only_available_with_forge_secret_configured(): void
    {
        $this->get('/setup/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/InitialAdminSetup'));

        config()->set('services.initial_admin_setup_token', '');
        $this->get('/setup/admin')->assertNotFound();
        $this->post('/setup/admin', $this->details())->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_correct_secret_creates_one_verified_admin_and_logs_them_in(): void
    {
        $this->post('/setup/admin', $this->details())->assertRedirect(route('dashboard'));

        $admin = User::query()->sole();
        $this->assertSame('owner@example.com', $admin->email);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->can('manage users'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull(DB::table('initial_admin_setup')->value('completed_at'));
    }

    public function test_incorrect_secret_is_rejected_without_creating_an_account(): void
    {
        $this->post('/setup/admin', $this->details(['setup_token' => 'incorrect-token']))
            ->assertSessionHasErrors('setup_token');

        $this->assertDatabaseCount('users', 0);
        $this->assertNull(DB::table('initial_admin_setup')->value('completed_at'));
        // The bearer code must never enter flashed form state on validation errors.
        $this->assertArrayNotHasKey('setup_token', session()->get('_old_input', []));
    }

    public function test_setup_post_is_rate_limited_to_five_attempts_per_minute(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/setup/admin', $this->details(['setup_token' => 'incorrect-token']))
                ->assertSessionHasErrors('setup_token');
        }

        $this->post('/setup/admin', $this->details())->assertTooManyRequests();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_weak_and_unconfirmed_passwords_are_rejected(): void
    {
        $this->post('/setup/admin', $this->details([
            'password' => 'weakpassword',
            'password_confirmation' => 'weakpassword',
        ]))->assertSessionHasErrors('password');

        $this->post('/setup/admin', $this->details([
            'password_confirmation' => 'DifferentPassphrase!2026',
        ]))->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_setup_cannot_create_a_second_admin(): void
    {
        $this->post('/setup/admin', $this->details())->assertRedirect(route('dashboard'));
        $this->post('/logout');

        $this->get('/setup/admin')->assertNotFound();
        $this->post('/setup/admin', $this->details([
            'email' => 'another@example.com',
        ]))->assertNotFound();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_deleting_the_only_admin_never_reopens_setup(): void
    {
        $this->post('/setup/admin', $this->details())->assertRedirect(route('dashboard'));
        User::query()->delete();

        $this->get('/setup/admin')->assertNotFound();
        $this->post('/setup/admin', $this->details())->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_preexisting_user_blocks_setup(): void
    {
        User::factory()->create();

        $this->get('/setup/admin')->assertNotFound();
        $this->post('/setup/admin', $this->details())->assertNotFound();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_public_registration_remains_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', $this->details())->assertNotFound();
    }
}
