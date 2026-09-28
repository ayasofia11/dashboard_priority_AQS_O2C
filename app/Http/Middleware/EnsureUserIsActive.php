<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    // Le login refuse déjà les comptes désactivés. Ce middleware éjecte
    // ceux qu'on désactive PENDANT qu'ils sont connectés.
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Compte désactivé.'], 403);
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
