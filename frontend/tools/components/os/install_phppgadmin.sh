#!/bin/bash

sudo apt-get install phppgadmin -y

# alter settings
sudo chmod 666 /etc/apache2/conf-available/phppgadmin.conf
sudo cat <<EOF > /etc/apache2/conf-available/phppgadmin.conf

Alias /phppgadmin /usr/share/phppgadmin

<Directory /usr/share/phppgadmin>

<IfModule mod_dir.c>
DirectoryIndex index.php
</IfModule>
AllowOverride None

# Only allow connections from localhost:
# Require local
Allow From all

<IfModule mod_php.c>
  php_flag magic_quotes_gpc Off
  php_flag track_vars On
  #php_value include_path .
</IfModule>
<IfModule !mod_php.c>
  <IfModule mod_actions.c>
    <IfModule mod_cgi.c>
      AddType application/x-httpd-php .php
      Action application/x-httpd-php /cgi-bin/php
    </IfModule>
    <IfModule mod_cgid.c>
      AddType application/x-httpd-php .php
      Action application/x-httpd-php /cgi-bin/php
    </IfModule>
  </IfModule>
</IfModule>

</Directory>

EOF
sudo chmod 644 /etc/apache2/conf-available/phppgadmin.conf
echo "Apache2 configuration altered"
