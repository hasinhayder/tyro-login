<?php

namespace HasinHayder\TyroLogin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
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
        if (! $this->passkeysAvailable()) {
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

    /**
     * Show the passkey management page (list + remove).
     *
     * Lists every passkey belonging to the authenticated user and lets them
     * remove individual passkeys. Read access is always available to the auth
     * user; deletion is handled by `destroy()`.
     */
    public function showRemove(Request $request): View {
        if (! $this->passkeysAvailable()) {
            abort(404);
        }

        $user = $request->user();
        $passkeys = \Laravel\Passkeys\Passkey::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->latest()
            ->get();

        $config = config('tyro-login.passkeys');

        return view('tyro-login::passkeys-remove', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'videoBackground' => config('tyro-login.video_background'),
            'title' => $config['remove_title'],
            'subtitle' => $config['remove_subtitle'],
            'removeButtonText' => $config['remove_button_text'],
            'emptyText' => $config['empty_text'],
            'passkeys' => $passkeys,
            'afterLoginUrl' => config('tyro-login.redirects.after_login', '/'),
        ]);
    }

    /**
     * Delete one of the authenticated user's passkeys.
     *
     * The passkey is always scoped to the authenticated user, so a user can
     * only remove their own passkeys. Delegation goes through the package's
     * DeletePasskey action (which fires the PasskeyDeleted event) when present,
     * falling back to a plain model delete.
     */
    public function destroy(Request $request, string $id): RedirectResponse {
        if (! $this->passkeysAvailable()) {
            abort(404);
        }

        $user = $request->user();

        $passkey = \Laravel\Passkeys\Passkey::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->findOrFail($id);

        if (class_exists(\Laravel\Passkeys\Actions\DeletePasskey::class)) {
            app(\Laravel\Passkeys\Actions\DeletePasskey::class)($user, $passkey);
        } else {
            $passkey->delete();
        }

        return redirect()
            ->route('tyro-login.passkeys.remove')
            ->with('success', 'Passkey removed.');
    }

    /**
     * Determine whether the passkeys feature is enabled and its package present.
     */
    protected function passkeysAvailable(): bool {
        return config('tyro-login.passkeys.enabled', false)
            && class_exists(\Laravel\Passkeys\Passkeys::class);
    }
}
