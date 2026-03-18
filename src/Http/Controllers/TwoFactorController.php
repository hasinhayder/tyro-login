<?php

namespace HasinHayder\TyroLogin\Http\Controllers;

use HasinHayder\TyroLogin\Casts\EncryptedOrPlaintext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller {
    /**
     * Show the 2FA setup wizard.
     */
    public function showSetup(Request $request): View|RedirectResponse {
        $user = Auth::user();

        if (! $user) {
            $userId = $request->session()->get('login.id');
            if (! $userId) {
                return redirect()->route('tyro-login.login');
            }
            $userModel = config('tyro-login.user_model', 'App\\Models\\User');
            $user = $userModel::find($userId);

            if (! $user) {
                return redirect()->route('tyro-login.login');
            }
        }

        if ($user->two_factor_confirmed_at) {
            return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
        }

        $google2fa = new Google2FA;

        // Get secret key (either existing or new)
        $secretKey = $this->getTwoFactorSecret($user);

        if (! $secretKey) {
            $secretKey = $google2fa->generateSecretKey();
            $this->saveTwoFactorSecret($user, $secretKey);
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('tyro-login.branding.app_name', config('app.name')),
            $user->email,
            $secretKey
        );

        // Generate QR Code image
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return view('tyro-login::two-factor-setup', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'qrCodeSvg' => $qrCodeSvg,
            'secretKey' => $secretKey,
            'title' => config('tyro-login.two_factor.setup_title'),
            'subtitle' => config('tyro-login.two_factor.setup_subtitle'),
        ]);
    }

    /**
     * Confirm 2FA setup.
     */
    public function confirm(Request $request): RedirectResponse {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();

        if (! $user) {
            $userId = $request->session()->get('login.id');
            if (! $userId) {
                return redirect()->route('tyro-login.login');
            }
            $userModel = config('tyro-login.user_model', 'App\\Models\\User');
            $user = $userModel::find($userId);

            if (! $user) {
                return redirect()->route('tyro-login.login');
            }
        }

        $google2fa = new Google2FA;

        $secretKey = $this->getTwoFactorSecret($user);

        if (! $secretKey) {
            return back()->withErrors(['code' => 'Invalid secret key state. Please try setup again.']);
        }

        $valid = $google2fa->verifyKey($secretKey, $request->code);

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => ['The provided two factor authentication code was invalid.'],
            ]);
        }

        // Generate recovery codes
        $recoveryCodes = Collection::times(8, function () {
            return \Illuminate\Support\Str::random(10).'-'.\Illuminate\Support\Str::random(10);
        })->all();

        // Save recovery codes
        $this->saveRecoveryCodes($user, $recoveryCodes);

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        // Finalize Login if not already logged in
        if (! Auth::check()) {
            Auth::login($user, $request->session()->get('login.remember', false));
            $request->session()->forget(['login.id', 'login.remember']);
            $request->session()->regenerate();
        }

        // Redirect to show recovery codes and allow user to proceed
        return redirect()->route('tyro-login.two-factor.recovery-codes');
    }

    /**
     * Skip 2FA setup.
     */
    public function skip(Request $request): RedirectResponse {
        if (! config('tyro-login.two_factor.allow_skip', false)) {
            abort(403, 'Two factor authentication setup is required.');
        }

        $user = Auth::user();

        if (! $user) {
            $userId = $request->session()->get('login.id');
            if ($userId) {
                $userModel = config('tyro-login.user_model', 'App\\Models\\User');
                $user = $userModel::find($userId);

                if ($user) {
                    Auth::login($user, $request->session()->get('login.remember', false));
                    $request->session()->forget(['login.id', 'login.remember']);
                    $request->session()->regenerate();
                }
            }
        }

        return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
    }

    /**
     * Show recovery codes.
     */
    public function showRecoveryCodes(Request $request): View {
        $user = Auth::user();
        $recoveryCodes = $this->getRecoveryCodes($user);

        return view('tyro-login::two-factor-recovery-codes', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    /**
     * Show the 2FA challenge.
     */
    public function showChallenge(Request $request): View|RedirectResponse {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('tyro-login.login');
        }

        return view('tyro-login::two-factor-challenge', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'title' => config('tyro-login.two_factor.challenge_title'),
            'subtitle' => config('tyro-login.two_factor.challenge_subtitle'),
        ]);
    }

    /**
     * Verify the 2FA challenge.
     */
    public function verify(Request $request): RedirectResponse {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('tyro-login.login');
        }

        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        $user = $userModel::find($request->session()->get('login.id'));

        if (! $user) {
            return redirect()->route('tyro-login.login');
        }

        $request->validate([
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        if (! $request->code && ! $request->recovery_code) {
            throw ValidationException::withMessages([
                'code' => ['Please enter your authentication code.'],
            ]);
        }

        if ($request->recovery_code) {
            $recoveryCodes = $this->getRecoveryCodes($user);

            $valid = false;
            foreach ($recoveryCodes as $key => $code) {
                if ($code === $request->recovery_code) {
                    $valid = true;
                    unset($recoveryCodes[$key]);
                    break;
                }
            }

            if (! $valid) {
                throw ValidationException::withMessages([
                    'recovery_code' => ['The provided recovery code was invalid.'],
                ]);
            }

            $this->saveRecoveryCodes($user, array_values($recoveryCodes));

        } else {
            $secretKey = $this->getTwoFactorSecret($user);

            if (! $secretKey) {
                return redirect()->route('tyro-login.login')->withErrors(['email' => 'Two factor authentication has been invalidated. Please contact support.']);
            }

            $google2fa = new Google2FA;
            $valid = $google2fa->verifyKey($secretKey, $request->code);

            if (! $valid) {
                throw ValidationException::withMessages([
                    'code' => ['The provided two factor authentication code was invalid.'],
                ]);
            }
        }

        Auth::login($user, $request->session()->get('login.remember', false));
        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();

        return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
    }

    /**
     * Get two factor secret.
     */
    protected function getTwoFactorSecret($user): ?string {
        // If cast is present, it returns plaintext
        if ($this->hasCasts($user, 'two_factor_secret')) {
            return $user->two_factor_secret;
        }

        // If not cast, we try to decrypt manually
        try {
            return $user->two_factor_secret ? Crypt::decryptString($user->two_factor_secret) : null;
        } catch (\Exception $e) {
            // Fallback for legacy encrypted non-string, or plaintext
            try {
                return decrypt($user->two_factor_secret);
            } catch (\Exception $e2) {
                return $user->two_factor_secret;
            }
        }
    }

    /**
     * Save two factor secret.
     */
    protected function saveTwoFactorSecret($user, string $secret): void {
        if ($this->hasCasts($user, 'two_factor_secret')) {
            $user->forceFill(['two_factor_secret' => $secret])->save();

            return;
        }

        $user->forceFill(['two_factor_secret' => Crypt::encryptString($secret)])->save();
    }

    /**
     * Get recovery codes.
     */
    protected function getRecoveryCodes($user): array {
        if ($this->hasCasts($user, 'two_factor_recovery_codes')) {
            if (is_array($user->two_factor_recovery_codes)) {
                return $user->two_factor_recovery_codes;
            }

            return json_decode($user->two_factor_recovery_codes, true) ?? [];
        }

        if (! $user->two_factor_recovery_codes) {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($user->two_factor_recovery_codes);

            return json_decode($decrypted, true) ?? [];
        } catch (\Exception $e) {
            // Fallback
            try {
                return json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];
            } catch (\Exception $e2) {
                return [];
            }
        }
    }

    /**
     * Save recovery codes.
     */
    protected function saveRecoveryCodes($user, array $codes): void {
        $json = json_encode($codes);

        if ($this->hasCasts($user, 'two_factor_recovery_codes')) {
            $user->forceFill(['two_factor_recovery_codes' => $json])->save();

            return;
        }

        $user->forceFill(['two_factor_recovery_codes' => Crypt::encryptString($json)])->save();
    }

    /**
     * Check if user model has cast for attribute.
     */
    protected function hasCasts($user, string $key): bool {
        $casts = $user->getCasts();
        if (! array_key_exists($key, $casts)) {
            return false;
        }

        $castType = $casts[$key];

        // Check if it matches our EncryptedOrPlaintext cast
        return str_contains($castType, 'EncryptedOrPlaintext');
    }
}
