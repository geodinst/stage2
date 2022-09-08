#!/bin/bash

# download and enable drush language
drush dl drush_language -y
drush en drush_language -y

# import main project language
drush language-import ${CONFIG[project_root]}/modules/mop/translations/sl.po --langcode=sl

# rebuild cache
drush cr
