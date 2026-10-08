<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * La societe cliente — le premier des deux niveaux de la securite.
 *
 * Fiche VOLONTAIREMENT MINIMALE : le nom commercial et le code interne, qui est
 * la cle de rapprochement avec l'ERP. Toute autre donnee de societe (adresse,
 * contacts, contrats) appartient a l'ERP, qui en reste la source : la dupliquer
 * ici fabriquerait deux verites.
 *
 * Les classes d'entite ne sont pas `final` : Doctrine derive la classe pour
 * fabriquer ses mandataires de chargement differe.
 */
#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'client')]
#[ORM\UniqueConstraint(name: 'uniq_client_code', columns: ['code'])]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Cle unique partagee avec l'ERP. Normalisee en MAJUSCULES : l'ERP ne
     * distingue pas la casse, la base si — sans normalisation, « milliris » et
     * « MILLIRIS » coexisteraient et designeraient deux societes.
     */
    #[ORM\Column(length: 32)]
    private string $code;

    #[ORM\Column(length: 180)]
    private string $name;

    // LE COTE INVERSE (la liste des utilisateurs d'une societe) N'EST PAS
    // MAPPE, volontairement. Deux raisons :
    //  - aucun ecran ne l'utilise a ce jour (l'administration des fiches
    //    arrive au lot suivant) ;
    //  - l'utilisateur authentifie est SERIALISE dans la session a chaque
    //    requete, avec sa societe. Une collection Doctrine dans ce graphe
    //    ferait voyager un objet lie a l'EntityManager dans la session, pour
    //    rien. Le jour ou un ecran listera les comptes d'un client, il le fera
    //    par UserRepository, ou en ajoutant ici la relation inverse en
    //    connaissance de cause.

    public function __construct(string $code, string $name)
    {
        $this->code = self::normalizeCode($code);
        $this->name = trim($name);
    }

    public static function normalizeCode(string $code): string
    {
        return mb_strtoupper(trim($code));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = self::normalizeCode($code);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }
}
