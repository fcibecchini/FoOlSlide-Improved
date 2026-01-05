FROM php:5.6-apache

RUN echo "deb http://archive.debian.org/debian/ stretch main" > /etc/apt/sources.list && \
    echo "deb http://archive.debian.org/debian-security stretch/updates main" >> /etc/apt/sources.list

RUN apt-get update && apt-get install -y --no-install-recommends --allow-unauthenticated \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    zlib1g-dev \
    libzip-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd mysqli zip mcrypt xml

RUN echo "date.timezone = UTC" > /usr/local/etc/php/conf.d/timezone.ini

RUN a2enmod rewrite

COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY . /var/www/html/
COPY .htaccess /var/www/html/.htaccess

RUN mkdir -p /var/www/html/content/logs
RUN mkdir -p /var/www/html/content/cache
RUN mkdir -p /var/www/html/content/comics
RUN mkdir -p /var/www/html/content/tags

RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 777 /var/www/html

EXPOSE 80
