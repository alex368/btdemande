#!/bin/bash
set -e

# ============================================================================
# Docker Entrypoint - Initialisation des permissions et démarrage PHP-FPM
# ============================================================================

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Starting PHP-FPM with volume init..."

safe_chmod_recursive() {
  local mode="$1"
  local path="$2"

  if [ -e "$path" ]; then
    chmod -R "$mode" "$path" 2>/dev/null || echo "[WARN] Could not chmod $path to $mode, continuing..."
  fi
}

# ============================================================================
# 1) INITIALISER LES PERMISSIONS DES VOLUMES
# ============================================================================

# var/ doit être writable par app pour cache et logs
if [ -d /var/www/html/var ]; then
  echo "[INIT] Fixing var/ permissions..."
  safe_chmod_recursive 755 /var/www/html/var
  chmod -R g+w /var/www/html/var 2>/dev/null || echo "[WARN] Could not add group write on /var/www/html/var, continuing..."

  # Cas spécial: tmp, cache, log doivent être très accessibles
  safe_chmod_recursive 777 /var/www/html/var/tmp
  safe_chmod_recursive 777 /var/www/html/var/cache
  safe_chmod_recursive 777 /var/www/html/var/log
fi

# Repartir d'un cache Symfony propre évite de garder un container compilé
# avec un ancien jeu de bundles dans le volume Docker persistant.
if [ -d /var/www/html/var/cache ]; then
  echo "[INIT] Resetting Symfony cache..."
  find /var/www/html/var/cache -mindepth 1 -maxdepth 1 -exec rm -rf {} + 2>/dev/null || echo "[WARN] Could not fully clear Symfony cache volume, continuing..."
fi

# public/uploads/ doit être writable pour les uploads
if [ -d /var/www/html/public/uploads ]; then
  echo "[INIT] Fixing public/uploads/ permissions..."
  safe_chmod_recursive 755 /var/www/html/public/uploads
  chmod -R g+w /var/www/html/public/uploads 2>/dev/null || echo "[WARN] Could not add group write on /var/www/html/public/uploads, continuing..."
fi

# ============================================================================
# 2) VÉRIFIER LA CONNECTIVITÉ BD
# ============================================================================

if [ ! -z "$DATABASE_URL" ]; then
  echo "[INIT] Waiting for database to be ready..."

  db_ready=0

  # Simple check: essayer une connexion
  for i in {1..30}; do
    if php -r "
      \$url = parse_url(getenv('DATABASE_URL'));
      if (!\$url || (\$url['scheme'] ?? null) !== 'mysql') {
        fwrite(STDERR, 'Unsupported DATABASE_URL');
        exit(1);
      }
      \$host = \$url['host'] ?? 'db';
      \$port = \$url['port'] ?? 3306;
      \$db = isset(\$url['path']) ? ltrim(\$url['path'], '/') : null;
      \$user = \$url['user'] ?? null;
      \$pass = \$url['pass'] ?? null;
      \$dsn = sprintf('mysql:host=%s;port=%s%s', \$host, \$port, \$db ? ';dbname='.\$db : '');
      try {
        \$pdo = new PDO(\$dsn, \$user, \$pass, [PDO::ATTR_TIMEOUT => 2]);
        echo 'Database OK';
        exit(0);
      } catch (Exception \$e) {
        echo 'DB not ready: ' . \$e->getMessage();
        exit(1);
      }
    " 2>/dev/null; then
      echo "✓ Database is ready"
      db_ready=1
      break
    else
      echo "Waiting for database... ($i/30)"
      sleep 1
    fi
  done

  if [ "$db_ready" -ne 1 ]; then
    echo "[INIT] Database did not become ready in time. Exiting to let Docker retry."
    exit 1
  fi
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
