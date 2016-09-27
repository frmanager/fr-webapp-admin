#!/usr/bin/env bash

echo "SETTING UP LRESPTO_FUNRUN APP"


#WE ARE SETTING UP A TRASHABLE DEVELOPMENT ENVIRONMENT.....THIS IS NOT "INSECURE"
##########################
#     DATABASE CONFIG    #
##########################
database_host="localhost"
database_db="homestead"
database_user="homestead"
database_password="secret"

##########################
#       ADMIN USER       #
##########################
app_username="davidlarrimore"
app_password="secret"
app_email="davidlarrimore@gmail.com"

#GETTING TO THE CORRECT DIRECTORY
cd /home/vagrant/projects/funrun-symfony2

#CLEARING CAHS
php bin/console cache:clear --env=prod --no-debug

#SETTING UP DATABASE ENTETTIES
php bin/console doctrine:schema:update --force

php bin/console fos:user:create $app_username $app_email $app_password
php bin/console fos:user:promote $app_username --super

mysql --user=$database_user --password=$database_password --database=$database_db -e "source scripts/database_bootstrap.sql"
