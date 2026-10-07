<?php

declare(strict_types=1);

namespace App\Controller;

use App\Navigation\ModuleRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ModuleController extends AbstractController
{
    /**
     * Page « a venir » PARTAGEE par tous les volets pas encore ecrits.
     *
     * Elle existe pour qu'aucune entree de menu ne mene a un ecran mort : le
     * jour ou un module est livre, il prend sa propre route et disparait d'ici
     * en changeant `route` dans App\Navigation\Module.
     */
    #[Route(
        '/modules/{code}',
        name: 'app_module_placeholder',
        requirements: ['code' => '[a-z0-9-]+'],
        methods: ['GET'],
    )]
    public function placeholder(string $code, ModuleRegistry $modules): Response
    {
        $module = $modules->find($code);

        if (null === $module) {
            throw $this->createNotFoundException(sprintf('Aucun module ne porte le code « %s ».', $code));
        }

        return $this->render('module/placeholder.html.twig', [
            'module' => $module,
        ]);
    }
}
