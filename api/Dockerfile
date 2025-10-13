FROM php:8.3-apache

WORKDIR /var/www/html

# Copia o código do projeto Laravel
COPY . .

# Copia configuração do Apache (ajuste o caminho se necessário)
COPY ./apache.conf /etc/apache2/sites-available/000-default.conf

RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# Instala o Composer
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

# Instala dependências do Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN a2enmod rewrite

CMD ["/var/www/html/afterbuild.sh"]
