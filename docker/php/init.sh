#!/bin/sh
set -e

echo "=== DEBUT INIT.SH ==="

# Extraire les infos de connexion depuis DATABASE_URL
if [ -n "$DATABASE_URL" ]; then
    # Extraction correcte des composants de l'URL PostgreSQL
    # Format: postgresql://user:password@host:port/database
    
    # Extraire le host (partie entre @ et :port)
    export PGHOST=$(echo "$DATABASE_URL" | sed -n 's|.*@\([^:]*\):.*|\1|p')
    
    # Extraire le port (partie entre :host: et /database)  
    export PGPORT=$(echo "$DATABASE_URL" | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
    
    # Extraire l'utilisateur (partie entre :// et :password)
    export PGUSER=$(echo "$DATABASE_URL" | sed -n 's|.*://\([^:]*\):.*|\1|p')
    
    # Extraire le nom de la base (partie après le dernier /)
    export PGDATABASE=$(echo "$DATABASE_URL" | sed -n 's|.*/\([^/]*\)$|\1|p')
    
else
    # Valeurs par défaut pour Docker local
    export PGHOST=database
    export PGPORT=5432
    export PGUSER=postgres
    export PGDATABASE=symfony
fi

echo "DATABASE_URL=$DATABASE_URL"
echo "PGHOST=$PGHOST"
echo "PGPORT=$PGPORT" 
echo "PGUSER=$PGUSER"
echo "PGDATABASE=$PGDATABASE"

echo "Attente de la base de données à $PGHOST:$PGPORT (utilisateur: $PGUSER)..."

# Attendre que PostgreSQL soit disponible
while ! pg_isready -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE"; do
    echo "En attente de PostgreSQL..."
    sleep 2
done

echo "Base de données disponible !"

cd /var/www/html

# Vérifier la connectivité Symfony
echo "Test de connectivité Doctrine..."
php bin/console doctrine:schema:validate --skip-sync || echo "Schéma pas encore synchronisé, normal au premier déploiement"

# Migrations
echo "Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || echo "Migrations échouées, on continue..."

# Compilation des assets (si Webpack Encore est utilisé)
if [ -f "webpack.config.js" ]; then
    echo "Compilation des assets..."
    php bin/console asset-map:compile || echo "Asset compilation failed, continuing..."
fi

# Cache Symfony
echo "Nettoyage du cache..."
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Permissions
echo "Configuration des permissions..."
chown -R www-data:www-data var public/uploads 2>/dev/null || true
chmod -R 755 var public 2>/dev/null || true

echo "Initialisation terminée avec succès !"
echo "=== FIN INIT.SH ==="

# Lancer le service principal
exec "$@"