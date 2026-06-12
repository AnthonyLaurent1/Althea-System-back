<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EmailVerifier;
use App\Service\PasswordReset;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, EmailVerifier $emailVerifier): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password']) || empty($data['firstName']) || empty($data['lastName'])) {
            return new JsonResponse(['error' => 'Champs obligatoires manquants'], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setPhone($data['phone'] ?? '');
        $user->setCity($data['city'] ?? '');
        $user->setCountry($data['country'] ?? '');
        $user->setAddress($data['address'] ?? '');
        $user->setAdditionalAddress($data['additionalAddress'] ?? '');
        $user->setPostalCode($data['postalCode'] ?? '');
        $user->setCompany($data['company'] ?? '');
        $user->setSiret($data['siret'] ?? '');
        $user->setIsVerified(false);

        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

        $this->em->persist($user);
        $this->em->flush();

        $emailVerifier->sendEmailConfirmation($user);

        return new JsonResponse([
            'message' => 'Utilisateur créé avec succès. Vérifiez votre email pour activer le compte.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'city' => $user->getCity(),
                'country' => $user->getCountry(),
                'address' => $user->getAddress(),
                'additionalAddress' => $user->getAdditionalAddress(),
                'postalCode' => $user->getPostalCode(),
                'company' => $user->getCompany(),
                'siret' => $user->getSiret(),
                'isVerified' => $user->isVerified()
            ]
        ], 201);
    }

    #[Route('/verify-email/{token}', name: 'api_verify_email_redirect', methods: ['GET'])]
    public function verifyEmailRedirect(string $token): \Symfony\Component\HttpFoundation\Response
    {
        return $this->redirect('http://localhost:5173/verify-email?token=' . $token);
    }

    #[Route('/verify-email/{token}', name: 'api_verify_email', methods: ['POST'])]
    public function verifyEmail(string $token, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $user = $em->getRepository(User::class)->findOneBy(['confirmationToken' => $token]);
        if (!$user) {
            return new JsonResponse(['error' => 'Token invalide'], 400);
        }

        $user->setIsVerified(true);
        $user->setConfirmationToken(null);
        $em->flush();

        $jwt = $jwtManager->create($user);

        return new JsonResponse(['message' => 'Email vérifié avec succès !', 'token' => $jwt]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request, PasswordReset $passwordReset, UserRepository $userRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['email'])) {
            return new JsonResponse(['error' => 'Email requis'], 400);
        }

        $user = $userRepo->findOneBy(['email' => $data['email']]);
        if (!$user) {
            return new JsonResponse(['message' => 'Email envoyé si le compte existe'], 200); // éviter la fuite d'info
        }

        $passwordReset->sendResetPasswordEmail($user);
        return new JsonResponse(['message' => 'Email de réinitialisation envoyé si le compte existe'], 200);
    }

    #[Route('/reset-password/{token}', name: 'api_reset_password_get', methods: ['GET'])]
    public function resetPasswordGet(string $token): \Symfony\Component\HttpFoundation\Response
    {
        return $this->redirect('http://localhost:5173/reset-password?token=' . $token);
    }

    #[Route('/reset-password/{token}', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(string $token, Request $request, UserRepository $userRepo, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $userRepo->findOneBy(['resetPasswordToken' => $token]);
        if (!$user || !$user->getResetPasswordTokenExpiresAt() || $user->getResetPasswordTokenExpiresAt() < new \DateTime()) {
            return new JsonResponse(['error' => 'Token invalide ou expiré'], 400);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['password'])) {
            return new JsonResponse(['error' => 'Mot de passe requis'], 400);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setResetPasswordToken(null);
        $user->setResetPasswordTokenExpiresAt(null);

        $this->em->flush();

        return new JsonResponse(['message' => 'Mot de passe réinitialisé avec succès'], 200);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'phone' => $user->getPhone(),
            'city' => $user->getCity(),
            'country' => $user->getCountry(),
            'address' => $user->getAddress(),
            'additionalAddress' => $user->getAdditionalAddress(),
            'postalCode' => $user->getPostalCode(),
            'company' => $user->getCompany(),
            'siret' => $user->getSiret(),
            'isVerified' => $user->isVerified()
        ]);
    }

    #[Route('/me', name: 'api_me_update', methods: ['PUT'])]
    public function updateMe(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['firstName'])) $user->setFirstName($data['firstName']);
        if (isset($data['lastName'])) $user->setLastName($data['lastName']);
        if (isset($data['phone'])) $user->setPhone($data['phone']);
        if (isset($data['city'])) $user->setCity($data['city']);
        if (isset($data['country'])) $user->setCountry($data['country']);
        if (isset($data['address'])) $user->setAddress($data['address']);
        if (isset($data['additionalAddress'])) $user->setAdditionalAddress($data['additionalAddress']);
        if (isset($data['postalCode'])) $user->setPostalCode($data['postalCode']);
        if (isset($data['company'])) $user->setCompany($data['company']);
        if (isset($data['siret'])) $user->setSiret($data['siret']);

        $this->em->flush();

        return new JsonResponse([
            'message' => 'Profil mis à jour avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'city' => $user->getCity(),
                'country' => $user->getCountry(),
                'address' => $user->getAddress(),
                'additionalAddress' => $user->getAdditionalAddress(),
                'postalCode' => $user->getPostalCode(),
                'company' => $user->getCompany(),
                'siret' => $user->getSiret(),
                'isVerified' => $user->isVerified()
            ]
        ]);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Déconnexion réussie'
        ]);
    }
}
