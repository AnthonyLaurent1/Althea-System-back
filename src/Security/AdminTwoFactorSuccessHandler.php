<?php

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Random\RandomException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

readonly class AdminTwoFactorSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private CacheItemPoolInterface $cache,
        private MailerInterface $mailer,
    ) {}

    /**
     * @throws RandomException
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur invalide'], 401);
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new JsonResponse([
                'token' => $this->jwtManager->create($user),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                ]
            ]);
        }

        $code = (string) random_int(100000, 999999);
        $challengeId = bin2hex(random_bytes(32));

        $item = $this->cache->getItem('admin_2fa_'.$challengeId);
        $item->set([
            'userId' => $user->getId(),
            'codeHash' => password_hash($code, PASSWORD_DEFAULT),
            'attempts' => 0,
        ]);
        $item->expiresAfter(600);

        $this->cache->save($item);

        $email = new Email()
            ->from('AltheaSystem@admin.com')
            ->to($user->getEmail())
            ->subject('Code de connexion administrateur')
            ->html("
                <h2>Connexion administrateur</h2>
                <p>Voici votre code de validation :</p>
                <h1>{$code}</h1>
                <p>Ce code expire dans 10 minutes.</p>
            ");

        $this->mailer->send($email);

        return new JsonResponse([
            'requires2fa' => true,
            'challengeId' => $challengeId,
            'message' => 'Un code de vérification a été envoyé par email.',
        ]);
    }
}
