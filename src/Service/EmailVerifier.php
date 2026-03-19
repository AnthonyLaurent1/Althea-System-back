<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

readonly class EmailVerifier
{
    public function __construct(
        private MailerInterface        $mailer,
        private UrlGeneratorInterface  $router,
        private EntityManagerInterface $em,
        private EmailTemplateVerification $templateVerification
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmailConfirmation(User $user): void
    {
        $token = Uuid::v4()->toRfc4122();
        $user->setConfirmationToken($token);
        $this->em->flush();

        $url = $this->router->generate('api_verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = new Email()
            ->from('AltheaSystem@admin.com')
            ->to($user->getEmail())
            ->subject('Confirme ton email')
            ->html($this->templateVerification->confirmationEmail($user, $url));

        $this->mailer->send($email);
    }
}
