<?php

namespace HasinHayder\TyroLogin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MagicLinkSessionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && $request->session()->has('tyro_magic_link_expires_at')) {
            $expiresAt = $request->session()->get('tyro_magic_link_expires_at');
            
            if (now()->timestamp > $expiresAt) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('tyro-login.login')
                    ->withErrors(['login' => 'Your magic link session has expired.']);
            }
        }

        return $next($request);
    }
}
