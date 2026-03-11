# Analyse Docker - Problèmes et Fixes

**Date:** 2026-03-10  
**Status:** ⚠️ 6 problèmes critiques/majeurs identifiés

---

## 🔴 PROBLÈMES IDENTIFIÉS

### 1. **Composer Install SANS Scripts Symfony** (CRITIQUE)
**Localisation:** Ligne 9 du Dockerfile  
**Problème:**
```dockerfile
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
```
- `--no-scripts` = **SKIPS** `post-install-cmd` et `post-update-cmd` hooks
- Dans Symfony, cela signifie:
  - ❌ `.env.local.php` n'est pas généré
  - ❌ `bin/console cache:warmup` ne s'exécute pas
  - ❌ Permissions sur `var/` peuvent être incorrectes
  - ❌ L'app démarre sans cache précompilé → **LENT + RISQUÉ en prod**

**Impact:** 🔴 App non-fonctionnelle ou très lente au démarrage

**Fix:**
```dockerfile
RUN composer install --no-dev --optimize-autoloader --no-interaction
```
Ou si vous avez besoin de scripts custom:
```dockerfile
RUN composer install --no-dev --optimize-autoloader --no-interaction && \
    bin/console cache:warmup --env=prod
```

---

### 2. **Ordre USER app trop tôt** (MAJEUR)
**Localisation:** Avant `composer install`  
**Problème:**
```dockerfile
USER app
RUN composer install --no-dev...
```
- L'utilisateur `app` (uid 1000) n'a PAS les droits écriture partout
- Composer crée des fichiers/dossiers pendant l'install
- Les permissions finales sont **inconsistantes**
- Cache Composer peut être inaccessible

**Fix:**
```dockerfile
# AVANT USER app
RUN composer install --no-dev --optimize-autoloader --no-interaction

# APRÈS, chown tous les fichiers
RUN chown -R app:app /var/www/html

# ENFIN, switch user
USER app
```

---

### 3. **mkdir + chown en ROOT puis USER app** (MAJEUR)
**Localisation:** Lignes 7-8 du Dockerfile
```dockerfile
RUN mkdir -p var/tmp var/cache var/log public/uploads/documents \
 && chown -R app:app /var/www/html

USER app  # ← APRÈS
```

**Problème:**
- Répertoires créés en ROOT avec umask par défaut (755)
- L'utilisateur `app` n'a pas toujours les droits d'écriture complets
- Symfony `var/cache` et `var/log` ont besoin de **777 ou strictement 755+writable**

**Fix:**
```dockerfile
# Créer répertoires AVANT chown avec droits explicites
RUN mkdir -p var/tmp var/cache var/log public/uploads/documents && \
    chown -R app:app var tmp public/uploads && \
    chmod -R g+w var

# Plus tard, pas de re-chown
USER app
```

---

### 4. **Volumes `uploads` et `var_data` sans init** (MAJEUR)
**Localisation:** docker-compose.yml + Dockerfile  
**Problème:**
```yaml
volumes:
  - uploads:/var/www/html/public/uploads/documents
  - var_data:/var/www/html/var
```

- Si les volumes Docker sont **vides**, ils écrasent les répertoires Dockerfile
- Le contenu du Dockerfile (`public/uploads/documents` créé, `var/` du composer) **disparaît**
- Permissions deviennent 755 (root:root) par défaut
- `var/cache` vide = app non-fonctionnelle

**Fix - Option A (Préféré - prod):**
Initialiser les volumes au build:
```dockerfile
# Dans le Dockerfile, AVANT COPY
RUN mkdir -p var/tmp var/cache var/log && \
    chmod 777 var/tmp var/cache var/log && \
    mkdir -p public/uploads/documents && \
    chmod 755 public/uploads/documents
```

Puis utiliser un script d'init dans le docker-compose:
```yaml
php:
  volumes:
    - uploads:/var/www/html/public/uploads/documents
    - var_data:/var/www/html/var
  entrypoint: |
    sh -c "
      chown -R app:app /var/www/html/var /var/www/html/public/uploads
      chmod -R g+w /var/www/html/var
      exec php-fpm
    "
```

**Fix - Option B (Dev/Test):**
Ne pas utiliser les volumes en prod, ou les rendre named + init:
```yaml
volumes:
  var_data:
    driver_opts:
      type: none
      o: bind
      device: ./var
```

---

### 5. **OLLAMA_CHAT_MODEL sans pré-téléchargement** (MAJEUR)
**Localisation:** docker-compose.yml env  
```yaml
OLLAMA_CHAT_MODEL: ministral-3:3b
```

**Problème:**
- Le modèle `ministral-3:3b` doit être **téléchargé** dans `/root/.ollama`
- Pas de script d'init dans le container OLLAMA
- Au premier démarrage, il faut **attendre le téléchargement** (peut prendre 5-10 min)
- L'app échouera si elle essaie de parler à OLLAMA avant que le modèle soit prêt

**Fix:**
```yaml
ollama:
  image: ollama/ollama:latest
  ports:
    - "11434:11434"
  volumes:
    - ollama_data:/root/.ollama
  entrypoint: |
    sh -c "
      ollama serve &
      sleep 10
      ollama pull ministral-3:3b
      wait
    "
  networks:
    - symfony
```

Ou utiliser un healthcheck + init script PHP:
```dockerfile
# Dans PHP: Dockerfile
COPY docker/scripts/init-ollama.sh /usr/local/bin/init-ollama
RUN chmod +x /usr/local/bin/init-ollama
```

```bash
#!/bin/bash
# docker/scripts/init-ollama.sh
set -e

OLLAMA_BASE_URL="${OLLAMA_BASE_URL:-http://ollama:11434}"
MODEL="${OLLAMA_CHAT_MODEL:-ministral-3:3b}"

echo "Waiting for OLLAMA to be ready..."
for i in {1..30}; do
  if curl -s "$OLLAMA_BASE_URL/api/tags" > /dev/null 2>&1; then
    echo "✓ OLLAMA is ready"
    break
  fi
  echo "Waiting... ($i/30)"
  sleep 2
done

echo "Ensuring model '$MODEL' is available..."
curl -s "$OLLAMA_BASE_URL/api/pull" \
  -d "{\"name\": \"$MODEL\"}" \
  -X POST || echo "Model may already exist"

echo "✓ OLLAMA init complete"
```

---

### 6. **nginx:ro sur uploads SANS garantie d'existence** (MINEUR)
**Localisation:** docker-compose.yml nginx  
```yaml
volumes:
  - uploads:/var/www/html/public/uploads/documents:ro
```

**Problème:**
- Volume `uploads` peut être **vide** ou non-initialisé
- nginx ne peut servir que ce qui existe
- Si le PHP crée des fichiers uploads, nginx ne les verra pas (read-only!)

**Fix:**
```yaml
nginx:
  volumes:
    - uploads:/var/www/html/public/uploads/documents:ro
    # Option: monter aussi le répertoire public complet (non-uploads)
    - ./public:/var/www/html/public:ro
```

Ou changer la stratégie:
```yaml
# Laisser PHP écrire, nginx lit (sans read-only)
volumes:
  - ./public:/var/www/html/public
```

---

## ✅ RECOMMANDATIONS

### Pour Production:
1. **Retirer `--no-scripts`** de composer install
2. **Déplacer `USER app` APRÈS composer** + chown final
3. **Créer un entrypoint** qui initialise les permissions des volumes
4. **Ajouter healthcheck** OLLAMA
5. **Expliciter les permissions** sur `var/` et `public/uploads`
6. **Documenter le init** de OLLAMA

### Pour Development:
1. Monter le code en bind-mount local
2. Garder les volumes nommés pour BD et OLLAMA
3. Ajouter debug tools (xdebug, logs verbeux)

---

## 📋 CHECKLIST AVANT PROD

- [ ] Composer install sans `--no-scripts`
- [ ] Cache warmup exécuté (`bin/console cache:warmup`)
- [ ] Permissions `var/` = 775 (group writable)
- [ ] `public/uploads` writable par app
- [ ] OLLAMA model pré-téléchargé ou auto-pull
- [ ] Healthchecks en place (PHP, OLLAMA, DB)
- [ ] Volumes initialisés correctement
- [ ] `.env.prod` ou `docker/.env` sécurisé (pas de secrets en plaintext)
- [ ] Logs collectés (`/var/www/html/var/log` sauvegardé)
- [ ] MySQL backup strategy défini

