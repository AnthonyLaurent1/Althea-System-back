# Projet Althea System

Backend Symfony du projet Althea System.

## Installation du projet

1. Cloner le depot.
2. Se placer dans le repertoire du projet.
3. Installer les dependances :

```bash
composer install
```

## Configuration de l'environnement

1. Copier le fichier d'exemple :

```bash
cp .env.dist .env
```

2. Modifier les variables de connexion a la base de donnees dans `.env` :

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/nom_de_la_bdd?serverVersion=8.0"
```

## Creation de la base de donnees

1. Creer la base :

```bash
symfony console doctrine:database:create
```

2. Generer une migration si necessaire :

```bash
php bin/console make:migration
```

3. Executer les migrations :

```bash
symfony console doctrine:migrations:migrate
```

## Lancement du serveur de developpement

Pour demarrer le serveur local :

```bash
symfony serve
```

## Generation de fausses donnees

Le projet contient plusieurs commandes Symfony pour alimenter la base avec des donnees de demonstration.

Ces commandes servent a :

- tester rapidement l'API
- afficher un catalogue sans saisie manuelle
- verifier le fonctionnement des categories, produits et traductions

### Commande principale

```bash
php bin/console app:seed-data
```

Cette commande :

- cree les categories de base
- cree plusieurs produits de demonstration
- remplit la base avec des informations factices utiles pour les tests

Elle sert a preparer rapidement une base exploitable en francais.

### Traductions anglaises

```bash
php bin/console app:seed-translations-en
```

Cette commande :

- recupere les categories existantes
- ajoute leur traduction anglaise
- recupere les produits existants
- ajoute leur traduction anglaise

Elle sert a tester les routes avec :

```http
/api/categories?locale=en
/api/products?locale=en
```

### Traductions russes

```bash
php bin/console app:seed-translations-ru
```

Cette commande :

- recupere les categories existantes
- ajoute leur traduction russe
- recupere les produits existants
- ajoute leur traduction russe

Elle sert a tester les routes avec :

```http
/api/categories?locale=ru
/api/products?locale=ru
```

## Ordre conseille pour remplir la base

Pour obtenir une base complete de demonstration :

```bash
php bin/console app:seed-data
php bin/console app:seed-translations-en
php bin/console app:seed-translations-ru
```

Cela permet d'avoir :

- les donnees principales en francais
- les traductions anglaises
- les traductions russes

## Documentation API

La documentation des routes API est disponible dans :

- `API_ROUTES.md`

Ce fichier decrit :

- les endpoints disponibles
- leur role
- les donnees attendues
- le fonctionnement des traductions `fr`, `en` et `ru`
