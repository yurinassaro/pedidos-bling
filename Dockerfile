FROM php:8.2-fpm-alpine

# Instalar dependencias do sistema (incluindo WebP)
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    oniguruma-dev \
    libzip-dev \
    icu-dev \
    && rm -rf /var/cache/apk/*

# Configurar e instalar extensoes PHP (com suporte WebP)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    gd \
    zip \
    opcache \
    bcmath \
    intl

# Copiar composer do container oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar diretorio de trabalho
WORKDIR /var/www/html

# Copiar arquivos do projeto
COPY . .

# Instalar dependencias do Composer (producao)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configurar permissoes
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configurar PHP para producao
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configurar OPcache para producao
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Aumentar limites PHP
RUN echo "upload_max_filesize=20M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Expor porta do PHP-FPM
EXPOSE 9000

# Comando padrao
CMD ["php-fpm"]
