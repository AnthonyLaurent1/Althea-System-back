# Projet Althea System

## Installation du projet

1. Cloner le dépôt :
2. Composer install dans le répertoire du projet.

## Configuration de l’environnement

1. Dans le terminal : cp .env.dist .env

2. Modifier les variables de connexion à la base de données dans .env :

DATABASE_URL="mysql://user:password@127.0.0.1:3306/nom_de_la_bdd?serverVersion=8.0"

## Création de la base de données

1. Créer la base de données : symfony console doctrine:database:create
2. Exécuter les migrations : php bin/console make:migration & symfony console doctrine:migrations:migrate

## Lancement du serveur de développement

1. Lancer le serveur : symfony serve
