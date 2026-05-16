# Authentification - Guide Frontend

Ce document explique le fonctionnement de l'authentification du projet `althea-system-back` du point de vue d'un developpeur frontend.

L'objectif est de comprendre :

- quelles routes appeler
- dans quel ordre
- quand stocker le token JWT
- quand envoyer le token
- comment gerer les cas inscription, verification email, connexion, reset mot de passe et deconnexion

## Vue d'ensemble

Le projet utilise une authentification JWT.

Le principe est le suivant :

1. l'utilisateur cree son compte
2. un email de verification lui est envoye
3. il peut obtenir un token JWT via la verification email ou via la route de login
4. le frontend envoie ensuite ce token dans les requetes protegees

Le header a envoyer est :

```http
Authorization: Bearer <token>
```

## Routes utiles

### Inscription

```http
POST /api/auth/register
```

### Connexion

```http
POST /api/auth/login_check
```

### Verification email

```http
GET /api/auth/verify-email/{token}
```

### Mot de passe oublie

```http
POST /api/auth/forgot-password
```

### Reinitialisation du mot de passe

```http
POST /api/auth/reset-password/{token}
```

### Deconnexion

```http
POST /api/auth/logout
```

## 1. Inscription

Le frontend envoie une requete sur :

```http
POST /api/auth/register
Content-Type: application/json
```

Exemple de body :

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

Champs obligatoires :

- `email`
- `password`
- `firstName`
- `lastName`

Comportement attendu cote front :

- envoyer les donnees du formulaire
- afficher le message de succes
- informer l'utilisateur qu'un email de verification a ete envoye

Important :

- cette route ne retourne pas de JWT
- apres inscription, il faut attendre la verification email ou utiliser le login classique

## 2. Verification email

Apres inscription, l'utilisateur recoit un email contenant un lien vers :

```http
GET /api/auth/verify-email/{token}
```

Pour consulter l'email de test envoye par l'application, ouvrir la boite mail Ethereal :

- [https://ethereal.email/messages](https://ethereal.email/messages)

Quand cette route reussit, l'API retourne :

```json
{
  "message": "Email verifie avec succes !",
  "token": "JWT_ICI"
}
```

Comportement recommande cote front :

1. recuperer le `token` depuis la reponse
2. le stocker
3. considerer l'utilisateur comme connecte
4. rediriger vers l'espace connecte

Si le token d'email est invalide :

- afficher un message du type "Lien invalide ou expire"

## 3. Connexion

Le login utilise la route :

```http
POST /api/auth/login_check
Content-Type: application/json
```

Body attendu :

```json
{
  "email": "user@example.com",
  "password": "secret"
}
```

Si les identifiants sont corrects, l'API retourne un JWT.

Comportement recommande cote front :

1. envoyer email + mot de passe
2. recuperer le token de la reponse
3. le stocker
4. utiliser ce token sur toutes les routes protegees

## 4. Stockage du token JWT

Une fois le token recu, le frontend doit le stocker pour l'utiliser sur les requetes suivantes.

Solutions possibles :

- `localStorage`
- `sessionStorage`
- store memoire selon l'architecture du front

Le plus important est que chaque requete protegee envoie :

```http
Authorization: Bearer <token>
```

## 5. Routes protegees

Certaines routes exigent un utilisateur connecte.

Exemples dans le projet :

- `PATCH /api/order/update-items`
- `DELETE /api/order/remove-item/{id}`
- `POST /api/order/checkout`
- `GET /api/invoice/{id}`

Si le token est absent ou invalide, il faut s'attendre a une erreur d'authentification.

Comportement recommande cote front :

- si une requete repond `401`, supprimer le token local si necessaire
- rediriger l'utilisateur vers la page de connexion

## 6. Mot de passe oublie

Le frontend appelle :

```http
POST /api/auth/forgot-password
Content-Type: application/json
```

Body :

```json
{
  "email": "user@example.com"
}
```

Comportement de l'API :

- si l'email existe, un email de reset est envoye
- si l'email n'existe pas, l'API retourne quand meme un message neutre

Pour recuperer l'email de reinitialisation pendant le developpement, consulter la boite mail Ethereal :

- [https://ethereal.email/messages](https://ethereal.email/messages)

Comportement recommande cote front :

- toujours afficher un message du type :
  `Si un compte existe, un email de reinitialisation a ete envoye.`

Cela evite d'exposer si un email existe ou non.

## 7. Reinitialisation du mot de passe

L'utilisateur recoit un email contenant un lien avec un token.

Le frontend doit ensuite appeler :

```http
POST /api/auth/reset-password/{token}
Content-Type: application/json
```

Body :

```json
{
  "password": "nouveau-mot-de-passe"
}
```

Comportement recommande cote front :

1. recuperer le token dans l'URL
2. afficher un formulaire de nouveau mot de passe
3. envoyer le nouveau mot de passe a l'API
4. rediriger ensuite vers la page de connexion

Si le token est invalide ou expire :

- afficher un message d'erreur clair

## 8. Deconnexion

Le frontend peut appeler :

```http
POST /api/auth/logout
```

Mais dans ce projet, l'auth est stateless avec JWT.

Cela veut dire que la deconnexion reelle se gere surtout cote frontend :

1. supprimer le token stocke
2. vider l'etat utilisateur local
3. rediriger vers une page publique

## 9. Flux frontend conseille

### Cas inscription

1. l'utilisateur remplit le formulaire
2. le front appelle `POST /api/auth/register`
3. le front affiche un message indiquant qu'un email de verification a ete envoye

### Cas verification email

1. l'utilisateur clique sur le lien de l'email
2. le front ouvre la page de verification
3. cette page appelle `GET /api/auth/verify-email/{token}`
4. si succes, le front recupere le JWT
5. le token est stocke
6. l'utilisateur est redirige comme connecte

### Cas connexion classique

1. l'utilisateur entre son email et mot de passe
2. le front appelle `POST /api/auth/login_check`
3. le JWT est recupere
4. le JWT est stocke
5. l'utilisateur est considere comme connecte

### Cas mot de passe oublie

1. l'utilisateur entre son email
2. le front appelle `POST /api/auth/forgot-password`
3. le front affiche un message neutre
4. l'utilisateur recoit un lien email
5. le front affiche un formulaire de nouveau mot de passe
6. le front appelle `POST /api/auth/reset-password/{token}`

## 10. Exemple de helper frontend

Exemple simple de requete authentifiee :

```js
const token = localStorage.getItem('token');

await fetch('http://localhost:8000/api/order/checkout', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  }
});
```

## 11. Point d'attention

Dans l'etat actuel du projet, la verification email existe bien, mais il faut verifier dans le comportement metier si la connexion est strictement bloquee tant que `isVerified` est `false`.

Pour le developpement frontend, il vaut mieux prevoir ces deux cas :

- soit l'utilisateur peut se connecter avant verification
- soit le backend evoluera plus tard pour bloquer le login des comptes non verifies

## 12. Resume rapide

- `register` cree le compte
- `verify-email` valide l'email et peut retourner un JWT
- `login_check` connecte et retourne un JWT
- le JWT doit etre envoye dans `Authorization: Bearer <token>`
- `forgot-password` envoie un email de reset
- `reset-password` change le mot de passe
- `logout` cote API est symbolique, la vraie deconnexion se fait surtout cote front
