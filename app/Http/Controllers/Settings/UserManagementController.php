<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    private const ROLES = ['admin', 'cashier', 'kitchen', 'auditor'];

    public function index(): Response
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $users = User::with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'roles'      => $u->getRoleNames()->values(),
                'created_at' => $u->created_at?->toDateString(),
            ]);

        return Inertia::render('settings/Users', [
            'users'          => $users,
            'availableRoles' => self::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'roles'    => 'required|array|min:1',
            'roles.*'  => 'string|in:' . implode(',', self::ROLES),
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
        $user->syncRoles($data['roles']);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $data = $request->validate([
            'roles'   => 'required|array',
            'roles.*' => 'string|in:' . implode(',', self::ROLES),
        ]);

        $user->syncRoles($data['roles']);

        return back()->with('success', 'User roles updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');

        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
