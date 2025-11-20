<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Créer un nouvel utilisateur interactif.',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Prénom
        $name = $io->ask('👤 Prénom :');

        // Nom de famille
        $lastname = $io->ask('🧑‍💼 Nom de famille :');

        // Numéro de téléphone
        $number = $io->ask('📱 Numéro de téléphone :');

        // Email
        $email = $io->ask('📧 Adresse e-mail :', null, function ($email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Adresse e-mail invalide.');
            }
            return $email;
        });

        // Vérification de l'unicité
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error("Un utilisateur avec l'e-mail \"$email\" existe déjà.");
            return Command::FAILURE;
        }

        // Mot de passe
        $plainPassword = $io->askHidden('🔑 Mot de passe :', function ($password) {
            if (strlen($password) < 6) {
                throw new \RuntimeException('Le mot de passe doit contenir au moins 6 caractères.');
            }
            return $password;
        });

        // Choix du rôle
        $roleChoice = $io->choice(
            '👤 Rôle de l\'utilisateur :',
            ['collaborateur', 'admin', 'client'],
            'client'
        );

        $roleMap = [
            'collaborateur' => 'ROLE_COLLABORATEUR',
            'admin' => 'ROLE_ADMIN',
            'client' => 'ROLE_CUSTOMER',
        ];

        // Création de l'utilisateur
        $user = new User();
        $user->setName($name);
        $user->setLastname($lastname);
        $user->setNumber($number);
        $user->setEmail($email);
        $user->setRoles([$roleMap[$roleChoice]]);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->em->persist($user);
        $this->em->flush();

        $io->success("✅ Utilisateur \"$email\" créé avec succès.");
        return Command::SUCCESS;
    }
}
