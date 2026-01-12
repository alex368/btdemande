<?php

namespace App\Controller;

use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\ResetPasswordType;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ResetPasswordController extends AbstractController
{
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManager = $entityManagerInterface;
    }
    #[Route('/reset-password', name: 'app_reset_password')]
    public function index(Request $request, MailerService $mailerService)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_landing_page');
        }

        if ($request->get('email')) {
            //dd($request->get('email'));
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));

            if ($user) {
                //enrengistrer la demande de changement de mot de passe
                $resetPassword = new ResetPassword();
                $resetPassword->setUser($user);
                $resetPassword->setToken(uniqid());
                $resetPassword->setCreatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($resetPassword);
                $this->entityManager->flush();

                $mailerService->send(
                    $user->getEmail(),
                    'réintialiser mot de passe',
                    'emails/reset_password.html.twig',
                    [

                        'user' => $user,
                        'token' => $resetPassword->getToken(),
                        'id' => $resetPassword->getUser(),

                    ]
                );
                $this->addFlash('warning', 'Vous allez reçevoir un email');
            } else {
                $this->addFlash('warning', 'Cette adresse est inconnu');
            }
        }
        return $this->render('reset_password/index.html.twig');
    }


    


      #[Route('/edit-password/{token}', name: 'app_edit_password')]
    public function update($token, Request $request, UserPasswordHasherInterface $encoder)
    {
        $resetPassword = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);

        if (!$resetPassword) {
            return $this->redirectToRoute('app_reset_password');
        }

        $now = new \DateTime();
        if ($now > $resetPassword->getCreatedAt()->modify('+ 3 hour')) {
            $this->addFlash('notice', 'Votre demande de mot de passe à expiré');
            return $this->redirectToRoute('app_reset_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new_pwd = $form->get('password')->getdata();
            $password = $encoder->hashPassword($resetPassword->getUser(), $new_pwd);
            $resetPassword->getUser()->setPassword($password);
            $this->entityManager->flush();
            $this->addFlash('warning', 'Votre mot de passe a été mise à jour');
            return $this->redirectToRoute('app_login');
        }

        return $this->render(
            'reset_password/update.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}
