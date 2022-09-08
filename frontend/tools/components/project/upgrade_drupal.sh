#!/bin/bash
# go to project root folder
cd /var/www/html/${CONFIG[drupal_root]}
sudo drush up drupal-y
