#!/bin/sh
set -e

echo "=== DEBUT INIT.SH ==="

cd /var/www/html

echo "DATABASE_URL=$DATABASE_URL"

# Attendre que la base de données soit disponible en utilisant PHP directement
echo "Attente de la base de données..."
timeout=60
counter=0

while [ $counter -lt $timeout ]; do
    if php -r "
        try {
            \$pdo = new PDO('$DATABASE_URL');
            echo 'Connexion réussie!';
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; then
        echo "Base de données disponible!"
        break
    fi
    
    echo "En attente... ($counter/$timeout)"
    sleep 2
    counter=$((counter + 2))
done

if [ $counter -ge $timeout ]; then
    echo "Timeout: impossible de se connecter à la base de données"
    exit 1
fi

# Test Symfony/Doctrine
echo "Test Doctrine..."
php bin/console doctrine:schema:validate --skip-sync || echo "Schéma pas encore synchronisé"

# Migrations
echo "Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || echo "Erreur migrations, on continue..."

# Cache
echo "Nettoyage du cache..."
php bin/console cache:clear --env=prod || echo "Erreur cache clear"
php bin/console cache:warmup --env=prod || echo "Erreur cache warmup"

# Permissions
echo "Configuration des permissions..."
chown -R www-data:www-data var/ 2>/dev/null || true
chmod -R 755 var/ 2>/dev/null || true

echo "Initialisation terminée avec succès !"
echo "=== FIN INIT.SH ==="

# Lancer le service principal
exec "$@"