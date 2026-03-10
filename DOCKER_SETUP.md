# Docker Setup & Deployment Guide

Configuration Docker pour BT Demande en production.

## 🔧 Modifications Apportées

### Dockerfile Améliorations
✅ **Ordre des opérations corrigé**
- Composer install **avant** switch user (évite problèmes permissions)
- Cache warmup + assets install en production

✅ **Permissions cohérentes**
- `chmod 755` sur var/ et public/uploads
- Ownership app:app après install

✅ **Composer optimisé**
- `--classmap-authoritative` (prod optimization)
- `composer clear-cache` après install

✅ **Health check ajouté**
- Endpoint FPM vérifié toutes les 30s

### docker-compose.yml Améliorations
✅ **Service health checks**
- PHP: curl vers FPM status
- Nginx: wget vers endpoint /health
- MariaDB: mariadb-admin ping

✅ **Ordre de démarrage intelligent**
- `depends_on: condition: service_healthy`
- Nginx attend que PHP soit healthy
- PHP attend que DB soit healthy

✅ **Robustesse**
- `restart: unless-stopped` sur tous les services
- Container names explicites
- Environment variables pour secrets

✅ **Volume drivers explicites**
- Tous les volumes en `driver: local`

---

## 🚀 Déploiement

### 1. Pré-requis

```bash
# Vérifier Docker/Compose
docker --version      # 20.10+
docker-compose --version  # 2.0+

# Cloner le repo
git clone https://github.com/alex368/btdemande.git
cd btdemande
```

### 2. Configuration

Créer `.env.prod.local`:

```bash
cat > .env.prod.local << EOF
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=$(head -c 32 /dev/urandom | base64)
DATABASE_URL=mysql://btdemande:btdemande@db:3306/btdemande?serverVersion=mariadb-11.4.0&charset=utf8mb4
OLLAMA_BASE_URL=http://ollama:11434
OLLAMA_CHAT_MODEL=ministral-3:3b
DEFAULT_URI=https://fidelissimo.fr
MAILER_DSN=smtp://your-mail-server:587
EOF
```

### 3. Build & démarrage

```bash
# Build image PHP
docker-compose build

# Démarrer les services
docker-compose up -d

# Vérifier le démarrage
docker-compose logs -f php
```

### 4. Initialisation BD

```bash
# Attendre que la BD soit ready (30-60s)
docker-compose exec php php bin/console doctrine:database:create --if-not-exists

# Migrations
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Fixtures (optionnel)
docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

### 5. Ollama - Modèle

```bash
# Pull le modèle (peut prendre 5-10 min)
docker-compose exec ollama ollama pull ministral-3:3b

# Vérifier
docker-compose exec ollama ollama list
```

---

## ✅ Vérification Post-Déploiement

### Endpoints

- **App:** http://localhost:7070
- **PhpMyAdmin:** http://localhost:4040
- **Ollama API:** http://localhost:11434/api/tags

### Health Checks

```bash
# Tous les services
docker-compose ps

# Health status détaillé
docker inspect btdemande_php --format='{{json .State.Health}}'
docker inspect btdemande_db --format='{{json .State.Health}}'
docker inspect btdemande_nginx --format='{{json .State.Health}}'
```

### Logs

```bash
# PHP logs
docker-compose logs php

# Nginx logs
docker-compose logs nginx

# DB logs
docker-compose logs db

# Ollama logs
docker-compose logs ollama

# Suivi en temps réel
docker-compose logs -f
```

### BD Connection

```bash
# MySQL CLI
docker-compose exec db mariadb -ubtdemande -pbtdemande btdemande

# SQL query
docker-compose exec db mariadb -ubtdemande -pbtdemande btdemande -e "SELECT VERSION();"
```

---

## 🔐 Production Checklist

- [ ] `.env.prod.local` configuré avec secrets uniques
- [ ] `APP_SECRET` généré et fort (32 caractères min)
- [ ] `APP_DEBUG=0` (jamais 1 en prod)
- [ ] `DATABASE_URL` pointe vers DB prod
- [ ] `MAILER_DSN` configuré
- [ ] Ollama modèle téléchargé
- [ ] HTTPS/SSL configuré (nginx reverse proxy)
- [ ] Backups BD en place
- [ ] Logs centralisés
- [ ] Monitoring actif

---

## 🛑 Troubleshooting

### "Unable to connect to database"

```bash
# Vérifier BD health
docker-compose logs db

# Réinitialiser BD
docker-compose down -v
docker-compose up -d db

# Attendre ~30s puis retry
sleep 30
docker-compose exec php php bin/console doctrine:database:create
```

### "Ollama: connection refused"

```bash
# Vérifier Ollama est up
docker-compose logs ollama

# Redémarrer
docker-compose restart ollama

# Vérifier modèle
docker-compose exec ollama ollama list
```

### "Permission denied var/"

```bash
# Fix permissions
docker-compose exec php chmod -R 755 var
docker-compose exec php chown -R app:app /var/www/html
```

### "Assets not loading"

```bash
# Rebuild assets
docker-compose exec php php bin/console assets:install --env=prod
docker-compose restart nginx
```

---

## 📋 Maintenance

### Backups

```bash
# BD backup
docker-compose exec db mariadb-dump -ubtdemande -pbtdemande btdemande > backup.sql

# Uploads backup
docker cp btdemande_php:/var/www/html/public/uploads/documents ./uploads_backup
```

### Updates

```bash
# Pull latest code
git pull origin main

# Rebuild image
docker-compose build --no-cache

# Restart services
docker-compose up -d

# Run migrations
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### Monitoring

```bash
# Resource usage
docker stats btdemande_php btdemande_db btdemande_nginx

# Check running processes
docker top btdemande_php
```

---

## 🔗 Ressources

- [Docker Compose Docs](https://docs.docker.com/compose)
- [Symfony Docker Guide](https://symfony.com/doc/current/setup/docker.html)
- [MariaDB Docker](https://hub.docker.com/_/mariadb)
- [Ollama Docker](https://hub.docker.com/r/ollama/ollama)
