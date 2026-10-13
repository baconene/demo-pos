<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * One-time, possession-of-secret-gated setup for the very first DEMO POS admin.
 * Public Fortify registration remains disabled. Do not use a secret in the URL.
 */
class InitialAdminSetupController extends Controller
{
    public function show(): Response
    {
        $this->ensureAvailable();

        return Inertia::render('auth/InitialAdminSetup');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAvailable();

        // Check the Forge-only bootstrap secret *before* validating account details.
        // No secret is ever returned in the Inertia page props or a redirect URL.
        $expected = (string) config('services.initial_admin_setup_token', '');
        $provided = $request->input('setup_token');

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            throw ValidationException::withMessages([
                'setup_token' => 'Invalid initial setup code.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ]);

        // The singleton record is locked inside the transaction. Concurrent POSTs
        // cannot both pass the check, and completion is never reset on deletion.
        $user = DB::transaction(function () use ($data): User {
            $setup = DB::table('initial_admin_setup')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            abort_unless($setup && $setup->completed_at === null && ! User::query()->exists(), 404);

            // RolePermissionSeeder must run during Forge provisioning. A role
            // with no permissions must not be silently created here.
            $role = Role::query()
                ->where('name', 'admin')
                ->where('guard_name', 'web')
                ->first();

            abort_unless($role, 503, 'Run the base role seeder before administrator setup.');

            $user = User::create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'password' => Hash::make($data['password']),
            ]);

            // The Forge setup code is the initial identity proof. Mark the
            // email verified so the newly created admin can reach /dashboard.
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole($role);

            DB::table('initial_admin_setup')->where('id', 1)->update([
                'completed_at' => now(),
            ]);

            return $user;
        }, attempts: 3);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function ensureAvailable(): void
    {
        $token = config('services.initial_admin_setup_token');
        abort_unless(is_string($token) && strlen($token) >= 32, 404);

        // This page should vanish permanently after the first successful run,
        // including when the first admin is deleted in the future.
        $setup = DB::table('initial_admin_setup')->where('id', 1)->first();
        abort_unless(
            $setup && $setup->completed_at === null && ! User::query()->exists(),
            404,
        );
    }
}
