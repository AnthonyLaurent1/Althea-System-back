# Documentation technique — Althea System (Backend)

> Document destiné aux développeurs rejoignant le projet. Il présente la **stack technique**, **les choix d'architecture et leurs raisons**, **le modèle de données** et **les routes API disponibles**.
>
> Pour le détail exhaustif des payloads de chaque route, voir aussi :
> - [`API_ROUTES.md`](./API_ROUTES.md) — référence complète des endpoints
> - [`AUTH_FRONT.md`](./AUTH_FRONT.md) — intégration front de l'authentification
> - [`ORDER_FRONT.md`](./ORDER_FRONT.md) — intégration front du panier / commande

---

## 1. Vue d'ensemble

Althea System Back est l'**API REST** d'une plateforme e-commerce de matériel médical. Elle gère :

- un **catalogue** multilingue (catégories, produits, remises) — `fr`, `en`, `ru` ;
- l'**authentification** des clients (inscription, vérification d'email, connexion JWT, réinitialisation de mot de passe) ;
- un **panier / tunnel de commande** (invité ou connecté) avec **paiement Stripe** ;
- la **génération de factures PDF** ;
- un **formulaire de contact** et un **chatbot FAQ** multilingue.

C'est un backend **stateless** : il expose une API JSON consommée par un frontend séparé (SPA). Aucune page n'est rendue côté serveur, à l'exception des **templates PDF** et **emails** (Twig).

---

## 2. Stack technique

| Domaine | Technologie | Version |
|---|---|---|
| Langage | PHP | **8.4** |
| Framework | Symfony | **8.0** |
| API | API Platform | **4.3** |
| ORM | Doctrine ORM | **3.6** (+ DoctrineBundle 3, Migrations 4) |
| Base de données | MySQL | **8.0** |
| Authentification | LexikJWTAuthenticationBundle | **3.2** |
| CORS | NelmioCorsBundle | **2.6** |
| Paiement | stripe/stripe-php | **20.x** |
| Génération PDF | dompdf/dompdf | **3.1** |
| Emails | symfony/mailer | **8.0** |
| Templating (PDF/emails) | Twig | **8.0** |
| Conteneurisation | Docker (PHP-FPM + Nginx + MySQL + MailHog) | — |

### Pourquoi ces choix ?

- **PHP 8.4 / Symfony 8** — version LTS-moderne, typage strict, attributs PHP natifs (utilisés pour le mapping Doctrine et l'injection). On reste sur l'écosystème Symfony standard (pas de `symfony/symfony` monolithique : voir `conflict` dans `composer.json`), ce qui allège les dépendances.
- **API Platform** — fournit gratuitement la base d'une API REST normalisée, la négociation de contenu, la documentation OpenAPI/Swagger et les conventions de sérialisation. Il sert ici de socle pour exposer l'API sous le préfixe `/api`.
- **Doctrine ORM (mapping par attributs)** — mapping objet-relationnel standard de Symfony. Le mapping se fait **par attributs PHP** directement dans les entités (`src/Entity`), pas en XML/YAML — plus lisible et colocalisé avec le code.
- **JWT (Lexik)** — authentification **stateless** adaptée à une API consommée par une SPA / mobile : pas de session côté serveur, le token (TTL **24 h**) est porté par le header `Authorization: Bearer`. Évite la gestion de sessions partagées en cas de scaling horizontal.
- **Stripe** — délégation complète du paiement (PCI-DSS) via *Checkout Session* + *webhook*. Le backend ne manipule jamais de données carte.
- **Dompdf** — génération de factures PDF à partir de templates Twig HTML, sans dépendance système lourde (pas de binaire externe type wkhtmltopdf).
- **NelmioCors** — la SPA étant servie depuis une autre origine, le CORS est indispensable. Configuré par variable d'environnement (`CORS_ALLOW_ORIGIN`).
- **MailHog (dev)** — capture les emails sortants en développement (vérification d'email, reset de mot de passe) sans envoyer de vrais messages.

---

## 3. Architecture & organisation du code

```
src/
├── Command/          # Commandes CLI (seed des données et traductions)
├── Dto/              # Objets de transfert (entrée/sortie d'API découplée des entités)
├── Entity/           # Entités Doctrine (mapping par attributs)
├── EventListener/    # Abonnés aux événements Symfony (fusion panier au login)
├── Repository/       # Requêtes Doctrine personnalisées
├── Service/          # Logique métier (Stripe, PDF, emails, chatbot…)
└── Kernel.php
config/
├── packages/         # Config des bundles (security, doctrine, jwt, cors, mailer…)
├── routes/           # Chargement des routes (api_platform, security)
└── jwt/              # Clés publique/privée JWT (générées, non versionnées)
templates/            # base.html.twig, pdf.html.twig (facture)
migrations/           # Migrations Doctrine versionnées
docker/               # Configs Nginx + PHP-FPM
```

### Principes structurants

- **Séparation Entité / DTO.** Les **entités** (`src/Entity`) reflètent le schéma de base. Les **DTO** (`src/Dto`) définissent la forme des données échangées avec le client (`CategoryDto`, `ProductDto`, `AddItemDto`, `UpdateItemQuantityDto`…). Cela évite d'exposer directement le modèle interne et de fuiter des champs sensibles.
- **Logique métier dans les Services.** Toute la logique non triviale est isolée dans `src/Service` :
  - `StripeService` — création des *Checkout Sessions* Stripe.
  - `InvoicePdfService` — rendu de la facture PDF (Twig + Dompdf).
  - `EmailVerifier`, `EmailTemplateVerification`, `EmailTemplatePasswordReset` — envoi des emails transactionnels.
  - `PasswordReset` — gestion des tokens de réinitialisation.
  - `FaqChatbotService` — moteur de réponses du chatbot FAQ (matching par mots-clés, multilingue, fallback vers le formulaire de contact).
- **Internationalisation par tables de traduction.** Les entités `Category` et `Product` portent la version **FR par défaut**. Les variantes `en` et `ru` vivent dans `CategoryTranslation` / `ProductTranslation` (relation `OneToMany`). La langue est sélectionnée par le query param `?locale=`, avec **fallback automatique sur le FR** si la traduction manque.
- **Fusion du panier invité → connecté.** `LoginSuccessListener` écoute `LoginSuccessEvent` : au login, le panier stocké en **session** (invité) est fusionné dans la commande au statut `cart` de l'utilisateur, puis la session est vidée.

> **Note pour les nouveaux développeurs :** la couche **contrôleur HTTP** n'est pas présente dans cette branche du dépôt (`src/Controller` absent). Les routes ci-dessous sont la **référence contractuelle** de l'API (issue de `API_ROUTES.md` et de la config de sécurité) ; la logique métier qu'elles appellent réside dans `src/Service`. Vérifier la présence des contrôleurs avant un déploiement.

---

## 4. Modèle de données

| Entité | Rôle | Champs notables |
|---|---|---|
| **User** | Client | `email` (unique), `roles`, `hashedPassword`, identité + adresse (`firstName`, `lastName`, `phone`, `city`, `country`, `address`, `postalCode`, `company`, `siret`), `isVerified`, `confirmationToken`, `resetPasswordToken(+ExpiresAt)` |
| **Category** | Catégorie produit (FR) | `title`, `pictureUrl` → `products`, `translations` |
| **CategoryTranslation** | Traduction catégorie | `locale`, `title` |
| **Product** | Produit (FR) | `title`, `description`, `price`, `pictureUrl`, `inStock`, `isPublished`, `isPortable`, `isOneTimeUse`, `powerSupplyType`, `medicalDomain` → `category`, `discounts`, `translations` |
| **ProductTranslation** | Traduction produit | `locale`, `title`, `description`, `powerSupplyType`, `medicalDomain` |
| **Discount** | Remise temporelle sur produit | `percentage`, `startDate`, `endDate` |
| **Orders** | Commande / panier | `user`, `paymentDate`, `totalPrice`, `status` (`cart` → `Paye`), `items` |
| **Items** | Ligne de commande | `product`, `quantity`, `price`, `orders` |
| **ContactRequest** | Demande de contact | `email`, `subject`, `message`, `status` (`new`), `source` (`form`), `adminReply`, `repliedAt`, `createdAt`, `updatedAt` |

Relations clés : `User 1—N Orders 1—N Items N—1 Product N—1 Category` ; `Product 1—N Discount` ; traductions en `1—N` sur `Category` et `Product`.

---

## 5. Authentification & sécurité

- **Connexion** : `POST /api/auth/login_check` (firewall `json_login`) renvoie un **JWT** signé (clés RSA dans `config/jwt`). TTL 24 h.
- **Accès API** : firewall `^/api` **stateless**, validation JWT (`jwt: ~`). Le header `Authorization: Bearer <token>` est requis pour les routes protégées.
- **Exception publique** : `^/api/order/stripe/webhook` est en `PUBLIC_ACCESS` (appelé par Stripe, authentifié par signature `Stripe-Signature`).
- **Mots de passe** : hashage `auto` (bcrypt/argon selon plateforme), jamais stockés ni renvoyés en clair.
- **Vérification d'email** : un token de confirmation est envoyé à l'inscription ; le compte n'est `isVerified` qu'après clic sur le lien.
- **CORS** : origines autorisées via `CORS_ALLOW_ORIGIN` (regex), méthodes `GET/POST/PUT/PATCH/DELETE/OPTIONS`, headers `Content-Type`/`Authorization`.

---

## 6. Routes API

Base métier : `/api` · Auth : `Authorization: Bearer <token>` · Langue : query param `?locale=fr|en|ru` (défaut `fr`).

### 6.1 Authentification

| Méthode | Route | Auth | Description |
|---|---|---|---|
| POST | `/api/auth/register` | publique | Crée un compte + envoie l'email de vérification. Obligatoires : `email`, `password`, `firstName`, `lastName`. |
| POST | `/api/auth/login_check` | publique | Authentifie et renvoie un JWT (`{email, password}`). |
| GET | `/api/auth/verify-email/{token}` | publique | Vérifie l'email via le token, renvoie un JWT. |
| POST | `/api/auth/forgot-password` | publique | Demande un email de réinitialisation (`{email}`). Répond `200` même si l'email n'existe pas (anti-énumération). |
| POST | `/api/auth/reset-password/{token}` | publique | Réinitialise le mot de passe (`{password}`) si le token est valide/non expiré. |
| POST | `/api/auth/logout` | — | Message de déconnexion (le token est purgé côté client en JWT stateless). |

### 6.2 Catégories

| Méthode | Route | Auth | Description |
|---|---|---|---|
| GET | `/api/categories` | publique | Liste des catégories + leurs produits (traduits selon `locale`). |
| GET | `/api/categories/{id}` | publique | Une catégorie + ses produits. |
| GET | `/api/categories/{id}/products` | publique | Produits d'une catégorie (traduits). |
| POST | `/api/categories` | admin | Crée une catégorie FR + traductions `en`/`ru` optionnelles. |
| PUT/PATCH | `/api/categories/{id}` | admin | Met à jour la catégorie ; champs de base = FR, `translations.en`/`.ru` = traductions. |

### 6.3 Produits

| Méthode | Route | Auth | Description |
|---|---|---|---|
| GET | `/api/products` | publique | Liste des produits (traduits selon `locale`). |
| GET | `/api/products/{id}` | publique | Un produit. |
| GET | `/api/products/{id}/similar` | publique | Jusqu'à 6 produits similaires (par `medicalDomain`, sinon par catégorie). |
| GET | `/api/products/search?q=…` | publique | Recherche par titre (min. 2 caractères ; tableau vide sinon). |
| POST | `/api/products` | admin | Crée un produit FR + traductions ; `categoryId` doit exister. |
| PUT/PATCH | `/api/products/{id}` | admin | Met à jour le produit (FR + traductions). |

### 6.4 Panier & commandes

| Méthode | Route | Auth | Description |
|---|---|---|---|
| POST | `/api/order/add-item` | publique* | Ajoute un produit au panier (`{productId, quantity}`). Connecté → commande `cart` ; invité → session. Stock vérifié. |
| GET | `/api/order/my-order` | publique* | Renvoie le panier courant (session pour l'invité, commande `cart` pour le connecté). |
| PATCH | `/api/order/update-items` | `ROLE_USER` | Met à jour les quantités (`{items:[{itemId, quantity}]}`). Quantité min. 1, stock vérifié. |
| DELETE | `/api/order/remove-item/{id}` | `ROLE_USER` | Retire un item du panier. |
| POST | `/api/order/checkout` | `ROLE_USER` | Crée une *Checkout Session* Stripe (panier non vide, stock suffisant). Renvoie `url` de redirection. |
| POST | `/api/order/stripe/webhook` | **publique** | Reçoit les events Stripe (signature requise). Sur `checkout.session.completed` : passe la commande à `Paye` et décrémente le stock. |
| GET | `/api/order/success` | publique | Message de succès post-paiement (`?session_id=…`). |

\* *Accessible aux invités (panier en session) comme aux connectés.*

### 6.5 Factures

| Méthode | Route | Auth | Description |
|---|---|---|---|
| GET | `/api/invoice/{id}` | `ROLE_USER` | Génère/retourne la facture PDF (`application/pdf`) d'une commande **payée** appartenant à l'utilisateur. Erreurs `401/403/404/400` selon le cas. |

### 6.6 Contact & chatbot

Fonctionnalités présentes côté métier (`ContactRequest` + `FaqChatbotService`) :

- **Formulaire de contact** — persiste une `ContactRequest` (`email`, `subject`, `message`, `status=new`, `source=form`).
- **Chatbot FAQ** — `FaqChatbotService::getResponse(message, locale)` : matching par mots-clés (FR/EN/RU), renvoie `answer`, `matchedIntent`, `category`, `contactSuggested` et un éventuel `redirectTo: /contact`. Fallback vers le formulaire de contact si aucune intention ne correspond.

> Les chemins HTTP exacts de ces deux fonctionnalités suivent la convention `/api/...` ; se référer au contrôleur correspondant pour le détail.

---

## 7. Mise en route

### Avec Docker (recommandé)

```bash
docker compose up -d --build
# API     → http://localhost:8080
# MailHog → http://localhost:8025
# MySQL   → localhost:3306
```

Le `docker-compose.yml` lance : **app** (PHP-FPM 8.4), **nginx** (8080), **database** (MySQL 8), **mailhog** (8025).

### En local

```bash
composer install
cp .env.dist .env          # puis configurer DATABASE_URL, MAILER_DSN, JWT_PASSPHRASE, STRIPE_SECRET_KEY, CORS_ALLOW_ORIGIN
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate
symfony serve
```

### Clés JWT

```bash
php bin/console lexik:jwt:generate-keypair   # génère config/jwt/{private,public}.pem
```

### Jeu de données de démonstration

```bash
php bin/console app:seed-data              # catégories + produits (FR)
php bin/console app:seed-translations-en   # traductions EN
php bin/console app:seed-translations-ru   # traductions RU
```

---

## 8. Variables d'environnement

| Variable | Rôle |
|---|---|
| `APP_ENV` / `APP_SECRET` | Environnement Symfony et secret applicatif |
| `DATABASE_URL` | DSN MySQL (Doctrine) |
| `MAILER_DSN` | Transport mail (MailHog en dev, SMTP en prod) |
| `JWT_SECRET_KEY` / `JWT_PUBLIC_KEY` / `JWT_PASSPHRASE` | Clés et passphrase JWT |
| `STRIPE_SECRET_KEY` | Clé secrète Stripe (paiement / webhook) |
| `CORS_ALLOW_ORIGIN` | Regex des origines autorisées (CORS) |

> ⚠️ Les secrets (JWT, Stripe, BDD) présents dans `.env.dist` et `docker-compose.yml` sont des valeurs de **développement**. Ne jamais les réutiliser en production — utiliser le *secrets vault* Symfony ou des variables d'environnement injectées.
