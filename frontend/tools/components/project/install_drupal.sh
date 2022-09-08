#!/bin/bash
# go to project root folder
cd /var/www/html/${CONFIG[drupal_root]}

sudo drush site-install standard --account-name="${CONFIG[account_name]}" --account-pass="${CONFIG[account_pass]}" --site-mail="${CONFIG[site_mail]}" --site-name="${CONFIG[site_name]}" --db-url=pgsql://${CONFIG[db_user]}:${CONFIG[db_pass]}@127.0.0.1/${CONFIG[db_name]} install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL -y
sudo chown -R www-data:www-data sites/
echo "Site installed"
