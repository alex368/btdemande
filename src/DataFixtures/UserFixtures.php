<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const CUSTOMER_ALICE = 'user.customer.alice';
    public const CUSTOMER_BRUNO = 'user.customer.bruno';
    public const CUSTOMER_CHLOE = 'user.customer.chloe';
    public const CUSTOMER_DIEGO = 'user.customer.diego';
    private const GENERATED_CUSTOMER_COUNT = 200;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'reference' => self::CUSTOMER_ALICE,
                'email' => 'alice.martin@example.test',
                'password' => 'customer123',
                'lastname' => 'Martin',
                'firstname' => 'Alice',
                'number' => '0600000001',
            ],
            [
                'reference' => self::CUSTOMER_BRUNO,
                'email' => 'bruno.dupont@example.test',
                'password' => 'customer123',
                'lastname' => 'Dupont',
                'firstname' => 'Bruno',
                'number' => '0600000002',
            ],
            [
                'reference' => self::CUSTOMER_CHLOE,
                'email' => 'chloe.bernard@example.test',
                'password' => 'customer123',
                'lastname' => 'Bernard',
                'firstname' => 'Chloe',
                'number' => '0600000003',
            ],
            [
                'reference' => self::CUSTOMER_DIEGO,
                'email' => 'diego.leroy@example.test',
                'password' => 'customer123',
                'lastname' => 'Leroy',
                'firstname' => 'Diego',
                'number' => '0600000004',
            ],
        ];

        for ($index = 5; $index <= self::GENERATED_CUSTOMER_COUNT; ++$index) {
            $users[] = [
                'reference' => sprintf('user.customer.%03d', $index),
                'email' => sprintf('customer%03d@example.test', $index),
                'password' => 'customer123',
                'lastname' => sprintf('Client%03d', $index),
                'firstname' => sprintf('Customer%03d', $index),
                'number' => sprintf('06%08d', $index),
            ];
        }

        foreach ($users as $data) {
            $user = $manager->getRepository(User::class)->findOneBy(['email' => $data['email']]) ?? new User();
            $user->setEmail($data['email']);
            $user->setName($data['firstname']);
            $user->setLastname($data['lastname']);
            $user->setNumber($data['number']);
            $user->setRoles(['ROLE_CUSTOMER']);

            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $manager->persist($user);
            $this->addReference($data['reference'], $user);
        }
        $manager->flush();
    }
}
