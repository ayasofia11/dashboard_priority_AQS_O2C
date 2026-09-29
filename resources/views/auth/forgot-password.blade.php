<x-layouts.guest title="Mot de passe oublié">
    <div class="text-center mb-4">
        <img src="{{ asset('images/aqs-logo.jpg') }}" alt="Algerian Qatari Steel" style="max-width: 150px;">
    </div>
    <p class="text-muted small mb-3">Indiquez votre e-mail, un lien de réinitialisation vous sera envoyé.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror" required autofocus>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn aqs-btn-primary w-100">Envoyer le lien</button>
    </form>
</x-layouts.guest>

<style>
    .aqs-btn-primary { background-color: #2E7D4F; border-color: #2E7D4F; color: #fff; }
    .aqs-btn-primary:hover { background-color: #256341; border-color: #256341; color: #fff; }
    .aqs-link { color: #7B2942; text-decoration: none; }
    .aqs-link:hover { color: #5c1f32; text-decoration: underline; }
    .form-control:focus { border-color: #2E7D4F; box-shadow: 0 0 0 0.2rem rgba(46, 125, 79, 0.2); }
</style>
