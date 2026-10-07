<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Security\LoginLinkSender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Le tunnel de connexion, seule partie publique de l'application.
 *
 * Quatre etapes : le formulaire (GET /connexion), l'envoi du lien
 * (POST /connexion), l'accuse d'envoi (/connexion/lien-envoye) et la
 * verification du lien (/connexion/verification), cette derniere etant traitee
 * par le pare-feu lui-meme.
 *
 * REGLE QUI GOUVERNE TOUT CET ECRAN : la reponse ne dit JAMAIS si l'adresse
 * saisie correspond a un compte. Adresse inconnue, compte desactive, panne
 * d'envoi : meme page d'accuse d'envoi dans les trois cas. Sans cela, le
 * formulaire devient un annuaire des clients de MILLIRIS.
 */
final class SecurityController extends AbstractController
{
    #[Route('/connexion', name: 'app_login', methods: ['GET'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login.html.twig', [
            // Pose par le pare-feu quand un lien est expire ou falsifie
            // (`failure_path` du pare-feu ramene ici).
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'message' => null,
            'email' => $authenticationUtils->getLastUsername(),
        ]);
    }

    #[Route('/connexion', name: 'app_login_request', methods: ['POST'])]
    public function requestLink(
        Request $request,
        UserRepository $users,
        LoginLinkSender $sender,
    ): Response {
        $email = trim($request->request->getString('email'));
        $token = $request->request->getString('_token');

        // Le jeton CSRF est verifie a la main (et non par l'attribut
        // #[IsCsrfTokenValid]) pour pouvoir re-afficher le formulaire avec un
        // message comprehensible : un jeton invalide ici veut presque toujours
        // dire « page laissee ouverte trop longtemps », pas « attaque », et une
        // page 403 serait une impasse pour le client.
        if (!$this->isCsrfTokenValid('app_login', $token)) {
            return $this->renderFormError(
                'Votre page a expire avant l\'envoi. Merci de redemander un lien.',
                $email,
            );
        }

        if ('' === $email || !filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->renderFormError('Cette adresse de courriel n\'est pas valide.', $email);
        }

        $user = $users->findOneActiveByEmail($email);

        if (null !== $user) {
            $sender->send($user);
        }

        // Redirection (et non rendu direct) : la page d'accuse d'envoi doit
        // pouvoir etre rechargee sans renvoyer un lien, et Turbo attend une
        // redirection sur une soumission reussie.
        return $this->redirectToRoute('app_login_link_sent');
    }

    #[Route('/connexion/lien-envoye', name: 'app_login_link_sent', methods: ['GET'])]
    public function linkSent(): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/link_sent.html.twig');
    }

    /**
     * Cible du lien recu par courriel. Le pare-feu (`login_link`) intercepte la
     * requete AVANT le controleur : ce corps n'est jamais execute. La route doit
     * malgre tout exister, c'est elle qui fabrique l'URL du lien.
     */
    #[Route('/connexion/verification', name: 'app_login_check', methods: ['GET'])]
    public function check(): never
    {
        throw new \LogicException('Route interceptee par le pare-feu (login_link) : verifier `check_route` dans config/packages/security.yaml.');
    }

    /**
     * Deconnexion, egalement traitee par le pare-feu (`logout`).
     */
    #[Route('/deconnexion', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Route interceptee par le pare-feu (logout) : verifier `logout.path` dans config/packages/security.yaml.');
    }

    /**
     * Re-affichage du formulaire en erreur.
     *
     * Le code 422 n'est pas decoratif : Turbo Drive (installe sur ce socle)
     * ignore une reponse 200 a une soumission de formulaire et laisse la page
     * inchangee. 422 est le code qu'il attend pour remplacer la page par le
     * formulaire en erreur.
     */
    private function renderFormError(string $message, string $email): Response
    {
        return $this->render('security/login.html.twig', [
            'error' => null,
            'message' => $message,
            'email' => $email,
        ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
