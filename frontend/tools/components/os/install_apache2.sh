#!/bin/bash

sudo apt-get install apache2 apache2-utils -y

sudo chmod 666 /etc/apache2/sites-available/000-default.conf
sudo cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    <Directory /var/www/html>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        allow from all
    </Directory>
    DocumentRoot /var/www/html
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF
sudo chmod 644 /etc/apache2/sites-available/000-default.conf
echo "Apache2 config added"

sudo a2enmod rewrite
