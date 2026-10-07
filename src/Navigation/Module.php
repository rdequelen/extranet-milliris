<?php

declare(strict_types=1);

namespace App\Navigation;

/**
 * Un volet de l'extranet, tel qu'il apparait dans le menu et sur l'accueil.
 *
 * Tant qu'un module n'a pas son propre controleur, `route` pointe sur la page
 * « a venir » partagee. Le jour ou il existe, on change `route`/`routeParams`
 * et `status` ici : ni le menu ni l'accueil n'ont a etre retouches.
 */
final readonly class Module
{
    /**
     * @param string               $code        identifiant stable, aussi utilise comme segment d'URL
     * @param string               $icon        classe Bootstrap Icons, par exemple « bi-cpu »
     * @param array<string, mixed> $routeParams parametres de `route`, hors `code`
     */
    public function __construct(
        public string $code,
        public string $label,
        public string $summary,
        public string $icon,
        public ModuleFamily $family,
        public ModuleStatus $status,
        public string $route = 'app_module_placeholder',
        public array $routeParams = [],
    ) {
    }

    public function isAvailable(): bool
    {
        return ModuleStatus::Available === $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function routeParameters(): array
    {
        return 'app_module_placeholder' === $this->route
            ? ['code' => $this->code] + $this->routeParams
            : $this->routeParams;
    }
}
