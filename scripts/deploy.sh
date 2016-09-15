#!/bin/bash

export BOWERPHP_TOKEN="$1"

export PATH=/usr/local/php56/bin:$PATH

export PATH=/home/$USER/.php/composer:$PATH

export SYMFONY_ENV=prod


#git pull

composer self-update

composer install --no-dev --optimize-autoloader

php bin/console cache:clear --env=prod --no-debug

php bin/console doctrine:schema:update --force

vendor/bin/bowerphp install

#FIN
