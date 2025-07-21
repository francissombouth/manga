#!/bin/sh
set -e

echo "=== DEBUT INIT.SH ==="
echo "--- CONTENU INIT.SH ---"
cat /usr/local/bin/init.sh
echo "--- FIN CONTENU INIT.SH ---"

# Extraire les infos de connexion depuis DATABASE_URL (compatible Render et local)
if [ -n "$DATABASE_URL" ]; then
  export PGHOST=$(echo $DATABASE_URL | sed -E 's|.*@([^:/]+):([0-9]+).*|\1|')
  export PGPORT=$(echo $DATABASE_URL | sed -E 's|.*@([^:/]+):([0-9]+).*|\2|')
  export PGUSER=$(echo $DATABASE_URL | sed -E 's|postgres://([^:]+):.*|\1|')
else
  export PGHOST=database
  export PGPORT=5432
  export PGUSER=postgres
fi

echo "DATABASE_URL=$DATABASE_URL"
echo "PGHOST=$PGHOST"
echo "PGPORT=$PGPORT"
echo "PGUSER=$PGUSER"

echo "Attente de la base de données à $PGHOST:$PGPORT (utilisateur: $PGUSER)..."
while ! pg_isready -h "$PGHOST" -p "$PGPORT" -U "$PGUSER"; do
  sleep 1
done
echo "Base de données disponible !"

cd /var/www/html

# Migrations (ignorer l’erreur si déjà fait)
php bin/console doctrine:migrations:migrate --no-interaction || echo "Migrations échouées, on continue..."

# Compilation des assets (ignorer l’erreur si pas critique)
php bin/console asset-map:compile || echo "Asset compilation failed, continuing..."

# Permissions
chown -R www-data:www-data var public
chmod -R 755 var public

echo "Initialisation terminée !"

echo "=== FIN INIT.SH ==="

# Lancer le service principal (Nginx + PHP-FPM via Supervisor)
exec "$@" 