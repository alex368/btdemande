<?php

namespace App\Security;

use App\Repository\ApiKeyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

final class BearerTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly ApiJwtTokenManager $tokenManager,
        private readonly UserRepository $userRepository,
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $header = $request->headers->get('Authorization');

        return \is_string($header) && str_starts_with($header, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $header = (string) $request->headers->get('Authorization');
        $token = trim(substr($header, 7));

        if ('' === $token) {
            throw new AuthenticationException('Missing bearer token.');
        }

        try {
            $identifier = $this->tokenManager->getUserIdentifierFromToken($token);

            return new SelfValidatingPassport(
                new UserBadge($identifier, fn (string $userIdentifier) => $this->userRepository->findOneBy(['email' => $userIdentifier]))
            );
        } catch (\Throwable) {
            $apiKey = $this->apiKeyRepository->findActiveByTokenHash(hash('sha256', $token));

            if (null === $apiKey || null === $apiKey->getUser()) {
                throw new AuthenticationException('Invalid bearer token.');
            }

            $apiKey->setLastUsedAt(new \DateTimeImmutable());
            $this->em->flush();

            $identifier = (string) $apiKey->getUser()->getUserIdentifier();

            return new SelfValidatingPassport(
                new UserBadge($identifier, fn (string $userIdentifier) => $this->userRepository->findOneBy(['email' => $userIdentifier]))
            );
        }
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => 'Invalid or expired bearer token.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'message' => 'Bearer token required.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
