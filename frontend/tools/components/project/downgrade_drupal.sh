#!/bin/bash
# go to project root folder
cd /var/www/html/${CONFIG[drupal_root]}
sudo drush pm-update drupal-8.2.7 -y
sudo drush cr
echo "finished"
