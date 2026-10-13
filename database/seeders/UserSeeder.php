<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Explicit opt-in ONLY; never included in DatabaseSeeder.
 * Create a real admin at /setup/admin before creating demonstration staff.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::table('initial_admin_setup')->where('id', 1)->whereNotNull('completed_at')->exists()
            || ! User::role('admin')->exists()) {
            throw new RuntimeException('Complete /setup/admin first. This seeder never creates administrator credentials.');
        }

        $staff = [
            ['name' => 'Sample Cashier', 'email' => 'cashier@demo.invalid', 'role' => 'cashier'],
            ['name' => 'Sample Kitchen', 'email' => 'kitchen@demo.invalid', 'role' => 'kitchen'],
            ['name' => 'Sample Auditor', 'email' => 'auditor@demo.invalid', 'role' => 'auditor'],
        ];

        foreach ($staff as $row) {
            if (User::query()->where('email', $row['email'])->exists()) {
                $this->command?->warn('Demo staff already exists: '.$row['email'].'. No credentials were changed.');
                continue;
            }

            $password = Str::password(24);
            $user = DB::transaction(function () use ($row, $password): User {
                $user = User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => $password, // Hashed automatically by User's password cast.
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
                $user->assignRole($row['role']);
                return $user;
            });

            $this->command?->warn("Temporary DEMO POS staff login ({$row['role']}): {$user->email} / {$password}");
        }

        $this->command?->warn('Temporary staff credentials were displayed once. Store them securely and replace before live operation.');
    }
}
