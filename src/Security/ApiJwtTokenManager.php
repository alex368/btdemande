<?php

namespace App\Security;

use App\Entity\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class ApiJwtTokenManager
{
    public function __construct(
        private readonly string $secret,
    ) {
    }

    public function createToken(User $user): string
    {
        $payload = [
            'sub' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'iat' => time(),
            'exp' => time() + 43200,
        ];

        return JWT::encode($payload, $this->getSigningKey(), 'HS256');
    }

    public function getUserIdentifierFromToken(string $token): string
    {
        $payload = JWT::decode($token, new Key($this->getSigningKey(), 'HS256'));
        $identifier = $payload->sub ?? null;

        if (!\is_string($identifier) || '' === $identifier) {
            throw new \UnexpectedValueException('Invalid token payload.');
        }

        return $identifier;
    }

    private function getSigningKey(): string
    {
        return hash('sha256', $this->secret, true);
    }
}
