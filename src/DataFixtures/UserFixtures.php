<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'email' => 'subvention@btdconsulting.fr',
                'password' => 'N4d!7xR2qL9b',
                'lastname' => 'Taleb',
                'firstname' => 'Nadia',
                'role' => ['ROLE_COLLABORATOR'],
            ],
            [
                'email' => 'communication@btdconsulting.fr',
                'password' => 'M9r@6TqF3yZa',
                'lastname' => 'Letaif',
                'firstname' => 'Mohamed Mehdi',
                'role' => ['ROLE_COLLABORATOR'],
            ],
            [
                'email' => 'contact@btdconsulting.fr',
                'password' => 'Aq3$J7pL0hVz',
                'lastname' => 'Ganvo',
                'firstname' => 'Alex',
                'role' => ['ROLE_ADMIN'],
            ],
            [
                'email' => 'dispositif@btdconsulting.fr',
                'password' => 'sV6&nX1uR8cE',
                'lastname' => 'Inconnu',
                'firstname' => 'Sylvain',
                'role' => ['ROLE_COLLABORATOR'],
            ],
            [
                'email' => 'aide@btdconsulting.fr',
                'password' => 'rB7!zK4mY2pH',
                'lastname' => 'Bouziane',
                'firstname' => 'Rim',
                'role' => ['ROLE_COLLABORATOR'],
            ],
            [
                'email' => 'marketing@btdconsulting.fr',
                'password' => 'vP8#s2KzW1mQ',
                'lastname' => 'Morel',
                'firstname' => 'Margaux',
                'role' => ['ROLE_CUSTOMER'],
            ],
        ];

        foreach ($users as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setName($data['firstname']);
            $user->setLastname($data['lastname']);
            $user->setNumber(''); // ou un numéro générique
            $user->setRoles($data['role']);

            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
