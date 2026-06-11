<?php

namespace App\Service;

use App\Entity\ChatbotConversation;
use App\Entity\ChatbotMessage;
use App\Entity\ContactRequest;
use App\Repository\ChatbotConversationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ChatbotService
{
    private const string STATUS_OPEN = 'open';
    private const string STATUS_ESCALATED = 'escalated';

    private const array QUESTIONS = [
        'update_address' => [
            'question' => 'Comment modifier mon adresse ?',
            'answer' => 'Connectez-vous à votre compte, ouvrez votre profil, puis modifiez vos informations de livraison ou de facturation avant de sauvegarder.',
            'keywords' => ['adresse', 'modifier adresse', 'changer adresse', 'livraison', 'facturation'],
        ],
        'payment_methods' => [
            'question' => 'Quelles sont les méthodes de paiement acceptées ?',
            'answer' => 'Nous acceptons les paiements par carte bancaire via Stripe. Les moyens disponibles peuvent varier selon votre pays et la configuration du paiement.',
            'keywords' => ['paiement', 'payer', 'carte', 'stripe', 'moyen de paiement'],
        ],
        'order_tracking' => [
            'question' => 'Comment suivre ma commande ?',
            'answer' => 'Vous pouvez consulter vos commandes depuis votre espace client. Le statut indique si la commande est en attente, validée, expédiée ou terminée.',
            'keywords' => ['commande', 'suivre', 'statut', 'tracking', 'suivi'],
        ],
        'delivery_delay' => [
            'question' => 'Quels sont les délais de livraison ?',
            'answer' => 'Les délais dépendent de la disponibilité des produits et de l’adresse de livraison. Vérifiez le statut de votre commande ou contactez le support pour un délai précis.',
            'keywords' => ['delai', 'livraison', 'expedition', 'recevoir', 'quand'],
        ],
        'returns_refunds' => [
            'question' => 'Comment demander un retour ou un remboursement ?',
            'answer' => 'Préparez votre numéro de commande et contactez le support avec le motif du retour. L’équipe vérifiera les conditions applicables avant validation.',
            'keywords' => ['retour', 'remboursement', 'rembourser', 'annuler', 'renvoyer'],
        ],
        'invoice' => [
            'question' => 'Où trouver ma facture ?',
            'answer' => 'Vos factures sont accessibles depuis le détail de vos commandes dans votre espace client lorsque la commande est disponible à la facturation.',
            'keywords' => ['facture', 'pdf', 'telecharger facture', 'invoice'],
        ],
        'password_reset' => [
            'question' => 'J’ai oublié mon mot de passe, que faire ?',
            'answer' => 'Utilisez le lien de réinitialisation du mot de passe sur la page de connexion. Un e-mail vous sera envoyé si le compte existe.',
            'keywords' => ['mot de passe', 'password', 'connexion', 'reinitialiser', 'compte'],
        ],
        'technical_support' => [
            'question' => 'J’ai un problème technique sur le site.',
            'answer' => 'Essayez de rafraîchir la page et vérifiez votre connexion. Si le problème continue, demandez un transfert vers le support en indiquant votre e-mail et le contexte.',
            'keywords' => ['bug', 'probleme', 'technique', 'erreur', 'site', 'support'],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ChatbotConversationRepository $conversationRepository,
    ) {
    }

    /**
     * @return array<int, array{key: string, question: string}>
     */
    public function questions(): array
    {
        return array_map(
            fn (string $key, array $item): array => ['key' => $key, 'question' => $item['question']],
            array_keys(self::QUESTIONS),
            self::QUESTIONS,
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function start(array $data = []): array
    {
        $conversation = new ChatbotConversation();
        $this->hydrateVisitorInfo($conversation, $data, false);

        $conversation->addMessage($this->createMessage(
            'bot',
            'Bonjour, je peux répondre aux questions fréquentes ou transmettre votre demande au support. Vous pouvez choisir une question ou écrire votre message.',
            null,
            ['availableQuestions' => $this->questions()],
        ));

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $this->mapConversation($conversation, true);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function reply(array $data): array
    {
        $conversation = $this->findOrCreateConversation($data);
        $this->hydrateVisitorInfo($conversation, $data, false);

        $message = trim((string) ($data['message'] ?? ''));
        $questionKey = isset($data['questionKey']) ? (string) $data['questionKey'] : null;

        if ($message === '' && ($questionKey === null || !isset(self::QUESTIONS[$questionKey]))) {
            throw new \InvalidArgumentException('Le champ "message" ou "questionKey" est requis.');
        }

        $userContent = $message !== '' ? $message : self::QUESTIONS[$questionKey]['question'];
        $conversation->addMessage($this->createMessage('user', $userContent, $questionKey));

        $matchedKey = $questionKey !== null && isset(self::QUESTIONS[$questionKey])
            ? $questionKey
            : $this->matchQuestion($message);

        if ($matchedKey !== null) {
            $conversation->addMessage($this->createMessage(
                'bot',
                self::QUESTIONS[$matchedKey]['answer'],
                $matchedKey,
                ['canEscalate' => true, 'contactFormUrl' => '/contact'],
            ));
        } else {
            $conversation->addMessage($this->createMessage(
                'bot',
                'Je n’ai pas de réponse automatique fiable pour cette demande. Je peux transmettre votre message au support si vous renseignez votre e-mail.',
                null,
                ['requiresEscalation' => true, 'contactFormUrl' => '/contact'],
            ));
        }

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $this->mapConversation($conversation, true);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function escalate(array $data): array
    {
        $conversation = $this->findOrCreateConversation($data);
        $this->hydrateVisitorInfo($conversation, $data, true);

        $message = trim((string) ($data['message'] ?? ''));
        $hasNewMessage = $message !== '';
        if (!$hasNewMessage) {
            $message = $this->lastUserMessage($conversation) ?? 'Demande transmise depuis le chatbot.';
        }

        if ($hasNewMessage || $this->lastUserMessage($conversation) === null) {
            $conversation->addMessage($this->createMessage('user', $message));
        }

        $contact = new ContactRequest();
        $contact->setEmail((string) $conversation->getEmail());
        $contact->setSubject($conversation->getSubject() ?: 'Demande chatbot');
        $contact->setMessage($this->limitText($message, 255));
        $contact->setStatus('new');
        $contact->setSource('chatbot');

        $conversation->setStatus(self::STATUS_ESCALATED);
        $conversation->setContactRequest($contact);
        $conversation->addMessage($this->createMessage(
            'bot',
            'Votre demande a été transmise à notre équipe support. Un membre de l’équipe pourra reprendre le suivi depuis le backoffice.',
            null,
            ['contactRequestId' => null],
        ));

        $this->entityManager->persist($contact);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $this->mapConversation($conversation, true);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function list(?string $status, int $page, int $limit): array
    {
        $paginator = $this->conversationRepository->paginate($status, $page, $limit);
        $items = [];

        foreach ($paginator as $conversation) {
            $items[] = $this->mapConversation($conversation, false);
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
    public function show(ChatbotConversation $conversation): array
    {
        return $this->mapConversation($conversation, true);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function findOrCreateConversation(array $data): ChatbotConversation
    {
        $conversationId = isset($data['conversationId']) ? (int) $data['conversationId'] : 0;
        if ($conversationId > 0) {
            $conversation = $this->conversationRepository->find($conversationId);
            if (!$conversation instanceof ChatbotConversation) {
                throw new \InvalidArgumentException('Conversation chatbot introuvable.');
            }

            return $conversation;
        }

        $conversation = new ChatbotConversation();
        $this->entityManager->persist($conversation);

        return $conversation;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrateVisitorInfo(ChatbotConversation $conversation, array $data, bool $requireEmail): void
    {
        $email = trim((string) ($data['email'] ?? $conversation->getEmail() ?? ''));
        $subject = trim((string) ($data['subject'] ?? $conversation->getSubject() ?? ''));

        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Adresse email invalide.');
            }
            $conversation->setEmail($email);
        }

        if ($requireEmail && $conversation->getEmail() === null) {
            throw new \InvalidArgumentException('Adresse email requise pour transférer la demande au support.');
        }

        if ($subject !== '') {
            $conversation->setSubject($this->limitText($subject, 255));
        }
    }

    private function matchQuestion(string $message): ?string
    {
        $normalizedMessage = $this->normalize($message);

        foreach (self::QUESTIONS as $key => $item) {
            foreach ($item['keywords'] as $keyword) {
                if (str_contains($normalizedMessage, $this->normalize($keyword))) {
                    return $key;
                }
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $value = strtolower($value);
        $withoutAccents = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $withoutAccents !== false ? $withoutAccents : $value;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    private function createMessage(string $sender, string $content, ?string $questionKey = null, ?array $metadata = null): ChatbotMessage
    {
        $message = new ChatbotMessage($sender, $content);
        $message->setQuestionKey($questionKey);
        $message->setMetadata($metadata);

        return $message;
    }

    private function lastUserMessage(ChatbotConversation $conversation): ?string
    {
        $messages = $conversation->getMessages()->toArray();
        for ($index = count($messages) - 1; $index >= 0; --$index) {
            if ($messages[$index]->getSender() === 'user') {
                return $messages[$index]->getContent();
            }
        }

        return null;
    }

    private function limitText(string $value, int $maxLength): string
    {
        return substr(trim($value), 0, $maxLength);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapConversation(ChatbotConversation $conversation, bool $includeMessages): array
    {
        $messages = $conversation->getMessages()->toArray();
        $lastMessage = count($messages) > 0 ? $messages[count($messages) - 1] : null;

        $data = [
            'id' => $conversation->getId(),
            'email' => $conversation->getEmail(),
            'subject' => $conversation->getSubject(),
            'status' => $conversation->getStatus(),
            'contactRequestId' => $conversation->getContactRequest()?->getId(),
            'lastMessage' => $lastMessage instanceof ChatbotMessage ? $this->mapMessage($lastMessage) : null,
            'createdAt' => $conversation->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $conversation->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];

        if ($includeMessages) {
            $data['messages'] = array_map(fn (ChatbotMessage $message): array => $this->mapMessage($message), $messages);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMessage(ChatbotMessage $message): array
    {
        return [
            'id' => $message->getId(),
            'sender' => $message->getSender(),
            'content' => $message->getContent(),
            'questionKey' => $message->getQuestionKey(),
            'metadata' => $message->getMetadata(),
            'createdAt' => $message->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
