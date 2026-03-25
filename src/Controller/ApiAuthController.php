<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Security\ApiJwtTokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ApiAuthController extends AbstractController
{
    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function __invoke(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        ApiJwtTokenManager $tokenManager,
    ): JsonResponse {
        $payload = $request->getContent() !== '' ? json_decode($request->getContent(), true) : [];
        $email = $payload['email'] ?? $request->request->get('email');
        $password = $payload['password'] ?? $request->request->get('password');

        if (!\is_string($email) || !\is_string($password) || '' === $email || '' === $password) {
            return new JsonResponse([
                'message' => 'email and password are required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse([
                'message' => 'Invalid credentials.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'token_type' => 'Bearer',
            'access_token' => $tokenManager->createToken($user),
            'expires_in' => 43200,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }
}
