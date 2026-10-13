<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LogoController extends Controller
{
    private const PATH = 'logo/current.png';

    public function edit(): Response
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        return Inertia::render('settings/Logo', [
            'currentLogoUrl' => Storage::disk('public')->exists(self::PATH)
                ? Storage::disk('public')->url(self::PATH)
                : null,
            'brandName' => Brand::name(),
        ]);
    }

    public function updateName(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $data = $request->validate([
            'brand_name' => 'nullable|string|max:50',
        ]);

        Brand::setName($data['brand_name'] ?? null);

        return back()->with('success', 'Brand name updated.');
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $request->validate([
            'logo' => 'required|image|max:2048|mimes:png,jpg,jpeg,webp',
        ]);

        Storage::disk('public')->delete(self::PATH);

        $request->file('logo')->storeAs('logo', 'current.png', 'public');

        return back()->with('success', 'Logo updated successfully.');
    }

    public function destroy(): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        Storage::disk('public')->delete(self::PATH);

        return back()->with('success', 'Logo reset to default.');
    }
}
