<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Outillage des tests qui ont besoin de vraies lignes en base.
 *
 * POURQUOI CE DETOUR : depuis la couche de securite, toute page de
 * l'application exige un utilisateur authentifie, et le pare-feu RECHARGE cet
 * utilisateur depuis la base a chaque requete (App\Security\UserProvider). Un
 * `loginUser()` sur une entite non persistee est donc deconnecte aussitot : ces
 * tests ne peuvent pas se passer d'une base, contrairement a ceux du lot 1.
 *
 * Quand la base de test n'est pas joignable, les tests qui utilisent ce trait
 * sont SAUTES et non mis en echec : `vendor/bin/phpunit` fait partie du contrat
 * de pile (.devcenter/app.yaml) et doit rester vert sur une machine qui n'a pas
 * encore sa base de recette. Le prix a payer : un oubli de base ne se voit que
 * dans le decompte des tests sautes (`--display-skipped`).
 *
 * Pour les jouer vraiment :
 *   php bin/console doctrine:database:create --env=test
 */
trait NeedsTestDatabase
{
    /**
     * Repart d'un schema vide, deduit des entites (et non des migrations : on
     * teste le code, pas le rattrapage de production).
     */
    protected function resetSchema(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();

        try {
            $metadata = $manager->getMetadataFactory()->getAllMetadata();
            $schemaTool = new SchemaTool($manager);
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        } catch (\Throwable $failure) {
            self::markTestSkipped(\sprintf(
                'Base de donnees de test indisponible (%s). Pour jouer ce test : php bin/console doctrine:database:create --env=test',
                $failure->getMessage(),
            ));
        }

        return $manager;
    }

    protected function createUser(
        EntityManagerInterface $manager,
        string $email = 'camille.durand@societe-cliente.test',
        bool $admin = false,
        bool $active = true,
        string $clientCode = 'DEMO',
        string $clientName = 'Societe Cliente SAS',
    ): User {
        $client = new Client($clientCode, $clientName);
        $user = new User($client, $email, 'Camille', 'Durand');
        $user->setAdmin($admin);
        $user->setActive($active);

        $manager->persist($client);
        $manager->persist($user);
        $manager->flush();

        return $user;
    }
}
