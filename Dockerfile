# Multi-stage build pour Symfony
FROM composer:latest AS composer

# Stage de build
FROM php:8.2-fpm-alpine AS build

# Installer les dépendances système
RUN apk add --no-cache \
    git \
    curl \
    postgresql-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    freetype-dev \
    zlib-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_pgsql \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        gd

# Copier Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de dépendances
COPY composer.json composer.lock ./

# Installer les dépendances PHP (sans scripts)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-ansi --no-scripts

# Copier le code source
COPY . .

# Copier le script d'init et le rendre exécutable
COPY docker/php/init.sh /usr/local/bin/init.sh
RUN chmod +x /usr/local/bin/init.sh

# Créer les répertoires nécessaires
RUN mkdir -p var/cache var/log public/uploads

# Exécuter les scripts post-installation après avoir copié tous les fichiers
RUN composer run-script post-install-cmd || echo "Scripts post-install failed, continuing..."

# Stage de production
FROM php:8.2-fpm-alpine AS production

# Installer les dépendances système pour la production
RUN apk add --no-cache \
    postgresql-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    freetype-dev \
    zlib-dev \
    libzip-dev \
    oniguruma-dev \
    nginx \
    supervisor \
    postgresql-client \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_pgsql \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        gd

# Copier Composer pour les tests
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les configurations
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copier le script d'init et le rendre exécutable (après le COPY . .)
COPY docker/php/init.sh /usr/local/bin/init.sh
RUN chmod +x /usr/local/bin/init.sh

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier l'application depuis le stage de build
COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

# Créer les répertoires nécessaires
RUN mkdir -p /var/www/html/var/cache \
    && mkdir -p /var/www/html/var/log \
    && mkdir -p /var/www/html/public/uploads \
    && mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/www/html/var \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod -R 755 /var/www/html/var \
    && chmod -R 755 /var/www/html/public/uploads \
    && chmod -R 755 /var/log/supervisor

# Exposer le port 8080
EXPOSE 8080

# Utiliser le script d'initialisation comme point d'entrée
ENTRYPOINT ["/usr/local/bin/init.sh"]

# Démarrer Supervisor qui gère PHP-FPM et Nginx
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"] 