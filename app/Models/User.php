<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // IMPORTANT : role et is_active ne sont PAS ici.
    // $fillable = les colonnes qu'un formulaire a le droit de remplir.
    // Si "role" y était, quelqu'un pourrait envoyer role=admin depuis un formulaire modifié.
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',     // hache automatiquement le mot de passe
            'role'              => Role::class,  // "planner" en base devient Role::Planner en PHP
            'is_active'         => 'boolean',
        ];
    }
}
