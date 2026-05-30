FROM php:8.3-fpm

# ── UID/GID mapping : aligne www-data sur l'utilisateur hôte ──
# Passer via docker-compose (build args USER_ID / GROUP_ID).
# Défaut 1000 = utilisateur Linux standard.
ARG USER_ID=1000
ARG GROUP_ID=1000

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    nano \
    nodejs \
    npm \
    && apt-get clean

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    zip \
    exif \
    pcntl \
    intl \
    gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Remappe www-data sur l'UID/GID de l'hôte.
# Les fichiers créés dans le container seront lisibles/modifiables
# par l'utilisateur hôte sans sudo ni chmod.
RUN groupmod -g ${GROUP_ID} www-data \
 && usermod  -u ${USER_ID}  www-data

EXPOSE 9000

CMD ["php-fpm"]
