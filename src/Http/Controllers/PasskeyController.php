<?php

namespace HasinHayder\TyroLogin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PasskeyController extends Controller {
    /**
     * Show the passkey setup page (registration).
     *
     * The actual WebAuthn registration ceremony is handled client-side by the
     * `@laravel/passkeys` browser client, which posts to the routes registered
     * by the `laravel/passkeys` package (/user/passkeys). This controller only
     * renders the page.
     */
    public function showSetup(Request $request): View {
        if (! config('tyro-login.passkeys.enabled', false) || ! class_exists(\Laravel\Passkeys\Passkeys::class)) {
            abort(404);
        }

        $config = config('tyro-login.passkeys');

        return view('tyro-login::passkeys-setup', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'videoBackground' => config('tyro-login.video_background'),
            'title' => $config['setup_title'],
            'subtitle' => $config['setup_subtitle'],
            'buttonText' => $config['setup_button_text'],
            'cdnUrl' => $config['cdn_url'],
            'afterLoginUrl' => config('tyro-login.redirects.after_login', '/'),
        ]);
    }
}
