#!/bin/bash

##############################
# declare environment groups #
##############################
# used in commands - to affect multiple environments at once
# only enviroments declared in assoc array will be available in commands
declare -A COMMANDS_ENVIRONMENT_GROUPS=(
    ["DEVELOPMENT"]="DEVELOPMENT"
    ["TEST"]="TEST"
)

########################
# declare environments #
########################
# DEVELOPMENT
declare -A DEVELOPMENT=(
    ["host"]="127.0.0.1"
    ["project_root"]="/var/www/html"
    ["user"]="ubuntu"
    ["pass"]="ubuntu"

    # database
    ["postgres_pass"]="password"
    ["db_name"]="drupal"
    ["db_user"]="drupal"
    ["db_pass"]="drupal"

    # drupal params
    ["account_name"]="admin"
    ["account_pass"]="admin"
    ["site_mail"]="mail@mail.com"
    ["site_name"]="STAGE"
    ["drupal_root"]="admin"

    #geoserver params
    ["geoserver_password"]="geoserver"
    ["geoserver_port"]="8080"

)

# TEST
declare -A TEST=(

)
