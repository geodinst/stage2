#!/bin/bash

sudo apt-get install php php-zip php-gd php-mbstring libapache2-mod-php php-curl php-pear php7.0-dev php-xml -y

# install dbase
sudo pecl install dbase-7.0.0beta1
sudo touch /etc/php/7.0/mods-available/dbase.ini
sudo chmod 666 /etc/php/7.0/mods-available/dbase.ini
sudo cat <<EOF > /etc/php/7.0/mods-available/dbase.ini
extension=dbase.so
EOF
sudo chmod 644 /etc/php/7.0/mods-available/dbase.ini
sudo phpenmod dbase
echo "Dbase module installed"

# alter PHP settings
sudo touch /etc/php/7.0/mods-available/project_settings.ini
sudo chmod 666 /etc/php/7.0/mods-available/project_settings.ini
sudo cat <<EOF > /etc/php/7.0/mods-available/project_settings.ini
max_input_vars = 10000
post_max_size = 128M
upload_max_filesize = 128M
memory_limit = 512M
max_execution_time = 3000
error_reporting = E_ALL
display_errors = on
display_startup_errors = on
EOF
sudo chmod 644 /etc/php/7.0/mods-available/project_settings.ini
sudo phpenmod project_settings
echo "PHP configuration added"
