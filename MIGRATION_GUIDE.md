# Guide de Migration - Docker Fixes

**Date:** 2026-03-10  
**Objectif:** Corriger les problèmes de configuration Docker pour production

---

## 📋 RÉSUMÉ DES CHANGEMENTS

### Fichiers à mettre à jour:
1. ✅ `Dockerfile` → utiliser `Dockerfile.prod`
2. ✅ `docker-compose.yml` → utiliser `docker-compose.fixed.yml`
3. ✅ Créer `docker/scripts/docker-entrypoint.sh` (nouveau)
4. ✅ Créer/mettre à jour `docker/php/conf.d/app.ini`
5. ✅ Créer `docker/nginx/default.conf` (si manquant)

---

## 🚀 ÉTAPES DE MIGRATION

### Phase 1: Préparation (avant changement)

```bash
# 1. Backup des données actuelles
docker-compose exec db mysqldump -uroot -proot btdemande > backup.sql
docker volume inspect btdemande_var_data
docker volume inspect btdemande_ollama_data
docker volume inspect btdemande_uploads

# 2. Arrêter les services
docker-compose down

# 3. Sauvegarder les volumes (optionnel mais prudent)
docker run --rm -v btdemande_db_data:/data -v $(pwd):/backup alpine \
  tar czf /backup/db_data_backup.tar.gz -C /data .

# 4. Copier les fichiers fixes
cp Dockerfile.prod Dockerfile
cp docker-compose.fixed.yml docker-compose.yml
```

### Phase 2: Construction + Démarrage

```bash
# 1. Builder l'image (pour vérifier les erreurs)
docker-compose build --no-cache

# Vérifier: l'image doit contenir:
# - Extensions PHP (intl, pdo_mysql, zip, gd, opcache)
# - Tesseract + modèles (fra, eng)
# - Composer executable
# - User 'app' (uid 1000)
# - Cache Symfony (var/cache/prod)
# - Répertoires var/ avec permissions correctes

# 2. Démarrer les services
docker-compose up -d

# 3. Vérifier les logs
docker-compose logs -f php

# Expected output:
# [INIT] Starting PHP-FPM with volume init...
# [INIT] Fixing var/ permissions...
# [INIT] Fixing public/uploads/ permissions...
# [INIT] ✓ Initialization complete. Starting PHP-FPM...
```

### Phase 3: Vérification (Smoke Test)

```bash
# 1. Vérifier les containers
docker-compose ps
# Status: healthy pour php, db, ollama

# 2. Tester PHP
docker-compose exec php php -v
docker-compose exec php php bin/console about

# 3. Tester BD
docker-compose exec php php bin/console doctrine:query:sql "SELECT 1"

# 4. Tester Ollama
curl http://localhost:11434/api/tags
# Doit retourner un JSON avec le modèle ministral-3:3b

# 5. Tester permissions
docker-compose exec php ls -la var/
# var/ doit être: drwxrwxr-x root app (775)

docker-compose exec php ls -la public/uploads/
# uploads/ doit être: drwxrwxr-x root app (775)

# 6. Tester logs
docker-compose exec php tail -f var/log/prod.log
# Doit être accessible + writable
```

### Phase 4: Nettoyage + Finalisation

```bash
# 1. Arrêter les anciens containers (si version double)
docker ps -a | grep btdemande  # identifier les vieux IDs
docker stop <container_id>
docker rm <container_id>

# 2. Nettoyer les images obsolètes
docker image prune -a -f

# 3. Vérifier le health
docker-compose exec php bin/console about
# App status: OK

# 4. Test functional: créer un upload
# Via interface: tester un upload de document
# Vérifier: fichier dans public/uploads/documents
# Vérifier: nginx peut le servir sur /uploads/...
```

---

## 🔍 CHECKLIST DE VÉRIFICATION

### Dockerfile
- [x] Pas de `--no-scripts` dans composer install
- [x] Composer install EN ROOT (pas avant USER app)
- [x] Cache warmup exécuté (`bin/console cache:warmup`)
- [x] Permissions explicites sur var/ et public/uploads
- [x] USER app switché APRÈS chown final
- [x] Healthcheck en place

### docker-compose.yml
- [x] Healthchecks sur php, db, ollama
- [x] depends_on avec conditions (service_healthy)
- [x] Volumes initialisés (var_data, uploads, db_data, ollama_data)
- [x] Entrypoint personnalisé pour php (docker-entrypoint.sh)
- [x] OLLAMA avec auto-pull du modèle

### Permissions
- [x] var/ = 755 + group writable (g+w)
- [x] var/cache, var/log, var/tmp = 777
- [x] public/uploads = 755 + group writable
- [x] app:app owner partout

### Secrets / Sécurité
- [x] DATABASE_URL en env (pas en code)
- [x] MARIADB_ROOT_PASSWORD changé (actuellement: root ⚠️)
- [x] phpmyadmin PMA_PASSWORD changé (actuellement: btdemande ⚠️)
- [x] HIDE_PHP_VERSION en phpmyadmin

---

## ⚠️ POINTS À SURVEILLER

### 1. **Temps de démarrage OLLAMA**
```
Le pull du modèle `ministral-3:3b` peut prendre 5-15 minutes.
Statut: "Pulling" vs "Ready"
Container OLLAMA must be healthy avant que PHP essaie de l'utiliser.
```

### 2. **Permissions des fichiers uploads**
```
Si PHP crée un fichier uploads/doc.pdf:
- Créé par user 'app' (uid 1000)
- Propriétaire: app:app
- Permissions: 644

nginx doit pouvoir le lire (il est en read-only sur uploads/).
```

### 3. **Migrations BD**
```
Actuellement: AUCUNE auto-migration au démarrage.
À implémenter si besoin (voir docker-entrypoint.sh commenté).
```

### 4. **Logs Symfony**
```
var/log/prod.log doit être accessible.
Permissions: 666 ou 644 (app writable).
À penser à la rotation (logrotate ou Monolog config).
```

---

## 🔧 DÉPANNAGE

### ❌ "PHP container keeps restarting"
```
→ Vérifier docker-entrypoint.sh
→ Vérifier: mkdir -p /var/www/html/docker/scripts/
→ chmod +x docker/scripts/docker-entrypoint.sh
```

### ❌ "var/cache/prod not created"
```
→ Vérifier: bin/console cache:warmup a exécuté
→ Vérifier: composer install n'a pas skip les scripts
→ Rebuild: docker-compose build --no-cache
```

### ❌ "Permission denied: var/log/prod.log"
```
→ Vérifier permissions dans docker-entrypoint.sh
→ Vérifier: chmod -R g+w /var/www/html/var/cache
→ Manuellement: docker-compose exec php chmod -R 777 var/
```

### ❌ "Ollama: Model not found"
```
→ Vérifier: curl http://localhost:11434/api/tags
→ Container log: docker-compose logs ollama
→ Manuellement: docker-compose exec ollama ollama pull ministral-3:3b
```

### ❌ "nginx: File not found /uploads/..."
```
→ Vérifier: le volume uploads est monté (docker-compose exec nginx ls -la /var/www/html/public/uploads)
→ Vérifier: nginx.conf pointe sur le bon chemin
→ Vérifier: PHP écrit bien dans /var/www/html/public/uploads (pas uploads_tmp ou ailleurs)
```

---

## 📊 AVANT vs APRÈS

| Aspect | Avant | Après |
|--------|-------|-------|
| **Composer scripts** | ❌ Skippés (`--no-scripts`) | ✅ Exécutés |
| **Cache Symfony** | ❌ Vide au démarrage | ✅ Pré-compilé |
| **USER app timing** | ❌ Avant composer | ✅ Après composer + chown |
| **Permissions var/** | ❌ 755 (root:root) | ✅ 775 (app:app, g+w) |
| **Permissions uploads/** | ❌ Vides/inaccessibles | ✅ 775 (writable par app) |
| **OLLAMA model** | ❌ Pas de garantie | ✅ Auto-pull au démarrage |
| **Health checks** | ❌ Néant | ✅ Sur php, db, ollama |
| **Initialisation BD** | ❌ Manuelle | ✅ Auto-attendre prêt |
| **Startup speed** | ⚠️ Lent (cache chaud) | 🚀 Rapide (cache pré-compilé) |
| **Prod readiness** | ❌ Non | ✅ Oui |

---

## 📝 CONFIGURATION SÉCURITÉ RECOMMANDÉE

### `.env.prod` (à créer):
```bash
APP_ENV=prod
APP_DEBUG=0
DATABASE_URL=mysql://btdemande:SECURE_PASSWORD@db:3306/btdemande?serverVersion=mariadb-11.4.0&charset=utf8mb4
OLLAMA_BASE_URL=http://ollama:11434
OLLAMA_CHAT_MODEL=ministral-3:3b
DEFAULT_URI=https://fidelissimo.fr
```

### `docker-compose.yml` (prod):
```yaml
php:
  env_file:
    - .env.prod
  # env variables ne doivent JAMAIS être en dur
```

### Secrets (optionnel mais meilleur):
```yaml
secrets:
  db_password:
    file: ./secrets/db_password.txt

php:
  environment:
    DATABASE_URL: mysql://btdemande:file_/run/secrets/db_password@db:3306/btdemande...
```

---

## 🎯 PROCHAINES ÉTAPES

- [ ] Appliquer les fixes
- [ ] Tester en staging
- [ ] Vérifier performance (startup time, memory usage)
- [ ] Configurer logs centralisés (ELK, Grafana, etc.)
- [ ] Ajouter monitoring (healthcheck prometheus)
- [ ] Backups automatisés (BD, volumes)
- [ ] CI/CD pour build + deploy
