#!/bin/bash

if [ "$ENV" == "DEVELOPMENT" ];
then
    sudo apt-get install libcgi-fast-perl -y
    sudo apt-get install libapache2-mod-fcgid -y
    sudo a2enmod fcgid
    sudo apt-get install munin munin-node munin-plugins-extra -y

sudo cat <<EOF > /etc/munin/apache24.conf
Alias /munin /var/cache/munin/www

<Directory /var/cache/munin/www>
    # Require local
    Require all granted
    Options FollowSymLinks SymLinksIfOwnerMatch
    Options None
</Directory>

ScriptAlias /munin-cgi/munin-cgi-graph /usr/lib/munin/cgi/munin-cgi-graph

<Location /munin-cgi/munin-cgi-graph>
    # Require local
    Require all granted
    Options FollowSymLinks SymLinksIfOwnerMatch
    <IfModule mod_fcgid.c>
        SetHandler fcgid-script
    </IfModule>
    <IfModule !mod_fcgid.c>
        SetHandler cgi-script
    </IfModule>
</Location>
EOF
    sudo service apache2 restart
else
    echo "No installation provided"
fi
