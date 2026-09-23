<?php
// app/Enums/PriorityLevel.php
namespace App\Enums;

enum PriorityLevel: string
{
    case Critique    = 'critique';
    case Urgente     = 'urgente';
    case Prioritaire = 'prioritaire';
    case Normale     = 'normale';
    case Bloquee     = 'bloquee';

    public function color(): string
    {
        return match ($this) {
            self::Critique    => 'rouge',
            self::Urgente     => 'orange',
            self::Prioritaire => 'jaune',
            self::Normale     => 'vert',
            self::Bloquee     => 'gris',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Critique    => 'Critique',
            self::Urgente     => 'Urgente',
            self::Prioritaire => 'Prioritaire',
            self::Normale     => 'Normale',
            self::Bloquee     => 'Bloquée',
        };
    }
}
