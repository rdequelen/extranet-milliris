<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Client;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cree une fiche utilisateur (et sa societe cliente si elle manque).
 *
 * POURQUOI UNE COMMANDE, et pas un jeu de donnees de demonstration : il n'y a
 * aucun mot de passe a « reinitialiser » dans cette application, donc aucun
 * moyen de rattraper une base sans utilisateur depuis l'ecran. C'est cette
 * commande qui amorce le tout premier compte — en pratique l'administrateur
 * MILLIRIS. L'administration des fiches a l'ecran viendra au lot suivant.
 */
#[AsCommand(
    name: 'app:user:create',
    description: 'Cree un utilisateur de l\'extranet, et sa societe cliente si le code est inconnu.',
)]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $users,
        private readonly ClientRepository $clients,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse de courriel : c\'est l\'identifiant de connexion.')
            ->addArgument('first-name', InputArgument::REQUIRED, 'Prenom.')
            ->addArgument('last-name', InputArgument::REQUIRED, 'Nom.')
            ->addArgument('client-code', InputArgument::REQUIRED, 'Code interne de la societe (cle partagee avec l\'ERP).')
            ->addOption('client-name', null, InputOption::VALUE_REQUIRED, 'Nom de la societe. Obligatoire seulement si le code n\'existe pas encore.')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Donne le role ROLE_ADMIN a cet utilisateur.')
            ->setHelp(<<<'TXT'
                Amorcer l'administrateur MILLIRIS :

                  <info>php %command.full_name% admin@milliris.com Prenom Nom MILLIRIS --client-name="MILLIRIS" --admin</info>

                Ajouter un contact chez un client deja connu :

                  <info>php %command.full_name% contact@societe.fr Prenom Nom CODECLIENT</info>
                TXT)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $io->error(\sprintf('« %s » n\'est pas une adresse de courriel valide.', $email));

            return Command::FAILURE;
        }

        if (null !== $this->users->findOneByEmail($email)) {
            $io->error(\sprintf('Un utilisateur porte deja l\'adresse « %s ».', $email));

            return Command::FAILURE;
        }

        $clientCode = (string) $input->getArgument('client-code');
        $client = $this->clients->findOneByCode($clientCode);

        if (null === $client) {
            $clientName = $input->getOption('client-name');

            if (!\is_string($clientName) || '' === trim($clientName)) {
                $io->error(\sprintf(
                    'Aucune societe ne porte le code « %s ». Pour la creer, passe --client-name="Nom de la societe".',
                    Client::normalizeCode($clientCode),
                ));

                return Command::FAILURE;
            }

            $client = new Client($clientCode, $clientName);
            $this->entityManager->persist($client);
            $io->note(\sprintf('Societe creee : %s (%s).', $client->getName(), $client->getCode()));
        }

        $user = new User(
            $client,
            $email,
            (string) $input->getArgument('first-name'),
            (string) $input->getArgument('last-name'),
        );
        $user->setAdmin((bool) $input->getOption('admin'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(\sprintf(
            'Utilisateur cree : %s <%s> — %s, %s.',
            $user->getDisplayName(),
            $user->getEmail(),
            $client->getName(),
            $user->getRoleLabel(),
        ));
        $io->writeln('Il peut maintenant demander son lien de connexion depuis /connexion.');

        return Command::SUCCESS;
    }
}
