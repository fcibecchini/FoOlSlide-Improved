FROM php:8.4-apache

RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		libfreetype6-dev \
		libjpeg62-turbo-dev \
		libpng-dev \
		libzip-dev \
		libxml2-dev \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j"$(nproc)" gd mysqli zip xml \
	&& rm -rf /var/lib/apt/lists/*

RUN echo "date.timezone = UTC" > /usr/local/etc/php/conf.d/timezone.ini \
	&& a2enmod rewrite

COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html/

RUN install -d -m 0775 -o www-data -g www-data \
	/var/www/html/content/logs \
	/var/www/html/content/cache \
	/var/www/html/content/comics \
	/var/www/html/content/tags

EXPOSE 80
