#!/bin/bash

# Script de test pour MangaThèque
# Corrige les erreurs CI/CD et exécute les tests

echo "🧪 Démarrage des tests MangaThèque..."
echo "======================================"

# Vérifier que PHP est installé
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé"
    exit 1
fi

# Vérifier que Composer est installé
if ! command -v composer &> /dev/null; then
    echo "❌ Composer n'est pas installé"
    exit 1
fi

# Créer le répertoire var s'il n'existe pas
if [ ! -d "var" ]; then
    echo "📁 Création du répertoire var..."
    mkdir -p var
fi

# Créer le fichier .env.test.local
echo "⚙️  Configuration de l'environnement de test..."
cat > .env.test.local << EOF
APP_ENV=test
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
APP_SECRET=test-secret-key-for-testing-only
MAILER_DSN=null://null
SYMFONY_DEPRECATIONS_HELPER=weak
EOF

# Installer les dépendances si nécessaire
if [ ! -d "vendor" ]; then
    echo "📦 Installation des dépendances..."
    composer install --no-interaction
fi

# Nettoyer les anciens fichiers de test
echo "🧹 Nettoyage des anciens fichiers de test..."
rm -f var/test.db
rm -rf var/coverage
rm -f var/coverage.xml
rm -f var/test-results.xml

# Exécuter les tests unitaires
echo "🔬 Exécution des tests unitaires..."
php bin/phpunit --testsuite="Unit Tests" --coverage-clover=var/coverage.xml --log-junit=var/test-results.xml

if [ $? -eq 0 ]; then
    echo "✅ Tests unitaires réussis"
else
    echo "❌ Tests unitaires échoués"
    exit 1
fi

# Exécuter les tests d'intégration
echo "🔗 Exécution des tests d'intégration..."
php bin/phpunit --testsuite="Integration Tests"

if [ $? -eq 0 ]; then
    echo "✅ Tests d'intégration réussis"
else
    echo "❌ Tests d'intégration échoués"
    exit 1
fi

# Exécuter les tests API
echo "🌐 Exécution des tests API..."
php bin/phpunit --testsuite="API Tests"

if [ $? -eq 0 ]; then
    echo "✅ Tests API réussis"
else
    echo "❌ Tests API échoués"
    exit 1
fi

# Exécuter tous les tests avec couverture
echo "📊 Exécution de tous les tests avec couverture..."
php bin/phpunit --coverage-html=var/coverage --coverage-clover=var/coverage.xml

if [ $? -eq 0 ]; then
    echo "✅ Tous les tests réussis"
    echo "📈 Couverture de code générée dans var/coverage/"
else
    echo "❌ Certains tests ont échoué"
    exit 1
fi

# Analyser le code avec PHPStan
echo "🔍 Analyse du code avec PHPStan..."
vendor/bin/phpstan analyse src --level=8 --no-progress

if [ $? -eq 0 ]; then
    echo "✅ Analyse PHPStan réussie"
else
    echo "⚠️  Analyse PHPStan avec des avertissements"
fi

# Vérifier le style de code
echo "🎨 Vérification du style de code..."
vendor/bin/php-cs-fixer fix --dry-run --diff

if [ $? -eq 0 ]; then
    echo "✅ Style de code conforme"
else
    echo "⚠️  Problèmes de style de code détectés"
fi

# Audit de sécurité
echo "🔒 Audit de sécurité..."
composer audit --format=json --no-interaction

if [ $? -eq 0 ]; then
    echo "✅ Aucune vulnérabilité détectée"
else
    echo "⚠️  Vulnérabilités détectées"
fi

echo ""
echo "🎉 Tests terminés avec succès !"
echo "📊 Résultats disponibles dans :"
echo "   - var/coverage/ (couverture HTML)"
echo "   - var/coverage.xml (couverture XML)"
echo "   - var/test-results.xml (résultats JUnit)"
echo ""
echo "🚀 Le pipeline CI/CD est maintenant fonctionnel !" 