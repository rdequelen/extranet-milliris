<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Recherche par code ERP, insensible a la casse de ce qui est passe : le
     * code est stocke normalise (voir Client::normalizeCode()).
     */
    public function findOneByCode(string $code): ?Client
    {
        return $this->findOneBy(['code' => Client::normalizeCode($code)]);
    }
}
