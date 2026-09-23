<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    // Affiche le formulaire (GET /login).
    public function create(): View
    {
        return view('auth.login');
    }

    // Traite le formulaire (POST /login).
    public function store(Request $request): RedirectResponse
    {
        // 1. Validation : si ça échoue, Laravel revient au formulaire avec les erreurs.
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 2. Anti force-brute : 5 essais ratés max par (email + adresse IP).
        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
            ]);
        }

        // 3. Tentative de connexion. Ajouter is_active => true fait que Laravel
        //    cherche seulement les comptes actifs (WHERE is_active = 1).
        //    Le mot de passe est comparé au hash, jamais en clair.
        $ok = Auth::attempt(
            [...$credentials, 'is_active' => true],
            $request->boolean('remember')
        );

        if (! $ok) {
            RateLimiter::hit($throttleKey);   // compte un essai raté

            // Même message pour "mauvais mot de passe" et "compte désactivé" :
            // on ne révèle pas à un inconnu si l'email existe.
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects ou compte désactivé.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // 4. Nouvel identifiant de session : empêche le vol de session
        //    ("session fixation") d'un attaquant qui aurait fixé l'ancien.
        $request->session()->regenerate();

        // intended() : retourne à la page demandée avant le login, sinon au dashboard.
        return redirect()->intended(route('dashboard'));
    }

    // Déconnexion (POST /logout).
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();       // détruit les données de session
        $request->session()->regenerateToken();  // nouveau jeton CSRF

        return redirect()->route('login');
    }
}
