<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        // Les tuiles sont rendues depuis la variable Twig globale `modules`
        // (App\Navigation\ModuleRegistry) : rien a passer ici.
        return $this->render('home/index.html.twig');
    }
}
