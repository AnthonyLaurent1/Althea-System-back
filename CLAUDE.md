# CLAUDE.md — Backend PHP Symfony (Althea Systems)

Ce fichier configure le comportement de Claude pour ce projet.
Il s'applique à toutes les interventions sur le code backend.

---

## Stack technique

- **Langage** : PHP 8.2+
- **Framework** : Symfony 7.x
- **ORM** : Doctrine ORM
- **Authentification** : LexikJWTAuthenticationBundle (access token + refresh token)
- **Validation** : Symfony Validator (annotations/attributs PHP 8)
- **Sérialisation** : Symfony Serializer ou JMS Serializer
- **Mails** : Symfony Mailer + Twig (templates)
- **Paiement** : Stripe PHP SDK
- **Documentation API** : NelmioApiDocBundle (OpenAPI/Swagger)
- **Tests** : PHPUnit + ApiTestCase (Symfony)
- **Qualité** : PHP-CS-Fixer, PHPStan (niveau 8 minimum)
- **Base de données** : PostgreSQL (driver Doctrine DBAL)

---

## Principes fondamentaux

### KISS — Keep It Simple, Stupid

> Toujours choisir la solution la plus simple qui résout le problème.

- Écrire du code qu'un développeur junior peut lire sans documentation.
- Pas d'abstraction prématurée. Une interface ou un pattern n'est justifié que s'il y a **au moins 2 implémentations concrètes** ou un **besoin démontré** de testabilité.
- Éviter les chaînes de méthodes trop longues ou les pipelines illisibles.
- Un Controller ne fait **que** recevoir la requête, déléguer au Service, et retourner la réponse. Aucune logique métier dans un Controller.
- Préférer un `if` explicite à un opérateur ternaire imbriqué.
- Les noms de classes, méthodes et variables doivent être **auto-documentants** : `getUserById()` vaut mieux que `fetch()`.

### SOLID

#### S — Single Responsibility Principle
- Chaque classe a **une seule raison de changer**.
- `UserService` gère la logique métier utilisateur. `UserRepository` gère les requêtes BDD. `UserController` gère le HTTP. Ces trois classes ne se substituent pas.
- Un Service qui grossit doit être découpé : `PasswordResetService`, `EmailConfirmationService`, pas tout dans `AuthService`.

#### O — Open/Closed Principle
- Les classes sont **ouvertes à l'extension, fermées à la modification**.
- Utiliser les **interfaces** pour les dépendances injectées, pas les classes concrètes.
- Pour ajouter un nouveau comportement (ex : nouvelle méthode de paiement), créer une nouvelle implémentation de l'interface `PaymentGatewayInterface`, sans modifier le code existant.

#### L — Liskov Substitution Principle
- Toute classe enfant ou implémentation d'interface doit être **substituable** sans changer le comportement attendu.
- Ne pas surcharger une méthode pour y lever une exception non prévue dans le contrat de l'interface.
- Éviter l'héritage profond (> 2 niveaux). Privilégier la composition.

#### I — Interface Segregation Principle
- Créer des **interfaces petites et cohérentes**, pas des interfaces fourre-tout.
- `PaymentGatewayInterface` expose `charge()`, `refund()`, `getPaymentMethod()`. Pas `sendEmail()`.
- Si une implémentation n'a pas besoin d'une méthode de l'interface, c'est que l'interface est trop large : la découper.

#### D — Dependency Inversion Principle
- Les modules de haut niveau (Services) **ne dépendent pas** des modules de bas niveau (implémentations concrètes de Repository, Gateway).
- Tout est injecté via le **constructeur** (constructor injection). Pas d'instanciation directe avec `new` dans un Service.
- Déclarer les dépendances via leurs **interfaces** dans le constructeur. Symfony DI résout l'implémentation concrète.

```php
// ✅ Correct
class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly MailerInterface $mailer,
    ) {}
}

// ❌ Interdit
class OrderService
{
    public function __construct()
    {
        $this->orderRepository = new OrderRepository();
        $this->stripe = new StripeGateway();
    }
}
```

### DRY — Don't Repeat Yourself

> Chaque logique doit avoir **une seule représentation canonique** dans le code.

- Si un bloc de code apparaît deux fois, l'extraire dans une méthode privée ou un Service dédié.
- Les règles de validation sont définies **une seule fois** sur les DTOs via les attributs PHP 8 (`#[Assert\NotBlank]`, etc.) — jamais dupliquées dans le Controller et le Service.
- Les requêtes Doctrine récurrentes appartiennent au Repository correspondant — jamais copiées dans un Service ou un Controller.
- Les templates Twig d'emails utilisent `{% extends %}` et `{% block %}` — pas de copier-coller de layout entre templates.
- Les constantes métier (durées de token, statuts de commande) sont déclarées dans des classes `enum` PHP 8.1+ ou des classes de constantes. Pas de magic strings dispersées.

---

## Architecture des dossiers

```
src/
├── Controller/          # HTTP uniquement. Pas de logique métier.
│   ├── Api/             # Endpoints API REST (préfixe /api)
│   └── Admin/           # Endpoints backoffice (préfixe /admin)
├── Service/             # Logique métier. Un service = une responsabilité.
├── Repository/          # Requêtes Doctrine. Étend ServiceEntityRepository.
├── Entity/              # Entités Doctrine. Pas de logique métier ici.
├── DTO/                 # Data Transfer Objects (entrée/sortie API). Avec contraintes de validation.
├── Enum/                # Enums PHP 8.1 (statuts, rôles, etc.).
├── Event/               # Événements Symfony (pour découpler les side-effects).
├── EventListener/       # Listeners d'événements.
├── Exception/           # Exceptions métier personnalisées.
├── Interface/           # Interfaces des Services et Gateways.
├── Gateway/             # Implémentations des services tiers (Stripe, Mailer...).
├── Security/            # Voters, Authenticators.
└── Validator/           # Contraintes de validation personnalisées (si besoin).
```

---

## Conventions de code

### Controllers

- Suffixe `Controller`. Annoter avec `#[Route]` sur la classe (préfixe) et sur chaque méthode.
- Retourner uniquement `JsonResponse` pour les endpoints API.
- Injecter les DTOs via `#[MapRequestPayload]` (Symfony 6.3+) — pas de `$request->getContent()` manuel.
- Un Controller ne contient **jamais** de `new`, de requête Doctrine, ni de logique conditionnelle métier.
- Taille max recommandée : **30 lignes** par méthode de Controller.

```php
#[Route('/api/products', name: 'api_products_')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getById($id);
        return $this->json($product, Response::HTTP_OK, [], ['groups' => ['product:read']]);
    }
}
```

### Services

- Suffixe `Service`. Classe `final` par défaut (sauf si héritage justifié).
- Toutes les dépendances injectées dans le constructeur avec `private readonly`.
- Les méthodes publiques sont le contrat métier. Les méthodes privées sont des détails d'implémentation.
- Lever des exceptions métier explicites (`ProductNotFoundException`, `InsufficientStockException`) plutôt que retourner `null` ou `false`.
- Ne pas retourner des tableaux bruts depuis un Service : retourner des objets (Entity, DTO) ou des collections typées.

### Repositories

- Étendre `ServiceEntityRepository`. Suffixe `Repository`.
- Déclarer une interface `ProductRepositoryInterface` — le Service dépend de l'interface.
- Nommer les méthodes de façon expressive : `findAvailableByCategory()`, pas `findBy(['available' => true, 'categoryId' => $id])` éparpillé partout.
- Pas de SQL brut sauf justification de performance documentée. Utiliser le QueryBuilder Doctrine.

### DTOs

- Un DTO par opération : `CreateProductDTO`, `UpdateProductDTO`, `ProductResponseDTO`.
- Les DTOs d'entrée portent les contraintes de validation (`#[Assert\*]`).
- Les DTOs de sortie portent les groupes de sérialisation (`#[Groups(['product:read'])]`).
- Pas d'Entity Doctrine exposée directement en réponse API (risque de sérialisation circulaire et de fuite de données internes).

### Entités Doctrine

- Attributs PHP 8 (`#[ORM\Entity]`, `#[ORM\Column]`, etc.) — pas d'annotations XML ou YAML.
- Pas de logique métier dans les entités. Getters/setters simples ou propriétés `public readonly` pour les Value Objects.
- Les relations sont déclarées explicitement avec `cascade`, `fetch` et `orphanRemoval` justifiés.

### Enums

Utiliser les `enum` PHP 8.1 pour tous les statuts et types fixes :

```php
enum OrderStatus: string
{
    case Pending  = 'pending';
    case Paid     = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
```

### Gestion des erreurs

- Créer un `ExceptionSubscriber` (ou `KernelExceptionEvent` listener) global qui intercepte les exceptions métier et les formate en `JsonResponse` standardisée.
- Format de réponse d'erreur unifié :
```json
{
  "error": "PRODUCT_NOT_FOUND",
  "message": "Le produit avec l'id 42 est introuvable.",
  "statusCode": 404
}
```
- Ne jamais laisser une stacktrace ou un message d'exception interne fuiter en production.

### Sécurité

- Utiliser les **Voters** Symfony pour toute logique d'autorisation. Pas de `if ($user->getRole() === 'ROLE_ADMIN')` dans les Controllers.
- Toujours valider les DTOs avant tout traitement (`#[MapRequestPayload]` le fait automatiquement).
- Paramétrer toutes les requêtes Doctrine (pas de concaténation de string dans le DQL/SQL).
- Les tokens (confirmation email, reset password) sont des UUID v4 générés via `Symfony\Component\Uid\Uuid`, stockés hashés en BDD, valides 24h.
- Les mots de passe sont hashés via `UserPasswordHasherInterface` (argon2id ou bcrypt selon config).

---

## Ce que Claude doit faire

- **Toujours** proposer une interface avant une implémentation concrète quand c'est pertinent.
- **Toujours** placer la logique dans le bon layer (Controller → Service → Repository).
- **Toujours** utiliser les attributs PHP 8+ (pas d'annotations doctrine/symfony legacy).
- **Toujours** injecter via le constructeur avec `private readonly`.
- **Signaler** explicitement quand une demande violerait KISS, SOLID ou DRY, et proposer une alternative.
- **Générer** les tests PHPUnit correspondants lorsqu'un Service ou une logique métier est créée.
- **Documenter** les endpoints avec les attributs NelmioApiDoc si une route API est créée.
- **Toujours** ajouter les endpoints qu'il crée dans le fichier Documentation/API_ROUTES.md
- **Garder en mémoire** ses modifications, en modifiant la todo du fichier .claude/advancement.md

## Ce que Claude ne doit pas faire

- Écrire de la logique métier dans un Controller.
- Écrire une requête Doctrine dans un Controller ou un Service.
- Retourner une entité Doctrine directement en réponse JSON.
- Créer des classes qui font plus d'une chose.
- Utiliser `new ClassName()` à l'intérieur d'un Service (sauf Value Objects simples).
- Dupliquer des règles de validation entre plusieurs couches.
- Utiliser des magic strings là où un `enum` ou une constante est possible.
- Générer du code sans gestion d'erreur.

---

## Commandes utiles (rappel)

```bash
# Créer une entité
php bin/console make:entity

# Créer un Controller API
php bin/console make:controller

# Générer une migration
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Lancer les tests
php bin/phpunit

# Vérifier le style
vendor/bin/php-cs-fixer fix --dry-run

# Analyse statique
vendor/bin/phpstan analyse src --level=8

# Vider le cache
php bin/console cache:clear
```
