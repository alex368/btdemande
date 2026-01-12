<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordType;
use App\Form\UserPasswordType;
use App\Form\UserType;
use App\Model\UserPasswordDto;
use App\Repository\UserRepository;
use App\Service\DashboardService;
use App\Service\SidebarService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile/{id}', name: 'app_profile')]
    public function index(
        int $id,
        User $user,
        EntityManagerInterface $em,
        Request $request,
    ): Response {{
        // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'utilisateur par UUID
        $user = $em->getRepository(User::class)->findOneBy(['id' => $id]);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }
        // $filesystem = new Filesystem();
        // $oldImage = $user->getImageProfile();
        // Créer le formulaire et lier les données de l'utilisateur
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            

            // Enregistrer les modifications dans la base de données
                $em->flush();
            


            // Rediriger vers le tableau de bord après la mise à jour
            return $this->redirectToRoute('app_dashboard');
        }

        // Afficher le formulaire de modification de profil
        return $this->render('profile/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}



#[Route('/password/{id}', name: 'app_profile_password')]
public function password(string $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $encoder): Response
    {
        $notification = null;

        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'utilisateur par UUID
        $user = $em->getRepository(User::class)->findOneBy(['id' => $id]);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        // Créer le formulaire et lier les données de l'utilisateur
        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $old_pwd = $form->get('old_password')->getData();


            if ($encoder->isPasswordValid($user, $old_pwd)) {
                $new_pwd = $form->get('new_password')->getData();
                $password = $encoder->hashPassword($user, $new_pwd);
                $user->setPassword($password); // Hash le mot de passe à partir du setter

                $em>flush(); // Enregistrer les changements dans la base de données

                $notification = 'Mot de passe modifié avec succès';
            } else {
                $notification = 'Mauvais mot de passe';
            }

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('profile/password.html.twig', [
            'form' => $form->createView(),
            'notification' => $notification
        ]);
    
    }

}