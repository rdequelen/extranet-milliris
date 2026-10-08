<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Fournisseur d'utilisateurs de l'extranet.
 *
 * POURQUOI un fournisseur maison plutot que le fournisseur Doctrine fourni par
 * Symfony : ce dernier recharge la fiche par sa CLE PRIMAIRE a chaque requete
 * (`$repository->find($id)`), sans regarder `is_active`. Une desactivation
 * n'aurait donc eu d'effet qu'a la prochaine demande de lien, et une session
 * ouverte aurait survecu — ce qui n'est pas acceptable pour un interrupteur
 * d'acces.
 *
 * Ici, les deux chemins (connexion et rafraichissement de session) passent par
 * la MEME requete « actif et cette adresse ». Consequences a connaitre :
 *  - desactiver un compte deconnecte ses sessions a la requete suivante ;
 *  - changer l'adresse d'une fiche deconnecte aussi (l'adresse EST l'identifiant
 *    stocke en session).
 *
 * @implements UserProviderInterface<User>
 */
final class UserProvider implements UserProviderInterface
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->users->findOneActiveByEmail($identifier);

        if (null === $user) {
            // Un compte inactif est indistinguable d'un compte inexistant :
            // c'est voulu, cote securite comme cote message affiche.
            $exception = new UserNotFoundException();
            $exception->setUserIdentifier($identifier);

            throw $exception;
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Le fournisseur ne sait pas rafraichir « %s ».', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
