<?php

namespace App\Enums;

enum Role: string
{
    case Admin      = 'admin';
    case Planner    = 'planner';
    case Commercial = 'commercial';
    case Logistics  = 'logistics';
    case Reader     = 'reader';

    public function label(): string
    {
        return match ($this) {
            self::Admin      => 'Administrateur',
            self::Planner    => 'Planificateur',
            self::Commercial => 'Responsable commercial',
            self::Logistics  => 'Responsable logistique',
            self::Reader     => 'Lecteur',
        };
    }

    public function canManageUsers(): bool
    {
        return $this === self::Admin;
    }

    // T09 : le lecteur ne peut pas importer.
    public function canImport(): bool
    {
        return in_array($this, [self::Admin, self::Planner], true);
    }

    // Liste explicite : un rôle ajouté plus tard n'aura PAS ce droit tant qu'on ne l'écrit pas ici.
    public function canExport(): bool
    {
        return in_array($this, [self::Admin, self::Planner, self::Commercial, self::Logistics], true);
    }
}
