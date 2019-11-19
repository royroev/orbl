FROM wordpress:5.3-php7.3-apache

COPY wp-content /var/www/html/wp-content

COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini
