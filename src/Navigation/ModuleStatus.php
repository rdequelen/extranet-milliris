<?php

declare(strict_types=1);

namespace App\Navigation;

enum ModuleStatus: string
{
    case Available = 'available';
    case Planned = 'planned';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Planned => 'A venir',
        };
    }
}
