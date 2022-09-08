#!/bin/bash

if hash drush 2>/dev/null; then
    echo "Drush is already installed"
else
    COMPOSER_HOME=/opt/drush COMPOSER_BIN_DIR=/usr/local/bin COMPOSER_VENDOR_DIR=/opt/drush/8 composer require drush/drush:8.*
    cd /opt/drush/8/drush/drush
    sudo composer update
    echo "Drush installed"
fi
