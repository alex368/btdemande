<?php
namespace App\Service;

use App\Entity\Contact;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ContactConverterService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

   public function convertContactToUser(Contact $contact): array
{
    $user = new User();

    $emails = $contact->getEmail();
    $email = is_array($emails) && count($emails) > 0 ? $emails[0] : null;

    if (!$email) {
        throw new \InvalidArgumentException('Le contact doit avoir au moins une adresse email.');
    }

    $user->setEmail($email);
    $user->setName($contact->getFirstName() ?? '');
    $user->setLastname($contact->getLastName() ?? '');
    $user->setNumber(($contact->getPhone()[0] ?? '') ?: ($contact->getMobilePhone()[0] ?? ''));

    $plainPassword = bin2hex(random_bytes(5));
    $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
    $user->setPassword($hashedPassword);
    $user->setRoles(['ROLE_USER']);

    $this->em->persist($user);
    $this->em->flush();

    return [
        'user' => $user,
        'plainPassword' => $plainPassword,
    ];
}

}
