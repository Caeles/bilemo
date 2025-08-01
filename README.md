# Bilemo API

## Description
Bilemo est une API RESTful développée avec Symfony pour permettre aux clients de Bilemo d'accéder au catalogue de téléphones mobiles. 

## Fonctionnalités
- Catalogue de produits (téléphones mobiles) avec pagination
- Gestion des utilisateurs par client (CRUD)
- Authentification JWT
- Documentation API interactive avec Nelmio
- Sérialisation avec groupes JMS Serializer
- Relations HATEOAS pour une navigation intuitive entre les ressources


## Prérequis
- PHP 8.2
- MySQL 8.0 
- Serveur web (Apache, Nginx)
- Composer
- Symfony CLI 

## Installation

### 1. Cloner le dépôt
```bash
git clone https://github.com/caeles/bilemo.git
cd bilemo
```

### 2. Installer les dépendances
```bash
composer install
```

### 3. Configurer l'environnement
Copiez le fichier `.env` en `.env.local` et configurez les variables d'environnement, notamment la connexion à la base de données :
```bash
DATABASE_URL=mysql://username:password@127.0.0.1:3306/bilemo
###> lexik/jwt-authentication-bundle ###
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_passphrase
###< lexik/jwt-authentication-bundle ###
```

### 4. Créer la base de données et générer les clés JWT
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

### 5. Charger les fixtures (données de test)
```bash
php bin/console doctrine:fixtures:load
```

### 6. Démarrer le serveur
```bash
symfony serve
```

## Utilisation de l'API

### Authentification
Pour obtenir un token JWT :
```
POST /api/login_check
Content-Type: application/json

{
  "username": "admin@admin.com",
  "password": "admin"
}
```

### Documentation
La documentation interactive de l'API est accessible à l'adresse :
```
GET /api/doc
```

### Endpoints principaux
- `GET /api/products` : Liste des produits (pagination avec paramètres `page` et `limit`)
- `GET /api/products/{id}` : Détails d'un produit
- `GET /api/users` : Liste des utilisateurs (pagination avec paramètres `page` et `limit`)
- `GET /api/user/{id}` : Détails d'un utilisateur
- `POST /api/user` : Création d'un utilisateur
- `DELETE /api/user/{id}` : Suppression d'un utilisateur
- `GET /api/customers` : Liste des clients
- `GET /api/customers/{id}` : Détails d'un client
- `GET /api/customers/{id}/users` : Liste des utilisateurs d'un client spécifique

## Licence

MIT License

Copyright (c) 2025
