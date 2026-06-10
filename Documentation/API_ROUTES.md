# Routes API - Althea System Back

Ce document liste les routes API exposees par le projet `althea-system-back`, leur role, et les donnees attendues quand un body JSON est necessaire.

## Notes generales

- Base des routes metier: `/api`
- Authentification: JWT via `Authorization: Bearer <token>`
- Endpoint de login JWT: `POST /api/auth/login_check`
- Webhook Stripe public: `POST /api/order/stripe/webhook`
- Certaines routes techniques d'API Platform existent aussi pour la doc et les erreurs

## Gestion des langues

Les categories et produits peuvent maintenant etre lus en :

- `fr`
- `en`
- `ru`

Regles:

- la table principale `category` et `product` contient la version FR par defaut
- les tables de traduction contiennent les variantes `en` et `ru`
- la langue se choisit avec le query param `locale`
- si une traduction n'existe pas, l'API retourne la valeur FR

Exemples:

- `GET /api/categories?locale=fr`
- `GET /api/categories?locale=en`
- `GET /api/categories?locale=ru`
- `GET /api/products?locale=en`
- `GET /api/products/1?locale=ru`

## Authentification

### POST `/api/auth/register`

Creer un compte utilisateur puis envoyer un email de verification.

Body JSON:

```json
{
  "email": "user@example.com",
  "password": "secret",
  "firstName": "Jean",
  "lastName": "Dupont",
  "phone": "0102030405",
  "city": "Paris",
  "country": "France",
  "address": "1 rue Exemple",
  "additionalAddress": "Bat B",
  "postalCode": "75000",
  "company": "Ma societe",
  "siret": "12345678900011"
}
```

Champs obligatoires:

- `email`
- `password`
- `firstName`
- `lastName`

Champs optionnels:

- `phone`
- `city`
- `country`
- `address`
- `additionalAddress`
- `postalCode`
- `company`
- `siret`

Reponse:

- `201` avec un message et l'utilisateur cree
- `400` si des champs obligatoires sont manquants

### POST `/api/auth/login_check`

Authentifier un utilisateur et retourner un JWT.

Body JSON attendu:

```json
{
  "email": "user@example.com",
  "password": "secret"
}
```

Reponse attendue:

- succes: token JWT retourne par LexikJWTAuthenticationBundle
- echec: erreur d'authentification

### GET `/api/auth/verify-email/{token}`

Verifier l'email via le token recu par email, puis retourner un JWT.

Parametre:

- `token`: token de confirmation

Reponse:

- `200` avec `message` et `token`
- `400` si le token est invalide

### POST `/api/auth/forgot-password`

Demander un email de reinitialisation du mot de passe.

Body JSON:

```json
{
  "email": "user@example.com"
}
```

Champ obligatoire:

- `email`

Reponse:

- `200` meme si l'email n'existe pas, pour eviter de reveler les comptes existants
- `400` si `email` est absent

### POST `/api/auth/reset-password/{token}`

Reinitialiser le mot de passe avec un token valide.

Parametre:

- `token`: token de reinitialisation

Body JSON:

```json
{
  "password": "nouveau-mot-de-passe"
}
```

Champ obligatoire:

- `password`

Reponse:

- `200` si le mot de passe est modifie
- `400` si le token est invalide/expire ou si `password` manque

### POST `/api/auth/logout`

Retourne simplement un message de deconnexion. En pratique, avec JWT stateless, la suppression du token se gere cote client.

## Categories

### GET `/api/categories`

Retourner toutes les categories avec leurs produits.

Query params:

- `locale` optionnel: `fr`, `en`, `ru`

Comportement:

- traduit `category.title`
- traduit aussi les produits inclus dans `products`
- si une traduction manque, fallback sur la version FR

Reponse:

- tableau de categories
- chaque categorie contient `id`, `title`, `pictureUrl`, `products`

### GET `/api/categories/{id}`

Retourner une categorie par identifiant avec ses produits.

Parametre:

- `id`: identifiant de la categorie

Query params:

- `locale` optionnel: `fr`, `en`, `ru`

Comportement:

- traduit la categorie et ses produits dans la langue demandee
- fallback FR si la traduction n'existe pas

### POST `/api/categories`

Creer une categorie en FR, avec possibilite d'ajouter les traductions `en` et `ru` dans la meme requete.

Body JSON minimal:

```json
{
  "title": "Sterilisation",
  "pictureUrl": "https://example.com/cat.jpg"
}
```

Body JSON complet avec traductions:

```json
{
  "title": "Sterilisation",
  "pictureUrl": "https://example.com/cat.jpg",
  "translations": {
    "en": {
      "title": "Sterilization"
    },
    "ru": {
      "title": "Стерилизация"
    }
  }
}
```

Champs utilises:

- `title`
- `pictureUrl`
- `translations.en.title`
- `translations.ru.title`

### PUT|PATCH `/api/categories/{id}`

Modifier une categorie existante.

Parametre:

- `id`: identifiant de la categorie

Body JSON FR seulement:

```json
{
  "title": "Nouveau titre",
  "pictureUrl": "https://example.com/new.jpg"
}
```

Body JSON pour modifier seulement l'anglais:

```json
{
  "translations": {
    "en": {
      "title": "Updated title"
    }
  }
}
```

Body JSON pour modifier seulement le russe:

```json
{
  "translations": {
    "ru": {
      "title": "Обновленное название"
    }
  }
}
```

Comportement:

- les champs de base modifient la version FR
- `translations.en` modifie ou cree la traduction anglaise
- `translations.ru` modifie ou cree la traduction russe
- si `title` n'est pas envoye, le titre FR ne change pas

### GET `/api/categories/{id}/products`

Retourner les produits d'une categorie.

Parametre:

- `id`: identifiant de la categorie

Query params:

- `locale` optionnel: `fr`, `en`, `ru`

Comportement:

- traduit les champs produits dans la langue demandee
- traduit aussi `product.category.title`

## Produits

### GET `/api/products`

Retourner tous les produits.

Query params:

- `locale` optionnel: `fr`, `en`, `ru`

Comportement:

- traduit `title`
- traduit `description`
- traduit `powerSupplyType`
- traduit `medicalDomain`
- traduit aussi `category.title`
- fallback FR si une traduction n'existe pas

Chaque produit retourne notamment:

- `id`
- `title`
- `description`
- `price`
- `pictureUrl`
- `inStock`
- `isPublished`
- `isPortable`
- `isOneTimeUse`
- `powerSupplyType`
- `medicalDomain`
- `category`
- `discounts`

### GET `/api/products/{id}`

Retourner un produit par identifiant.

Parametre:

- `id`: identifiant du produit

Query params:

- `locale` optionnel: `fr`, `en`, `ru`

### POST `/api/products`

Creer un produit en FR, avec possibilite d'ajouter les traductions `en` et `ru` dans la meme requete.

Body JSON:

```json
{
  "title": "Moniteur patient",
  "description": "Description FR",
  "price": "199.99",
  "pictureUrl": "https://example.com/product.jpg",
  "inStock": 10,
  "isPublished": true,
  "isPortable": false,
  "isOneTimeUse": false,
  "powerSupplyType": "Batterie/Secteur",
  "medicalDomain": "Diagnostic",
  "categoryId": 1,
  "translations": {
    "en": {
      "title": "Patient Monitor",
      "description": "English description",
      "powerSupplyType": "Battery / Mains",
      "medicalDomain": "Diagnostics"
    },
    "ru": {
      "title": "Монитор пациента",
      "description": "Описание на русском",
      "powerSupplyType": "Аккумулятор / Сеть",
      "medicalDomain": "Диагностика"
    }
  }
}
```

Champs utilises:

- `title`
- `description`
- `price`
- `pictureUrl`
- `inStock`
- `isPublished`
- `isPortable`
- `isOneTimeUse`
- `powerSupplyType`
- `medicalDomain`
- `categoryId`
- `translations.en.title`
- `translations.en.description`
- `translations.en.powerSupplyType`
- `translations.en.medicalDomain`
- `translations.ru.title`
- `translations.ru.description`
- `translations.ru.powerSupplyType`
- `translations.ru.medicalDomain`

Note:

- `categoryId` doit correspondre a une categorie existante

### PUT|PATCH `/api/products/{id}`

Mettre a jour un produit existant.

Parametre:

- `id`: identifiant du produit

Body JSON FR seulement:

```json
{
  "title": "Produit X v2",
  "price": "249.99",
  "inStock": 8,
  "categoryId": 2
}
```

Body JSON pour modifier seulement l'anglais:

```json
{
  "translations": {
    "en": {
      "title": "Updated Patient Monitor",
      "description": "Updated English description",
      "powerSupplyType": "Battery / Mains",
      "medicalDomain": "Diagnostics"
    }
  }
}
```

Body JSON pour modifier seulement le russe:

```json
{
  "translations": {
    "ru": {
      "title": "Обновленный монитор пациента",
      "description": "Обновленное описание",
      "powerSupplyType": "Аккумулятор / Сеть",
      "medicalDomain": "Диагностика"
    }
  }
}
```

Comportement:

- les champs de base modifient la version FR
- `translations.en` modifie ou cree la traduction anglaise
- `translations.ru` modifie ou cree la traduction russe

### GET `/api/products/{id}/similar`

Retourner jusqu'a 6 produits similaires.

Parametre:

- `id`: identifiant du produit source

Query params:

- `locale` optionnel: `fr`, `en`, `ru`

Logique:

- si `medicalDomain` du produit est renseigne, la recherche se fait sur ce domaine FR de base
- sinon, la recherche se fait sur la categorie
- le contenu retourne est traduit selon `locale`

### GET `/api/products/search?q=...`

Rechercher des produits par titre.

Query params:

- `q`: terme de recherche, minimum 2 caracteres
- `locale` optionnel: `fr`, `en`, `ru`

Reponse:

- tableau vide si `q` fait moins de 2 caracteres

Note:

- selon l'implementation actuelle, la recherche peut continuer a se faire sur le titre FR en base, meme si la reponse est traduite

## Panier et commandes

### POST `/api/order/add-item`

Ajouter un produit au panier.

Comportement:

- utilisateur connecte: ajout dans la commande `cart`
- utilisateur invite: ajout dans la session serveur

Body JSON:

```json
{
  "productId": 12,
  "quantity": 2
}
```

Champs obligatoires:

- `productId`
- `quantity`

Regles:

- `productId` doit exister
- `quantity` doit etre > 0
- le stock doit etre suffisant

Reponse:

- invite: `message` + contenu brut du panier session
- connecte: `message`, `totalPrice`, `promo`

### GET `/api/order/my-order`

Retourner le panier courant.

Comportement:

- utilisateur invite: retourne le panier stocke en session
- utilisateur connecte: retourne la commande en statut `cart`

Reponse connectee:

- `orderId`
- `userId`
- `status`
- `totalPrice`
- `promo`
- `items`

Reponse invitee:

- `orderId: null`
- `items`
- `totalPrice: 0`

### PATCH `/api/order/update-items`

Mettre a jour les quantites d'items du panier.

Authentification:

- `ROLE_USER` requis

Body JSON:

```json
{
  "items": [
    { "itemId": 3, "quantity": 2 },
    { "itemId": 4, "quantity": 1 }
  ]
}
```

Champs obligatoires:

- `items`: tableau d'objets
- `itemId`
- `quantity`

Regles:

- la quantite minimale appliquee est `1`
- le stock est verifie pour chaque item

### DELETE `/api/order/remove-item/{id}`

Supprimer un item du panier.

Authentification:

- `ROLE_USER` requis

Parametre:

- `id`: identifiant de l'item de commande

Reponse:

- `message`
- `totalPrice`

### POST `/api/order/checkout`

Creer une session de paiement Stripe pour le panier courant.

Authentification:

- `ROLE_USER` requis

Body JSON:

- aucun body attendu

Verifications:

- le panier ne doit pas etre vide
- le stock de chaque produit doit etre suffisant

Reponse:

- `message`
- `orderId`
- `url` de redirection Stripe

### POST `/api/order/stripe/webhook`

Recevoir les evenements Stripe apres paiement.

Authentification:

- publique

Attendus:

- header `Stripe-Signature`
- payload Stripe brut

Comportement:

- traite uniquement `checkout.session.completed`
- lit `metadata.orderId`
- passe la commande au statut `Paye`
- decrement le stock des produits

Reponse:

- `{"ok": true}` si tout va bien
- erreurs si signature, commande ou stock invalides

### GET `/api/order/success`

Retourne un message de succes apres paiement.

Query params:

- `session_id` optionnel

Reponse:

- `message`
- `session_id`

## Factures

### GET `/api/invoice/{id}`

Generer et retourner la facture PDF d'une commande payee.

Authentification:

- `ROLE_USER` requis

Parametre:

- `id`: identifiant de la commande

Conditions:

- la commande doit exister
- la commande doit appartenir a l'utilisateur connecte
- la commande doit etre au statut `Paye`

Reponse:

- PDF inline (`Content-Type: application/pdf`)
- erreurs `401`, `403`, `404` ou `400` selon le cas

## Carousel

### GET `/api/carousel`

Retourner tous les elements du carousel (public).

Reponse:

- tableau des elements du carousel avec images et order

## Chatbot

### POST `/api/chatbot/message`

Envoyer un message au chatbot.

Body JSON:

```json
{
  "message": "Bonjour",
  "sessionId": "optional-session-id"
}
```

Champs obligatoires:

- `message`

Champs optionnels:

- `sessionId`

Reponse:

- `response`: reponse du chatbot
- `sessionId`: identifiant de session

### POST `/api/chatbot/log`

Enregistrer une interaction avec le chatbot.

Body JSON:

```json
{
  "message": "User message",
  "response": "Chatbot response",
  "sessionId": "session-id"
}
```

Reponse:

- message de confirmation

### POST `/api/chatbot/escalate`

Escalader une conversation chatbot vers un agent humain.

Body JSON:

```json
{
  "sessionId": "session-id",
  "reason": "Need human support"
}
```

Reponse:

- message de confirmation

## Contact

### POST `/api/contact`

Soumettre une demande de contact.

Body JSON:

```json
{
  "email": "user@example.com",
  "name": "Jean Dupont",
  "subject": "Demande d'info",
  "message": "Je souhaite obtenir des infos..."
}
```

Reponse:

- `201` si creee avec succes
- `400` si donnees invalides

## Homepage

### GET `/api/homepage/top-products`

Retourner les produits mis en avant sur la page d'accueil.

Query params:

- `locale` optionnel: `fr`, `en`, `ru`

Reponse:

- tableau des top produits

## Admin - Authentication

### POST `/api/admin/auth/verify-2fa`

Verifier le code 2FA pour un admin.

Authentification:

- `ROLE_ADMIN` requis

Body JSON:

```json
{
  "challengeId": "challenge-id",
  "code": "123456"
}
```

Champs obligatoires:

- `challengeId`
- `code`

Reponse:

- `200` avec token JWT si valide
- `401` si code invalide

## Admin - Carousel

### GET `/api/admin/carousel`

Retourner tous les elements du carousel (admin).

Authentification:

- `ROLE_ADMIN` requis

Reponse:

- tableau de tous les elements carousel avec order

### POST `/api/admin/carousel`

Creer un nouvel element carousel.

Authentification:

- `ROLE_ADMIN` requis

Body JSON:

```json
{
  "title": "Titre",
  "description": "Description",
  "imageUrl": "https://example.com/image.jpg",
  "link": "https://example.com",
  "order": 1
}
```

Reponse:

- `201` element cree

### PATCH `/api/admin/carousel/reorder`

Reordonner les elements du carousel.

Authentification:

- `ROLE_ADMIN` requis

Body JSON:

```json
{
  "items": [
    { "id": 1, "order": 3 },
    { "id": 2, "order": 1 },
    { "id": 3, "order": 2 }
  ]
}
```

Reponse:

- `200` avec message de confirmation

### PATCH|PUT `/api/admin/carousel/{id}`

Mettre a jour un element carousel.

Authentification:

- `ROLE_ADMIN` requis

Parametre:

- `id`: identifiant de l'element

Body JSON:

```json
{
  "title": "Nouveau titre",
  "order": 2
}
```

Reponse:

- `200` avec element modifie

### DELETE `/api/admin/carousel/{id}`

Supprimer un element carousel.

Authentification:

- `ROLE_ADMIN` requis

Parametre:

- `id`: identifiant de l'element

Reponse:

- `204` No Content

## Admin - Chatbot

### GET `/api/admin/chatbot/logs`

Retourner tous les logs du chatbot.

Authentification:

- `ROLE_ADMIN` requis

Query params:

- `limit` optionnel: nombre de logs a retourner
- `offset` optionnel: pagination

Reponse:

- tableau de tous les logs chatbot

### GET `/api/admin/chatbot/logs/{sessionId}`

Retourner tous les logs d'une session chatbot.

Authentification:

- `ROLE_ADMIN` requis

Parametre:

- `sessionId`: identifiant de la session

Reponse:

- tableau des logs pour cette session

## Admin - Contact

### GET `/api/admin/contact/messages`

Retourner tous les messages de contact.

Authentification:

- `ROLE_ADMIN` requis

Query params:

- `status` optionnel: filtre par statut
- `limit` optionnel
- `offset` optionnel

Reponse:

- tableau de tous les messages de contact

### GET `/api/admin/contact/messages/{id}`

Retourner un message de contact specifique.

Authentification:

- `ROLE_ADMIN` requis

Parametre:

- `id`: identifiant du message

Reponse:

- objet message avec details

### PATCH `/api/admin/contact/messages/{id}/status`

Mettre a jour le statut d'un message de contact.

Authentification:

- `ROLE_ADMIN` requis

Parametre:

- `id`: identifiant du message

Body JSON:

```json
{
  "status": "read"
}
```

Reponse:

- `200` avec message modifie

### POST `/api/admin/contact/messages/{id}/reply`

Envoyer une reponse a un message de contact.

Authentification:

- `ROLE_ADMIN` requis

Parametre:

- `id`: identifiant du message

Body JSON:

```json
{
  "message": "Reponse a votre demande..."
}
```

Reponse:

- `201` avec confirmation

## Admin - Dashboard

### GET `/api/admin/dashboard/sales/daily`

Retourner les ventes journalieres.

Authentification:

- `ROLE_ADMIN` requis

Reponse:

- statistiques ventes du jour

### GET `/api/admin/dashboard/sales/weekly`

Retourner les ventes hebdomadaires.

Authentification:

- `ROLE_ADMIN` requis

Reponse:

- statistiques ventes de la semaine par jour

### GET `/api/admin/dashboard/sales/weekly-by-category`

Retourner les ventes hebdomadaires par categorie.

Authentification:

- `ROLE_ADMIN` requis

Reponse:

- ventes par categorie sur 7 jours

### GET `/api/admin/dashboard/sales/category-share`

Retourner la part de marche par categorie.

Authentification:

- `ROLE_ADMIN` requis

Reponse:

- pourcentage ventes par categorie

## Admin - Orders

### GET `/api/admin/orders`

Retourner toutes les commandes.

Authentification:

- `ROLE_ADMIN` requis

Query params:

- `status` optionnel: filtre par statut
- `limit` optionnel
- `offset` optionnel

Reponse:

- tableau de toutes les commandes

### GET `/api/admin/orders/{id}`

Retourner les details d'une commande.

Authentification:

- `ROLE_ADMIN` requis

Parametre:

- `id`: identifiant de la commande

Reponse:

- objet commande avec items et details

### PATCH `/api/admin/orders/{id}/status`

Mettre a jour le statut d'une commande.

Authentification:

- `ROLE_ADMIN` requis

Parametre:

- `id`: identifiant de la commande

Body JSON:

```json
{
  "status": "shipped"
}
```

Reponse:

- `200` avec commande modifiee

## Admin - Products

### GET `/api/admin/products`

Retourner tous les produits (admin).

Authentification:

- `ROLE_ADMIN` requis

Query params:

- `locale` optionnel: `fr`, `en`, `ru`
- `limit` optionnel
- `offset` optionnel

Reponse:

- tableau de tous les produits avec details complets

### GET `/api/admin/products/{id}`

Retourner un produit avec tous les details (admin).

Authentification:

- `ROLE_ADMIN` requis

Parametre:

- `id`: identifiant du produit

Reponse:

- objet produit avec toutes les donnees

### POST `/api/admin/products`

Creer un produit (voir section Produits pour body JSON complet).

Authentification:

- `ROLE_ADMIN` requis

Reponse:

- `201` produit cree

### PATCH|PUT `/api/admin/products/{id}`

Mettre a jour un produit (voir section Produits pour body JSON).

Authentification:

- `ROLE_ADMIN` requis

Parametre:

- `id`: identifiant du produit

Reponse:

- `200` avec produit modifie

### DELETE `/api/admin/products/{id}`

Supprimer un produit.

Authentification:

- `ROLE_ADMIN` requis

Parametre:

- `id`: identifiant du produit

Reponse:

- `204` No Content

### POST `/api/admin/products/bulk`

Operations en masse sur les produits.

Authentification:

- `ROLE_ADMIN` requis

Body JSON:

```json
{
  "action": "publish",
  "ids": [1, 2, 3]
}
```

Actions possibles:

- `publish`: publier des produits
- `unpublish`: depublier des produits
- `delete`: supprimer en masse

Reponse:

- `200` avec nombre d'items modifies

## Admin - Homepage

### GET `/api/admin/homepage/top-products`

Retourner les produits mis en avant (admin).

Authentification:

- `ROLE_ADMIN` requis

Reponse:

- tableau des top produits avec details

### PUT `/api/admin/homepage/top-products`

Remplacer les produits mis en avant.

Authentification:

- `ROLE_ADMIN` requis

Body JSON:

```json
{
  "productIds": [1, 3, 5, 7]
}
```

Reponse:

- `200` avec nouvelle liste

## Admin - Upload

### POST `/api/admin/upload`

Uploader un fichier (image, PDF, etc).

Authentification:

- `ROLE_ADMIN` requis

Content-Type:

- `multipart/form-data`

Body:

- `file`: fichier a uploader

Reponse:

- `201` avec URL du fichier uploader
- `400` si fichier invalide

