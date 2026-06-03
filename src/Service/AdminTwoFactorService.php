<?php

namespace App\Service;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

final class AdminTwoFactorService
{
    private const CACHE_PREFIX = 'admin_2fa_';
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly UserRepository $userRepository,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    /**
     * Vérifie le code OTP associé à un challenge et renvoie le JWT admin.
     *
     * @return array{token: string}
     */
    public function verify(string $challengeId, string $code): array
    {
        $challengeId = trim($challengeId);
        $code = trim($code);

        if ($challengeId === '' || $code === '') {
            throw new \InvalidArgumentException('challengeId et code sont requis.');
        }

        $item = $this->cache->getItem(self::CACHE_PREFIX.$challengeId);
        if (!$item->isHit()) {
            throw new \InvalidArgumentException('Challenge invalide ou expiré.');
        }

        /** @var array{userId: int, codeHash: string, attempts: int} $payload */
        $payload = $item->get();

        if ($payload['attempts'] >= self::MAX_ATTEMPTS) {
            $this->cache->deleteItem(self::CACHE_PREFIX.$challengeId);
            throw new \InvalidArgumentException('Trop de tentatives. Veuillez vous reconnecter.');
        }

        if (!password_verify($code, $payload['codeHash'])) {
            $payload['attempts']++;
            $item->set($payload);
            $this->cache->save($item);

            throw new \InvalidArgumentException('Code invalide.');
        }

        $this->cache->deleteItem(self::CACHE_PREFIX.$challengeId);

        $user = $this->userRepository->find($payload['userId']);
        if ($user === null) {
            throw new \InvalidArgumentException('Utilisateur introuvable.');
        }

        return ['token' => $this->jwtManager->create($user)];
    }
}
