<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Client;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Les regles portees par la fiche utilisateur elle-meme : normalisation de
 * l'adresse (c'est l'identifiant de connexion) et plancher de roles.
 */
final class UserTest extends TestCase
{
    public function testTheEmailIsStoredInLowercaseAndTrimmed(): void
    {
        $user = $this->user('  Camille.DURAND@Societe.TEST ');

        self::assertSame('camille.durand@societe.test', $user->getEmail());
        // L'identifiant de securite EST l'adresse : les deux doivent coller.
        self::assertSame($user->getEmail(), $user->getUserIdentifier());
    }

    public function testEveryAccountCarriesTheClientRole(): void
    {
        self::assertSame([User::ROLE_CLIENT], $this->user()->getRoles());
    }

    public function testAnAdminCarriesBothRoles(): void
    {
        $user = $this->user();
        $user->setAdmin(true);

        self::assertTrue($user->isAdmin());
        self::assertContains(User::ROLE_ADMIN, $user->getRoles());
        self::assertContains(User::ROLE_CLIENT, $user->getRoles());
    }

    /**
     * ROLE_CLIENT est implicite : le stocker en base en plus le ferait
     * apparaitre deux fois dans la liste rendue au pare-feu.
     */
    public function testTheImplicitRoleIsNeverDuplicated(): void
    {
        $user = $this->user();
        $user->setRoles([User::ROLE_CLIENT, User::ROLE_ADMIN, User::ROLE_CLIENT]);

        $roles = $user->getRoles();

        self::assertCount(2, $roles);
        self::assertSame(array_unique($roles), $roles);
    }

    public function testRevokingAdminLeavesAPlainClient(): void
    {
        $user = $this->user();
        $user->setAdmin(true);
        $user->setAdmin(false);

        self::assertFalse($user->isAdmin());
        self::assertSame([User::ROLE_CLIENT], $user->getRoles());
    }

    public function testDisplayNameAndInitials(): void
    {
        $user = $this->user();

        self::assertSame('Camille Durand', $user->getDisplayName());
        self::assertSame('CD', $user->getInitials());
    }

    public function testAFicheWithoutNamesFallsBackToTheEmail(): void
    {
        $user = new User(new Client('DEMO', 'Societe Cliente SAS'), 'contact@societe.test', '', '');

        self::assertSame('contact@societe.test', $user->getDisplayName());
        self::assertSame('C', $user->getInitials());
    }

    public function testAnAccountIsActiveUntilToldOtherwise(): void
    {
        $user = $this->user();
        self::assertTrue($user->isActive());

        $user->setActive(false);
        self::assertFalse($user->isActive());
    }

    public function testTheClientCodeIsStoredInUppercaseAndTheNameTrimmed(): void
    {
        self::assertSame('MILLIRIS', (new Client(' milliris ', ' MILLIRIS '))->getCode());
        self::assertSame('MILLIRIS', (new Client('milliris', ' MILLIRIS '))->getName());
    }

    private function user(string $email = 'camille.durand@societe.test'): User
    {
        return new User(new Client('DEMO', 'Societe Cliente SAS'), $email, 'Camille', 'Durand');
    }
}
