<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('name', 'like', '%' . $request->search . '%')
                       ->orWhere('email', 'like', '%' . $request->search . '%')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $users->through(fn (User $u) => $this->present($u));

        return response()->json($users);
    }

    public function show(User $user)
    {
        return response()->json($this->present($user));
    }

    // Confirmé : mot de passe TOUJOURS saisi par l'admin, aucune génération.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'     => ['required', Rule::enum(Role::class)],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ]);

        $user = new User();
        $user->forceFill([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => $validated['password'],   // haché par le cast du modèle
            'role'              => $validated['role'],
            'is_active'         => true,
            'email_verified_at' => now(),
        ])->save();

        return response()->json($this->present($user), 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'  => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'  => ['sometimes', 'required', Rule::enum(Role::class)],
        ]);

        // TODO (pas encore confirmé avec l'encadrant) : un admin peut-il changer
        // son propre rôle ? Bloqué par précaution en attendant sa réponse.
        if (isset($validated['role']) && $validated['role'] !== $user->role->value && $user->is(auth()->user())) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier votre propre rôle.'], 422);
        }

        $user->forceFill($validated)->save();

        return response()->json($this->present($user->fresh()));
    }

    public function activate(User $user)
    {
        $user->forceFill(['is_active' => true])->save();
        return response()->json($this->present($user));
    }

    // Confirmé : un admin ne peut pas se désactiver lui-même.
    public function deactivate(User $user)
    {
        if ($user->is(auth()->user())) {
            return response()->json(['message' => 'Vous ne pouvez pas désactiver votre propre compte.'], 422);
        }

        $user->forceFill(['is_active' => false])->save();
        return response()->json($this->present($user));
    }

    // Confirmé : pas de "mot de passe oublié" côté utilisateur.
    // Si un utilisateur l'oublie, il prévient l'admin, qui appelle cette route.
    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ]);

        $user->forceFill(['password' => $validated['password']])->save();

        return response()->json($this->present($user));
    }

    // Forme unique de réponse : jamais de hash ni de remember_token exposés.
    private function present(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role->value,
            'role_label' => $user->role->label(),
            'is_active'  => $user->is_active,
            'created_at' => $user->created_at,
        ];
    }
}
