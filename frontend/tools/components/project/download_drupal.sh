#!/bin/bash

# go to project root folder
cd ${CONFIG[project_root]}

# dl drupal to temp folder
sudo mkdir drupal_tmp

sudo drush dl drupal-8.2.7 --destination=drupal_tmp --drupal-project-rename=drupal_8

rsync -r drupal_tmp/drupal_8/ ${CONFIG[drupal_root]}

rm -rf drupal_tmp

#sudo chown -R www-data:www-data sites/

echo "Drupal downloaded"
