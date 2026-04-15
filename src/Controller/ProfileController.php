<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserPasswordType;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile/{id}', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(
        User $user,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $canAccess = $this->isGranted('ROLE_ADMIN') || $currentUser->getId() === $user->getId();
        if (!$canAccess) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce profil.');
        }

        $form = $this->createForm(UserType::class, $user, [
            'include_referent' => false,
            'include_password' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');

            return $this->redirectToRoute('app_profile', ['id' => $user->getId()]);
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form->createView(),
            'profileUser' => $user,
        ]);
    }

    #[Route('/password/{id}', name: 'app_profile_password', methods: ['GET', 'POST'])]
    public function password(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $canAccess = $this->isGranted('ROLE_ADMIN') || $currentUser->getId() === $user->getId();
        if (!$canAccess) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce mot de passe.');
        }

        $form = $this->createForm(UserPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = (string) $form->get('currentPassword')->getData();
            $newPassword = (string) $form->get('newPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('danger', 'Le mot de passe actuel est incorrect.');
            } elseif ($passwordHasher->isPasswordValid($user, $newPassword)) {
                $this->addFlash('warning', 'Le nouveau mot de passe doit être différent de l\'actuel.');
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $em->flush();

                $this->addFlash('success', 'Mot de passe modifié avec succès.');
                return $this->redirectToRoute('app_profile', ['id' => $user->getId()]);
            }
        }

        return $this->render('profile/password.html.twig', [
            'form' => $form->createView(),
            'profileUser' => $user,
        ]);
    }
}
