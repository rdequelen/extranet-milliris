<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Ce qui se passe quand un visiteur anonyme demande une page protegee : on le
 * renvoie au formulaire de connexion.
 *
 * Sans point d'entree, le pare-feu repondrait « 401 Full authentication is
 * required » — exact, mais illisible pour un client. Le noyau a deja memorise
 * en session le chemin demande avant de nous appeler : apres authentification,
 * l'utilisateur retombe sur la page qu'il voulait.
 *
 * Le generateur d'URL produit un chemin qui repart de la base de la requete :
 * l'application reste donc accessible sous un prefixe d'URL (environnements de
 * recette), contrairement a un « /connexion » ecrit en dur.
 */
final class LoginFormEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
