#!/bin/bash
cd ${CONFIG[project_root]}
# reinstall stage2_admin modules
drush pm-uninstall stage2_admin -y
drush pm-uninstall stage2_client -y
drush en stage2_admin -y
drush en stage2_client -y
drush cache-rebuild
