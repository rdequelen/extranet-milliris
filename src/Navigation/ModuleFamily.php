<?php

declare(strict_types=1);

namespace App\Navigation;

/**
 * Les trois branches de la baseline du logo MILLIRIS.
 *
 * Elles CODENT les familles de modules, elles ne decorent pas : un module porte
 * la couleur de sa branche sur son filet lateral, sa pastille de menu et
 * l'en-tete de sa tuile d'accueil — jamais en aplat derriere du texte.
 *
 * La valeur de chaque cas est aussi le suffixe de la classe CSS correspondante
 * (`.socle-family--maintenance`, ...), generee depuis la carte `$milliris-familles`
 * de assets/styles/_brand.scss. Les deux listes doivent rester alignees.
 */
enum ModuleFamily: string
{
    case Maintenance = 'maintenance';
    case ElecAutom = 'elec-autom';
    case Mecanisation = 'mecanisation';

    public function label(): string
    {
        return match ($this) {
            self::Maintenance => 'Maintenance',
            self::ElecAutom => 'Elec - Autom',
            self::Mecanisation => 'Mecanisation',
        };
    }
}
