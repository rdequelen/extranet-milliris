<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Une personne qui se connecte — le second des deux niveaux de la securite.
 *
 * Elle est TOUJOURS rattachee a une societe cliente (`client`, non nul) :
 * l'administrateur MILLIRIS lui-meme est un utilisateur du client « MILLIRIS ».
 * Cette regle evite un cas particulier (« utilisateur sans client ») que chaque
 * ecran aurait eu a gerer.
 *
 * AUCUN SECRET N'EST PORTE ICI : pas de mot de passe, pas de jeton stocke. On
 * entre par un lien de connexion signe, dont la validite se recalcule a chaque
 * clic (voir config/packages/security.yaml). Une fuite de la table ne donne
 * donc acces a rien.
 *
 * Les classes d'entite ne sont pas `final` : Doctrine derive la classe pour
 * fabriquer ses mandataires de chargement differe.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
// « user » est un mot reserve de PostgreSQL : la table s'appelle `app_user`,
// plutot que de dependre du guillemetage de Doctrine dans chaque requete ecrite
// a la main plus tard.
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(name: 'uniq_app_user_email', columns: ['email'])]
#[ORM\Index(name: 'idx_app_user_client', columns: ['client_id'])]
class User implements UserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * Role plancher : tout compte authentifie le porte, il n'est donc jamais
     * stocke en base. C'est lui que `access_control` exige sur `^/`.
     */
    public const ROLE_CLIENT = 'ROLE_CLIENT';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Identifiant de connexion. Normalise en minuscules : l'adresse saisie dans
     * le formulaire doit retrouver la fiche quelle que soit la casse tapee, et
     * l'unicite de la colonne doit valoir sur l'adresse reelle, pas sur son
     * habillage.
     */
    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 80)]
    private string $firstName;

    #[ORM\Column(length: 80)]
    private string $lastName;

    /**
     * Interrupteur d'acces. Un compte inactif est introuvable pour le
     * fournisseur d'utilisateurs : il ne peut plus demander de lien, et ses
     * sessions deja ouvertes tombent a la requete suivante.
     */
    #[ORM\Column]
    private bool $isActive = true;

    /**
     * Roles SUPPLEMENTAIRES, au-dela de ROLE_CLIENT. En pratique aujourd'hui :
     * vide, ou ['ROLE_ADMIN'].
     *
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    /**
     * `fetch: EAGER` et non le chargement differe par defaut : le nom de la
     * societe est affiche dans l'en-tete de CHAQUE page, et l'utilisateur est
     * recharge a chaque requete. Un mandataire differe ferait donc une seconde
     * requete systematique, et surtout voyagerait dans la session (ou il n'a
     * plus d'EntityManager pour se resoudre).
     */
    #[ORM\ManyToOne(targetEntity: Client::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private Client $client;

    public function __construct(Client $client, string $email, string $firstName, string $lastName)
    {
        $this->client = $client;
        $this->email = self::normalizeEmail($email);
        $this->firstName = trim($firstName);
        $this->lastName = trim($lastName);
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = self::normalizeEmail($email);
    }

    /**
     * L'adresse fait foi : c'est elle qui signe le lien de connexion
     * (`signature_properties` du pare-feu). La changer invalide donc les liens
     * deja envoyes, ce qui est le comportement voulu.
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = trim($firstName);
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = trim($lastName);
    }

    /**
     * Ce qu'on affiche a l'ecran. Repli sur l'adresse si la fiche n'a pas de
     * nom : mieux vaut une identite moche qu'une identite vide dans l'en-tete.
     */
    public function getDisplayName(): string
    {
        $name = trim($this->firstName.' '.$this->lastName);

        return '' !== $name ? $name : $this->email;
    }

    /**
     * Les initiales de la pastille de l'en-tete, une ou deux lettres.
     */
    public function getInitials(): string
    {
        $letters = array_filter([
            mb_substr($this->firstName, 0, 1),
            mb_substr($this->lastName, 0, 1),
        ], static fn (string $letter): bool => '' !== $letter);

        if ([] === $letters) {
            $letters = [mb_substr($this->email, 0, 1)];
        }

        return mb_strtoupper(implode('', $letters));
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = self::ROLE_CLIENT;

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles roles supplementaires ; ROLE_CLIENT est
     *                            implicite et retire s'il est passe
     */
    public function setRoles(array $roles): void
    {
        $this->roles = array_values(array_unique(array_filter(
            $roles,
            static fn (string $role): bool => self::ROLE_CLIENT !== $role,
        )));
    }

    public function isAdmin(): bool
    {
        return \in_array(self::ROLE_ADMIN, $this->roles, true);
    }

    public function setAdmin(bool $admin): void
    {
        $this->setRoles($admin ? [self::ROLE_ADMIN] : []);
    }

    /**
     * Libelle de role affiche a l'ecran (en-tete). Il n'a pas a etre exhaustif :
     * un seul role optionnel existe a ce jour.
     */
    public function getRoleLabel(): string
    {
        return $this->isAdmin() ? 'Administrateur MILLIRIS' : 'Compte client';
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * Exigee par UserInterface jusqu'a Symfony 8. Cette fiche ne porte aucune
     * donnee d'authentification en memoire : il n'y a rien a effacer.
     *
     * @deprecated depuis Symfony 7.3, retiree de UserInterface en 8.0
     */
    public function eraseCredentials(): void
    {
    }
}
