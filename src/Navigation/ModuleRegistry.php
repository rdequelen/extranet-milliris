<?php

declare(strict_types=1);

namespace App\Navigation;

/**
 * Catalogue des modules de l'extranet — SOURCE UNIQUE.
 *
 * Expose a Twig sous le nom `modules` (config/packages/twig.yaml). La barre
 * laterale, les tuiles d'accueil, la page « a venir » et la resolution d'un
 * code d'URL lisent tous ici : ajouter un volet, c'est ajouter une entree dans
 * ce constructeur, rien d'autre.
 *
 * La liste est codee en dur et non configurable : ces volets sont des decisions
 * produit, pas des reglages d'exploitation. Les y mettre en base ou en YAML
 * ferait payer un indirection a chaque lecteur sans rien apporter.
 */
final class ModuleRegistry
{
    /** @var list<Module> */
    private array $modules;

    public function __construct()
    {
        $this->modules = [
            new Module(
                code: 'contrat-heures',
                label: 'Contrat & heures',
                summary: 'Votre contrat de maintenance, le suivi de vos heures et la consommation de vos tickets.',
                icon: 'bi-file-earmark-text',
                family: ModuleFamily::Maintenance,
                status: ModuleStatus::Planned,
            ),
            new Module(
                code: 'gmao',
                label: 'GMAO / parc automatisme',
                summary: 'Le suivi de votre parc d\'automatisme, ses interventions, et a terme l\'assistance IA sur vos automates.',
                icon: 'bi-cpu',
                family: ModuleFamily::ElecAutom,
                status: ModuleStatus::Planned,
            ),
            new Module(
                code: 'mecanisation',
                label: 'Mecanisation',
                summary: 'Vos equipements de mecanisation, leurs mises en service et leur maintenance.',
                icon: 'bi-gear-wide-connected',
                family: ModuleFamily::Mecanisation,
                status: ModuleStatus::Planned,
            ),
        ];
    }

    /**
     * @return list<Module>
     */
    public function all(): array
    {
        return $this->modules;
    }

    public function find(string $code): ?Module
    {
        foreach ($this->modules as $module) {
            if ($module->code === $code) {
                return $module;
            }
        }

        return null;
    }

    /**
     * Les familles qui portent au moins un module, dans l'ordre de l'enum.
     *
     * @return list<ModuleFamily>
     */
    public function families(): array
    {
        return array_values(array_filter(
            ModuleFamily::cases(),
            fn (ModuleFamily $family): bool => [] !== $this->byFamily($family),
        ));
    }

    /**
     * @return list<Module>
     */
    public function byFamily(ModuleFamily $family): array
    {
        return array_values(array_filter(
            $this->modules,
            fn (Module $module): bool => $module->family === $family,
        ));
    }
}
