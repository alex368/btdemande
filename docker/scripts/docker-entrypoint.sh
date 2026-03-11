#!/bin/bash
set -e

# ============================================================================
# Docker Entrypoint - Initialisation des permissions et démarrage PHP-FPM
# ============================================================================

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Starting PHP-FPM with volume init..."

# ============================================================================
# 1) INITIALISER LES PERMISSIONS DES VOLUMES
# ============================================================================

# var/ doit être writable par app pour cache et logs
if [ -d /var/www/html/var ]; then
  echo "[INIT] Fixing var/ permissions..."
  chmod -R 755 /var/www/html/var
  chmod -R g+w /var/www/html/var
  
  # Cas spécial: tmp, cache, log doivent être très accessibles
  chmod -R 777 /var/www/html/var/tmp 2>/dev/null || true
  chmod -R 777 /var/www/html/var/cache 2>/dev/null || true
  chmod -R 777 /var/www/html/var/log 2>/dev/null || true
fi

# public/uploads/ doit être writable pour les uploads
if [ -d /var/www/html/public/uploads ]; then
  echo "[INIT] Fixing public/uploads/ permissions..."
  chmod -R 755 /var/www/html/public/uploads
  chmod -R g+w /var/www/html/public/uploads
fi

# ============================================================================
# 2) VÉRIFIER LA CONNECTIVITÉ BD
# ============================================================================

if [ ! -z "$DATABASE_URL" ]; then
  echo "[INIT] Waiting for database to be ready..."
  
  # Simple check: essayer une connexion
  for i in {1..30}; do
    if php -r "
      \$dsn = getenv('DATABASE_URL');
      try {
        \$pdo = new PDO(\$dsn, null, null, [PDO::ATTR_TIMEOUT => 2]);
        echo 'Database OK';
        exit(0);
      } catch (Exception \$e) {
        echo 'DB not ready: ' . \$e->getMessage();
        exit(1);
      }
    " 2>/dev/null; then
      echo "✓ Database is ready"
      break
    else
      echo "Waiting for database... ($i/30)"
      sleep 1
    fi
  done
fi

# ============================================================================
# 3) OPTIONNEL: RUN MIGRATIONS (si souhaité)
# ============================================================================

# Décommenter si vous voulez auto-run les migrations au démarrage
# if [ "$APP_ENV" = "prod" ] && [ -f /var/www/html/bin/console ]; then
#   echo "[INIT] Running database migrations..."
#   php /var/www/html/bin/console doctrine:migrations:migrate --no-interaction || true
# fi

# ============================================================================
# 4) DÉMARRER PHP-FPM
# ============================================================================

echo "[INIT] ✓ Initialization complete. Starting PHP-FPM..."
exec "$@"
