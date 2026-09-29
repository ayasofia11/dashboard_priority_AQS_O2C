<x-layouts.guest title="Connexion">
    <div class="text-center mb-4">
        <img src="{{ asset('images/aqs-logo.jpg') }}" alt="Algerian Qatari Steel" style="max-width: 150px;">
    </div>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label small" for="remember">Rester connecté</label>
            </div>
            <a href="{{ route('password.request') }}" class="small aqs-link">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="btn aqs-btn-primary w-100">Se connecter</button>
    </form>
</x-layouts.guest>

<style>
    .aqs-btn-primary { background-color: #2E7D4F; border-color: #2E7D4F; color: #fff; }
    .aqs-btn-primary:hover { background-color: #256341; border-color: #256341; color: #fff; }
    .aqs-link { color: #7B2942; text-decoration: none; }
    .aqs-link:hover { color: #5c1f32; text-decoration: underline; }
    .form-control:focus { border-color: #2E7D4F; box-shadow: 0 0 0 0.2rem rgba(46, 125, 79, 0.2); }
</style>

