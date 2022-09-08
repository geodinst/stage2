#!/bin/bash

# Get dir name of this script
P="$(dirname "$0")"

# load config
source "$P/config.sh"

# load utils
source "$P/_utils/utils.sh"

# OS provisioning
execute_component "$P/components/os/initialize.sh"

execute_component "$P/components/os/install_apache2.sh"
execute_component "$P/components/os/restart_apache2.sh"

execute_component "$P/components/os/install_postgresql.sh"
execute_component "$P/components/os/install_postgis.sh"
execute_component "$P/components/os/install_php.sh"
execute_component "$P/components/os/create_pgsql_db.sh"

execute_component "$P/components/os/install_phppgadmin.sh"
execute_component "$P/components/os/install_adminer.sh"
execute_component "$P/components/os/restart_apache2.sh"
execute_component "$P/components/os/install_memcached.sh"

execute_component "$P/components/os/install_nano.sh"
execute_component "$P/components/os/install_composer.sh"
execute_component "$P/components/os/install_drush.sh"
execute_component "$P/components/os/install_unzip.sh"
execute_component "$P/components/os/install_ntp.sh"
execute_component "$P/components/os/install_git.sh"
execute_component "$P/components/os/install_sshpass.sh"

execute_component "$P/components/os/various_settings.sh"
execute_component "$P/components/project/setup_tools.sh"

execute_component "$P/components/project/download_drupal.sh"
execute_component "$P/components/project/install_drupal.sh"
#execute_component "$P/components/project/drupal_schema_hack.sh"
#execute_component "$P/components/project/initialize_drupal.sh"
#execute_component "$P/components/project/drupal_prerequisites.sh"


execute_component "$P/components/os/install_jre8.sh"
execute_component "$P/components/os/install_geoserver.sh"
execute_component "$P/components/os/config_geoserver.sh"

execute_component "$P/components/os/install_samba.sh"
