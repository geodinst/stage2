#!/bin/bash

# go to project root folder
cd ${CONFIG[drupal_root]}

# enable related modules
drush en language -y
drush en locale -y
drush en config_translation -y

# enable STAGE modules
drush en stage2_admin -y

if [ "$ENV" == "DEVELOPMENT" ];
then
    drush dl devel -y
    drush en devel -y
    drush config-set system.logging error_level 'verbose' -y
    drush config-set bartik.settings logo.use_default '0' -y
    drush config-set bartik.settings favicon.use_default '0' -y
    #drush config-set color.theme.bartik palette.top '#00396b' -y
    #drush config-set color.theme.bartik palette.bottom '#8f3c00' -y
    # TODO - bartik needs its colors rebuild by manualy submit settings form
fi

drush config-set block.block.bartik_footer status '0' -y
drush config-set block.block.bartik_powered status '0' -y
drush config-set block.block.bartik_search status '0' -y
drush config-set block.block.bartik_tools status '0' -y
drush cache-rebuild
