<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * LA requete de la couche de securite : seuls les comptes ACTIFS existent
     * pour l'authentification. Un compte desactive est traite exactement comme
     * un compte inconnu, et aucun ecran n'a donc a tester `isActive()`.
     */
    public function findOneActiveByEmail(string $email): ?User
    {
        return $this->findOneBy([
            'email' => User::normalizeEmail($email),
            'isActive' => true,
        ]);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => User::normalizeEmail($email)]);
    }
}
