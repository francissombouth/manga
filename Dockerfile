# --- STAGE 1 : Build des dépendances et assets ---
FROM composer:latest AS composer

FROM php:8.2-fpm-alpine AS build

# Installer les dépendances système nécessaires
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
    nodejs \
    npm

# Installer les extensions PHP nécessaires
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_pgsql \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        gd

# Installer le binaire symfony pour les auto-scripts (méthode compatible Alpine)
RUN apk add --no-cache bash && \
    wget https://get.symfony.com/cli/installer -O - | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony && \
    apk del bash

# Copier Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Autoriser composer à tourner en root (Docker)
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Copier les fichiers de dépendances
COPY composer.json composer.lock ./

# Copier le code source
COPY . .

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-ansi

# Compiler les assets Symfony (AssetMapper)
RUN php bin/console asset-map:compile --env=prod || true
RUN php bin/console importmap:install --env=prod || true
RUN php bin/console assets:install public --env=prod || true

# --- STAGE 2 : Image de production ---
FROM php:8.2-fpm-alpine AS production

# Installer les dépendances système pour la prod
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
    netcat-openbsd \
    git

# Installer les extensions PHP nécessaires
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_pgsql \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        gd

# Copier Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier la configuration PHP
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copier la configuration Nginx et Supervisor
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Copier l'application et les vendors depuis le build
COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

# Copier le script d'init
COPY docker/php/init.sh /usr/local/bin/init.sh
RUN chmod +x /usr/local/bin/init.sh

# Créer les répertoires nécessaires et permissions
RUN mkdir -p /var/www/html/var/cache \
    && mkdir -p /var/www/html/var/log \
    && mkdir -p /var/www/html/public/uploads \
    && mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/www/html/var \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod -R 755 /var/www/html/var \
    && chmod -R 755 /var/www/html/public/uploads \
    && chmod -R 755 /var/log/supervisor

# Configuration PHP supplémentaire
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/php.ini \
    && echo "max_execution_time = 60" >> /usr/local/etc/php/php.ini \
    && echo "post_max_size = 32M" >> /usr/local/etc/php/php.ini \
    && echo "upload_max_filesize = 32M" >> /usr/local/etc/php/php.ini

# Exposer le port 8080 (pour Render et local)
EXPOSE 8080

# Entrypoint
ENTRYPOINT ["/usr/local/bin/init.sh"]

# Lancer Supervisor (qui gère PHP-FPM et Nginx)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]