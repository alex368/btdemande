# BT Demande

Application Symfony 7.4 pour la gestion des demandes de financement et documents clients.

## 🚀 Fonctionnalités

- **Gestion des demandes de financement** — Suivi complet des demandes, statuts, et validation
- **Admin Panel** — Interface EasyAdmin pour la gestion des entités
- **Intégration Google** — Connexion OAuth2 et accès à Google Sheets/Docs
- **Traitement de documents** — Génération et parsing de PDF, Excel, Word, PowerPoint
- **Portail client** — Accès personnalisé pour les clients avec leurs dossiers
- **Chat en temps réel** — Communication intégrée avec les clients
- **Scheduler** — Tâches planifiées et automatisées
- **Multi-rôles** — Admin, Collaborateur, Client

## 📋 Stack Technique

- **Framework:** Symfony 7.4
- **PHP:** 8.2+
- **BDD:** Doctrine ORM 3.6 + PostgreSQL/MySQL
- **Frontend:** Twig + UX Turbo + UX Twig Components
- **Admin:** EasyAdmin 4.27
- **Documents:** dompdf, PhpWord, PhpPresentation, PhpSpreadsheet
- **Intégrations:** Google API Client, LLPhant (IA)
- **Docker:** Containerisé avec Compose

## 🏗️ Structure du Projet

```
.
├── src/
│   ├── Controller/          # Controllers (routes, logique)
│   ├── Entity/              # Entities Doctrine (modèles)
│   ├── Form/                # Form types
│   ├── Repository/          # Repositories (requêtes BD)
│   └── Schedule.php         # Tâches planifiées
├── config/                  # Configuration Symfony (services, routes)
├── templates/               # Templates Twig
├── migrations/              # Migrations Doctrine
├── docker/                  # Fichiers Docker
├── assets/                  # Assets (CSS, JS, images)
└── tests/                   # Tests PHPUnit
```

## 🚦 Démarrage

### Avec Docker (recommandé)

```bash
docker-compose up -d
docker-compose exec app php bin/console doctrine:migrations:migrate
docker-compose exec app php bin/console cache:clear
```

L'app sera disponible à `http://localhost`

### Sans Docker

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
symfony serve
```

## 🔐 Configuration

### Variables d'environnement

Copier `.env` et adapter `.env.local`:

```bash
cp .env .env.local
```

Clés principales:
- `DATABASE_URL` — Connexion BDD
- `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` — Intégration Google
- `MAILER_DSN` — Configuration email

### Google OAuth

1. Aller à https://console.cloud.google.com
2. Créer un projet et une credential OAuth2
3. Copier Client ID et Secret dans `.env.local`
4. Autoriser les redirect URIs: `http://localhost/google/auth` (dev) ou ton domaine prod

## 📖 Commandes Utiles

```bash
# Migrations BDD
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:make

# Cache
php bin/console cache:clear
php bin/console cache:warmup

# Fixtures (données de test)
php bin/console doctrine:fixtures:load

# Tests
php bin/console test
./vendor/bin/phpunit

# Scheduler
php bin/console schedule:run
```

## 🐛 Troubleshooting

### Erreur "Undefined variable"
Vérifier les controllers pour les variables non initialisées avant utilisation.

### Migration échouée
```bash
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:execute <version>
```

### Permission Docker
```bash
sudo usermod -aG docker $USER
newgrp docker
```

## 📞 Support & Contribution

Pour les bugs ou questions, ouvrir une issue sur GitHub.

## 📄 Licence

Proprietary
