FROM php:8.4-cli

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        libzip-dev \
        libicu-dev \
        libpq-dev \
        libxml2-dev \
        zlib1g-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) intl pdo pdo_pgsql xml zip gd mbstring \
 && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Mark the project directory as a safe git directory to avoid "dubious ownership" errors
RUN git config --global --add safe.directory /srv/app || true

WORKDIR /srv/app

# At container start, if vendor/ is missing run composer install, then start the built-in server
CMD sh -c 'if [ ! -d vendor ]; then composer install --no-interaction --prefer-dist --no-suggest; fi; php -S 0.0.0.0:8000 -t public'
