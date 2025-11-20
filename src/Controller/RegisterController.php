<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function index(EntityManagerInterface $em, Request $request, UserPasswordHasherInterface $hasher): Response
    {

        $user = new User();
        $form = $this->createForm(UserType::class,$user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si tu dois encoder un mot de passe (optionnel)
           
               $hashedPassword = $hasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_CUSTOMER']);
            // Persistance de l'utilisateur
            $em->persist($user);
            $em->flush();

            // Redirection ou message flash
            $this->addFlash('success', 'Utilisateur créé avec succès.');

            return $this->redirectToRoute('app_login'); // ou autre route
        }

        
        
        return $this->render('register/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
