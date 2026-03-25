<?php

namespace App\Command;

use App\Entity\ApiKey;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-api-key',
    description: 'Genere une cle API personnelle pour un admin ou collaborateur.',
)]
class CreateApiKeyCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, "Email de l'utilisateur")
            ->addArgument('name', InputArgument::OPTIONAL, 'Nom de la cle API', 'openclawMailer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');
        $name = (string) $input->getArgument('name');

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $io->error(sprintf('Aucun utilisateur trouve pour "%s".', $email));

            return Command::FAILURE;
        }

        $roles = $user->getRoles();
        $isAllowed = \in_array('ROLE_ADMIN', $roles, true)
            || \in_array('ROLE_COLLABORATOR', $roles, true)
            || \in_array('ROLE_COLLABORATEUR', $roles, true);

        if (!$isAllowed) {
            $io->error('La cle API ne peut etre creee que pour un admin ou un collaborateur.');

            return Command::FAILURE;
        }

        $plainToken = 'btdm_' . bin2hex(random_bytes(24));

        $apiKey = new ApiKey();
        $apiKey->setUser($user);
        $apiKey->setName($name);
        $apiKey->setTokenPrefix(substr($plainToken, 0, 12));
        $apiKey->setTokenHash(hash('sha256', $plainToken));
        $apiKey->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($apiKey);
        $this->em->flush();

        $io->success('Cle API creee. Copie-la maintenant, elle ne sera plus reaffichee.');
        $io->writeln(sprintf('Utilisateur : %s', $user->getEmail()));
        $io->writeln(sprintf('Nom : %s', $name));
        $io->writeln(sprintf('Cle API : %s', $plainToken));

        return Command::SUCCESS;
    }
}
