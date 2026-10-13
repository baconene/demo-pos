<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PrintServiceSetting;
use Inertia\Inertia;
use Inertia\Response;

class PrintServiceSettingsController extends Controller
{
    public function edit(): Response
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        return Inertia::render('settings/PrintSettings', [
            'settings'            => PrintServiceSetting::getSetting(),
            'beams_instance_id'   => config('broadcasting.beams.instance_id') ?: null,
            'beams_configured'    => ! empty(config('broadcasting.beams.instance_id')) && ! empty(config('broadcasting.beams.secret_key')),
            'channels_configured' => ! empty(config('broadcasting.connections.pusher.key'))
                                  && ! empty(config('broadcasting.connections.pusher.secret'))
                                  && ! empty(config('broadcasting.connections.pusher.app_id')),
            'channels_driver_ok'  => config('broadcasting.default') === 'pusher',
            'channels_driver'     => config('broadcasting.default'),
            'channels_app_key'    => config('broadcasting.connections.pusher.key') ?: null,
            'channels_cluster'    => config('broadcasting.connections.pusher.options.cluster', 'ap1'),
        ]);
    }
}
