#!/bin/sh
set -e

echo "=== DIAGNOSTIC CONNEXION ==="
echo "Version PostgreSQL:" && php bin/console doctrine:query:sql "SELECT version()" || echo "Erreur version"

cd /var/www/html

echo "DATABASE_URL=$DATABASE_URL"

# Extraire les composants pour diagnostic
DB_HOST=$(echo "$DATABASE_URL" | sed -n 's|.*@\([^:]*\):.*|\1|p')
DB_PORT=$(echo "$DATABASE_URL" | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
DB_USER=$(echo "$DATABASE_URL" | sed -n 's|.*://\([^:]*\):.*|\1|p')
DB_NAME=$(echo "$DATABASE_URL" | sed -n 's|.*/\([^/]*\)$|\1|p')

echo "Host extraite: $DB_HOST"
echo "Port extrait: $DB_PORT"
echo "User extrait: $DB_USER"
echo "Database extraite: $DB_NAME"

# Test de résolution DNS
echo "Test de résolution DNS..."
nslookup "$DB_HOST" || echo "Erreur DNS"

# Test de connectivité réseau
echo "Test de connectivité réseau..."
nc -z "$DB_HOST" "$DB_PORT" && echo "Port accessible" || echo "Port inaccessible"

# Test avec psql si disponible
if command -v psql >/dev/null 2>&1; then
    echo "Test avec psql..."
    psql "$DATABASE_URL" -c "SELECT 1;" || echo "Erreur psql"
else
    echo "psql non disponible"
fi

# Test final avec PHP
echo "Test avec PHP/PDO..."
php -r "
try {
    \$pdo = new PDO('$DATABASE_URL');
    echo 'Connexion PHP réussie!' . PHP_EOL;
    \$stmt = \$pdo->query('SELECT version()');
    echo 'Version PostgreSQL: ' . \$stmt->fetchColumn() . PHP_EOL;
} catch (Exception \$e) {
    echo 'Erreur PHP: ' . \$e->getMessage() . PHP_EOL;
}
"

echo "=== FIN DIAGNOSTIC ==="

# Continuer avec le déploiement même en cas d'erreur
echo "Lancement du service principal..."
exec "$@"