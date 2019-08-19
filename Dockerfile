FROM php:7.1.8-apache

# COPY ./src /srv/app
RUN mkdir /srv/app
#RUN
COPY ./vhost.conf /etc/apache2/sites-available/000-default.conf
COPY ./certs /etc/certs

RUN chown -R www-data:www-data /srv/app \
    && a2enmod rewrite \
    && a2enmod ssl

RUN service apache2 restart