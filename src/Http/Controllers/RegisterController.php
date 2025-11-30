<?php

namespace HasinHayder\TyroLogin\Http\Controllers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegistrationForm(Request $request): View|RedirectResponse
    {
        if (!config('tyro-login.registration.enabled', true)) {
            return redirect()->route('tyro-login.login');
        }

        // Generate captcha if enabled
        $captcha = $this->generateCaptcha($request);

        return view('tyro-login::register', [
            'layout' => config('tyro-login.layout', 'centered'),
            'branding' => config('tyro-login.branding'),
            'backgroundImage' => config('tyro-login.background_image'),
            'requirePasswordConfirmation' => config('tyro-login.password.require_confirmation', true),
            'pageContent' => config('tyro-login.pages.register'),
            'captchaEnabled' => config('tyro-login.captcha.enabled_register', false),
            'captchaQuestion' => $captcha['question'] ?? null,
            'captchaConfig' => config('tyro-login.captcha'),
        ]);
    }

    /**
     * Handle a registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        if (!config('tyro-login.registration.enabled', true)) {
            abort(403, 'Registration is disabled.');
        }

        // Get validation rules (includes captcha if enabled)
        $rules = $this->getValidationRules();
        
        // Add captcha validation if enabled
        if (config('tyro-login.captcha.enabled_register', false)) {
            $rules['captcha_answer'] = ['required', 'numeric'];
        }

        $validated = $request->validate($rules);

        // Validate captcha if enabled
        if (config('tyro-login.captcha.enabled_register', false)) {
            if (!$this->validateCaptcha($request, $validated['captcha_answer'])) {
                // Regenerate captcha for next attempt
                $this->generateCaptcha($request);
                
                throw ValidationException::withMessages([
                    'captcha_answer' => config('tyro-login.captcha.error_message', 'Incorrect answer. Please try again.'),
                ]);
            }
            unset($validated['captcha_answer']);
        }

        $userModel = config('tyro-login.user_model', 'App\\Models\\User');

        $user = $userModel::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        // Assign Tyro role if package is installed
        $this->assignTyroRole($user);

        // Check if email verification is required
        if (config('tyro-login.registration.require_email_verification', false)) {
            // Generate verification URL and log it for development
            VerificationController::generateVerificationUrl($user);

            // Store email in session for the verification notice pag`e
            $request->session()->put('tyro-login.verification.email', $user->email);

            return redirect()->route('tyro-login.verification.notice');
        }

        // Auto-login if enabled and email verification is not required
        if (config('tyro-login.registration.auto_login', true)) {
            Auth::login($user);
        }

        return redirect(config('tyro-login.redirects.after_register', '/dashboard'));
    }

    /**
     * Get the validation rules for registration.
     */
    protected function getValidationRules(): array
    {
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');
        $usersTable = (new $userModel)->getTable();
        $minLength = config('tyro-login.password.min_length', 8);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . $usersTable],
            'password' => ['required', 'string', Password::min($minLength)],
        ];

        if (config('tyro-login.password.require_confirmation', true)) {
            $rules['password'][] = 'confirmed';
        }

        return $rules;
    }

    /**
     * Generate a math captcha.
     */
    protected function generateCaptcha(Request $request): array
    {
        if (!config('tyro-login.captcha.enabled_register', false)) {
            return [];
        }

        $min = config('tyro-login.captcha.min_number', 1);
        $max = config('tyro-login.captcha.max_number', 10);

        $num1 = rand($min, $max);
        $num2 = rand($min, $max);
        
        // Randomly choose addition or subtraction
        $isAddition = (bool) rand(0, 1);
        
        if ($isAddition) {
            $question = "$num1 + $num2 = ?";
            $answer = $num1 + $num2;
        } else {
            // Ensure first number is larger for positive result
            if ($num1 < $num2) {
                [$num1, $num2] = [$num2, $num1];
            }
            $question = "$num1 - $num2 = ?";
            $answer = $num1 - $num2;
        }

        // Store answer in session
        $request->session()->put('tyro-login.captcha.register', $answer);

        return [
            'question' => $question,
            'answer' => $answer,
        ];
    }

    /**
     * Validate the captcha answer.
     */
    protected function validateCaptcha(Request $request, $answer): bool
    {
        $expected = $request->session()->get('tyro-login.captcha.register');
        
        if ($expected === null) {
            return false;
        }

        // Clear the captcha from session after validation
        $request->session()->forget('tyro-login.captcha.register');

        return (int) $answer === (int) $expected;
    }

    /**
     * Assign the default Tyro role to a user if Tyro is installed.
     */
    protected function assignTyroRole($user): void
    {
        if (!config('tyro-login.tyro.assign_default_role', true)) {
            return;
        }

        // Check if Tyro is installed
        if (!class_exists('HasinHayder\\Tyro\\Models\\Role')) {
            return;
        }

        // Check if user has the HasTyroRoles trait
        if (!method_exists($user, 'assignRole')) {
            return;
        }

        $roleSlug = config('tyro-login.tyro.default_role_slug', 'user');

        try {
            $roleModel = 'HasinHayder\\Tyro\\Models\\Role';
            $role = $roleModel::where('slug', $roleSlug)->first();

            if ($role) {
                $user->assignRole($role);
            }
        } catch (\Exception $e) {
            // Silently fail if role assignment fails
            // This prevents breaking registration if Tyro tables don't exist
            report($e);
        }
    }
}
