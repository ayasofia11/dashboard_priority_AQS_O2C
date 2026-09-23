<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Administrateur',         'admin@aqs.test',      Role::Admin],
            ['Planificateur',          'planner@aqs.test',    Role::Planner],
            ['Responsable commercial', 'commercial@aqs.test', Role::Commercial],
            ['Responsable logistique', 'logistics@aqs.test',  Role::Logistics],
            ['Lecteur',                'reader@aqs.test',     Role::Reader],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            // Relançable sans doublon : cherche par email, sinon prépare un nouveau compte.
            $user = User::firstOrNew(['email' => $email]);

            // forceFill contourne $fillable : le rôle n'est attribué qu'ici, dans le code.
            $user->forceFill([
                'name'              => $name,
                'password'          => 'Password#2026',   // haché par le cast. À changer hors développement !
                'role'              => $role,
                'is_active'         => true,
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
