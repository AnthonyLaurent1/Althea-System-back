# Commandes et Checkout - Guide Frontend

Ce document explique le fonctionnement du systeme de panier, commande, checkout Stripe et facture du projet `althea-system-back` du point de vue d'un developpeur frontend.

L'objectif est de comprendre :

- comment ajouter un produit au panier
- quelle difference existe entre utilisateur invite et utilisateur connecte
- comment recuperer le panier courant
- comment modifier ou supprimer des items
- comment lancer le paiement Stripe
- comment savoir qu'un paiement est termine
- comment recuperer la facture PDF

## Vue d'ensemble

Le systeme fonctionne autour d'une commande en statut `cart`.

Comportement general :

1. un produit est ajoute au panier
2. si l'utilisateur est connecte, le panier est stocke en base
3. si l'utilisateur est invite, le panier est stocke en session
4. l'utilisateur connecte peut lancer un checkout Stripe
5. le webhook Stripe confirme le paiement
6. la commande passe en statut `Paye`
7. la facture PDF devient disponible

## Routes utiles

### Ajouter un produit au panier

```http
POST /api/order/add-item
```

### Recuperer le panier courant

```http
GET /api/order/my-order
```

### Modifier les quantites

```http
PATCH /api/order/update-items
```

### Supprimer un item

```http
DELETE /api/order/remove-item/{id}
```

### Lancer le checkout Stripe

```http
POST /api/order/checkout
```

### Route de succes apres paiement

```http
GET /api/order/success?session_id=...
```

### Webhook Stripe

```http
POST /api/order/stripe/webhook
```

### Recuperer la facture PDF

```http
GET /api/invoice/{id}
```

## 1. Ajouter un produit au panier

Le frontend appelle :

```http
POST /api/order/add-item
Content-Type: application/json
```

Body :

```json
{
  "productId": 12,
  "quantity": 2
}
```

Regles :

- `productId` doit exister
- `quantity` doit etre strictement superieure a `0`
- le stock doit etre suffisant

### Cas utilisateur invite

Si l'utilisateur n'est pas connecte :

- le panier est stocke en session serveur
- la reponse contient un objet `cart`

Exemple de reponse :

```json
{
  "message": "Item ajouté au panier invité",
  "cart": {
    "12": 2
  }
}
```

Important pour le front :

- le format du panier invite est tres simple
- il contient surtout des couples `productId => quantity`
- il ne contient pas les details complets du produit

### Cas utilisateur connecte

Si l'utilisateur est connecte :

- le backend cree ou reutilise une commande avec le statut `cart`
- l'item est ajoute ou mis a jour en base
- le total est recalcule

Exemple de reponse :

```json
{
  "message": "Item ajouté",
  "totalPrice": 199.98,
  "promo": 10
}
```

## 2. Recuperer le panier courant

Le frontend appelle :

```http
GET /api/order/my-order
```

### Cas utilisateur invite

Exemple de reponse :

```json
{
  "orderId": null,
  "items": {
    "12": 2,
    "8": 1
  },
  "totalPrice": 0
}
```

Important :

- pour un invite, le backend ne retourne pas encore le detail complet des produits
- le frontend peut avoir besoin de recharger les produits a part pour enrichir l'affichage

### Cas utilisateur connecte

Exemple de reponse :

```json
{
  "orderId": 5,
  "userId": 3,
  "status": "cart",
  "totalPrice": 189.99,
  "promo": 10,
  "items": [
    {
      "itemId": 7,
      "productId": 12,
      "title": "Moniteur patient",
      "quantity": 2,
      "price": "99.99",
      "total": 199.98,
      "available": true
    }
  ]
}
```

Le frontend peut s'appuyer sur cette route pour :

- afficher le panier
- afficher `itemId`
- calculer les interactions de modification et suppression
- afficher `promo` et `totalPrice`

## 3. Modifier les quantites d'un panier connecte

Le frontend appelle :

```http
PATCH /api/order/update-items
Authorization: Bearer <token>
Content-Type: application/json
```

Body :

```json
{
  "items": [
    { "itemId": 7, "quantity": 3 },
    { "itemId": 8, "quantity": 1 }
  ]
}
```

Important :

- cette route est reservee aux utilisateurs connectes
- si la quantite envoyee est inferieure a `1`, le backend force `1`
- le backend verifie le stock de chaque produit

Exemple de reponse :

```json
{
  "message": "Quantités mises à jour",
  "totalPrice": 249.99,
  "promo": 10
}
```

## 4. Supprimer un item du panier

Le frontend appelle :

```http
DELETE /api/order/remove-item/{id}
Authorization: Bearer <token>
```

Ici, `{id}` correspond a `itemId`, pas a `productId`.

Exemple :

```http
DELETE /api/order/remove-item/7
```

Exemple de reponse :

```json
{
  "message": "Item supprimé",
  "totalPrice": 99.99
}
```

## 5. Regle de total et promo

Le backend recalcule le panier a chaque modification.

Comportement actuel :

- le total est calcule a partir du prix unitaire et de la quantite
- une promo fixe de `10` est appliquee si le sous-total depasse `100`

Le frontend ne doit pas recalculer le vrai total metier de son cote comme source de verite.

Le bon reflexe est :

- afficher les valeurs retournees par l'API

## 6. Lancer le checkout Stripe

Le frontend appelle :

```http
POST /api/order/checkout
Authorization: Bearer <token>
```

Cette route :

- verifie que l'utilisateur est connecte
- verifie que le panier n'est pas vide
- verifie a nouveau le stock
- cree une session Stripe
- retourne l'URL Stripe

Exemple de reponse :

```json
{
  "message": "Redirection vers Stripe",
  "orderId": 5,
  "url": "https://checkout.stripe.com/..."
}
```

Comportement recommande cote front :

1. appeler `POST /api/order/checkout`
2. recuperer `url`
3. rediriger l'utilisateur vers cette URL

Exemple :

```js
const token = localStorage.getItem('token');

const response = await fetch('http://localhost:8000/api/order/checkout', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const data = await response.json();

if (data.url) {
  window.location.href = data.url;
}
```

## 7. Que se passe-t-il apres le paiement Stripe

Quand le paiement est termine, Stripe appelle automatiquement :

```http
POST /api/order/stripe/webhook
```

Cette route est utilisee par Stripe, pas par le frontend.

Elle :

- verifie la signature Stripe
- verifie l'evenement `checkout.session.completed`
- lit `orderId` dans les metadata Stripe
- met la commande en statut `Paye`
- decremente le stock des produits

Important pour le front :

- le frontend ne doit pas appeler cette route
- la confirmation metier du paiement se fait via le webhook, pas juste par la redirection navigateur

## 8. Retour apres paiement

Apres le checkout, Stripe redirige vers :

```http
GET /api/order/success?session_id={CHECKOUT_SESSION_ID}
```

La reponse est simplement :

```json
{
  "message": "Paiement réussi",
  "session_id": "cs_test_..."
}
```

Important :

- cette route confirme surtout le retour navigateur
- la vraie validation du paiement reste le webhook Stripe

Pour le frontend, le plus simple est :

1. afficher une page de succes
2. eventuellement relancer une requete metier pour rafraichir l'etat de commande

## 9. Recuperer la facture PDF

Une fois la commande payee, le frontend peut appeler :

```http
GET /api/invoice/{id}
Authorization: Bearer <token>
```

Conditions :

- l'utilisateur doit etre connecte
- la commande doit exister
- la commande doit appartenir a l'utilisateur connecte
- la commande doit etre en statut `Paye`

La reponse est un PDF avec :

- `Content-Type: application/pdf`

Comportement recommande cote front :

- ouvrir le PDF dans un nouvel onglet
- ou lancer un telechargement

Exemple simple :

```js
const token = localStorage.getItem('token');

const response = await fetch('http://localhost:8000/api/invoice/5', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const blob = await response.blob();
const url = window.URL.createObjectURL(blob);
window.open(url, '_blank');
```

## 10. Flux frontend conseille

### Cas utilisateur invite

1. l'utilisateur ajoute des produits
2. le front appelle `POST /api/order/add-item`
3. le front lit le panier session via `GET /api/order/my-order`
4. le panier invite reste non payable tant que l'utilisateur n'est pas connecte

### Cas utilisateur connecte

1. l'utilisateur ajoute des produits
2. le front appelle `POST /api/order/add-item`
3. le front recupere le panier detaille via `GET /api/order/my-order`
4. le front permet de modifier ou supprimer des lignes
5. le front appelle `POST /api/order/checkout`
6. le front redirige vers Stripe
7. Stripe paie et appelle le webhook
8. le front revient sur une page de succes
9. la facture peut ensuite etre recuperee

## 11. Points d'attention pour le frontend

- `remove-item/{id}` attend un `itemId`, pas un `productId`
- `update-items` et `checkout` exigent un JWT
- le panier invite et le panier connecte n'ont pas exactement la meme structure
- la route `success` ne remplace pas la confirmation webhook
- la facture n'est disponible que si le statut de commande est `Paye`

## 12. Resume rapide

- `add-item` ajoute au panier
- `my-order` retourne le panier courant
- `update-items` modifie les quantites
- `remove-item` supprime une ligne de panier
- `checkout` cree une session Stripe et retourne une URL
- `stripe/webhook` confirme le paiement cote backend
- `success` sert au retour navigateur apres Stripe
- `invoice/{id}` retourne la facture PDF
