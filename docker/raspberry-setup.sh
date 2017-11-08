#!/bin/bash

# setup raspberry env

set -e

# add php7.1 repository
echo "deb http://mirrordirector.raspbian.org/raspbian/ buster main contrib non-free rpi" > /etc/apt/sources.list.d/php7-1.list

# install packages
apt-get update && apt-get upgrade && apt-get install -y \
  nginx \
  git \
  mysql-server \
  php7.1 \
  php7.1-fpm \
  php7.1-mysql \
  php7.1-curl \
  php7.1-intl \
  php7.1-json \
  php7.1-xml \
  php7.1-zip 

# update php conf
sed -i "s/;date.timezone =.*/date.timezone = Europe\/Paris/" /etc/php/7.1/fpm/php.ini
sed -i "s/;memory_limit =.*/memory_limit = 128/" /etc/php/7.1/fpm/php.ini
sed -i "s/;\s*max_input_vars =.*/max_input_vars = 10000/" /etc/php/7.1/fpm/php.ini
sed -i 's/error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT/error_reporting = E_ALL/g' /etc/php/7.1/fpm/php.ini
sed -i "s/display_errors = Off/display_errors = On/" /etc/php/7.1/fpm/php.ini

# install composer
curl -k -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# install project
mkdir /var/www/web
cd /var/www/web

git clone https://github.com/binary010100/prayer-times-v3.git

cd prayer-times-v3

chmod 777 -r *
composer install

cp docker/vhost /etc/nginx/sites-enabled/default
service nginx restart



