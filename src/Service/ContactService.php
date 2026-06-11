<?php

namespace App\Service;

use App\Entity\ContactRequest;
use App\Repository\ContactRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class ContactService
{
    private const string STATUS_NEW = 'new';
    private const string STATUS_READ = 'read';
    private const string STATUS_REPLIED = 'replied';
    private const array ALLOWED_STATUSES = [self::STATUS_NEW, self::STATUS_READ, self::STATUS_REPLIED];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactRequestRepository $contactRequestRepository,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * Réception d'un message depuis le formulaire de contact public.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function createFromForm(array $data): array
    {
        $email = trim((string) ($data['email'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Adresse email invalide.');
        }
        if ($subject === '') {
            throw new \InvalidArgumentException('Le sujet est requis.');
        }
        if ($message === '') {
            throw new \InvalidArgumentException('Le message est requis.');
        }

        $contact = new ContactRequest();
        $contact->setEmail($email);
        $contact->setSubject($subject);
        $contact->setMessage($message);
        $contact->setStatus(self::STATUS_NEW);
        $contact->setSource((string) ($data['source'] ?? 'form'));

        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return $this->map($contact);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function list(?string $status, int $page, int $limit): array
    {
        $paginator = $this->contactRequestRepository->paginate($status, $page, $limit);

        $items = [];
        foreach ($paginator as $contact) {
            $items[] = $this->map($contact);
        }

        $total = count($paginator);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) max(1, (int) ceil($total / max(1, $limit))),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function markAsRead(ContactRequest $contact): array
    {
        if ($contact->getStatus() === self::STATUS_NEW) {
            $contact->setStatus(self::STATUS_READ);
            $this->entityManager->flush();
        }

        return $this->map($contact);
    }

    /**
     * @return array<string, mixed>
     */
    public function changeStatus(ContactRequest $contact, string $status): array
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Statut invalide. Valeurs autorisées : %s.',
                implode(', ', self::ALLOWED_STATUSES)
            ));
        }

        $contact->setStatus($status);
        $this->entityManager->flush();

        return $this->map($contact);
    }

    /**
     * @return array<string, mixed>
     * @throws TransportExceptionInterface
     */
    public function reply(ContactRequest $contact, string $reply): array
    {
        $reply = trim($reply);
        if ($reply === '') {
            throw new \InvalidArgumentException('La réponse ne peut pas être vide.');
        }

        $contact->setAdminReply($reply);
        $contact->setRepliedAt(new \DateTimeImmutable());
        $contact->setStatus(self::STATUS_REPLIED);
        $this->entityManager->flush();

        $email = new Email()
            ->from('AltheaSystem@admin.com')
            ->to((string) $contact->getEmail())
            ->subject('Re: '.(string) $contact->getSubject())
            ->text($reply);

        $this->mailer->send($email);

        return $this->map($contact);
    }

    /**
     * @return array<string, mixed>
     */
    public function map(ContactRequest $contact): array
    {
        return [
            'id' => $contact->getId(),
            'email' => $contact->getEmail(),
            'subject' => $contact->getSubject(),
            'message' => $contact->getMessage(),
            'status' => $contact->getStatus(),
            'source' => $contact->getSource(),
            'adminReply' => $contact->getAdminReply(),
            'repliedAt' => $contact->getRepliedAt()?->format(\DateTimeInterface::ATOM),
            'createdAt' => $contact->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $contact->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
