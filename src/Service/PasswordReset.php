<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

readonly class PasswordReset
{
    public function __construct(
        private MailerInterface            $mailer,
        private EntityManagerInterface     $em,
        private UrlGeneratorInterface      $router,
        private EmailTemplatePasswordReset $templatePasswordReset
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function sendResetPasswordEmail(User $user): void
    {
        $token = Uuid::v4()->toRfc4122();
        $user->setResetPasswordToken($token);

        $user->setResetPasswordTokenExpiresAt(new \DateTime('+1 hour'));
        $this->em->flush();

        $url = $this->router->generate('api_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = new Email()
            ->from('AltheaSystem@admin.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($this->templatePasswordReset->resetPasswordEmail($user, $url));

        $this->mailer->send($email);
    }
}
