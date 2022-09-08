#!/bin/bash

sudo apt-get install memcached -y
sudo apt install php-memcached -y

sudo service memcached restart
sudo service apache2 restart
