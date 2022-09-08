#!/bin/bash
cd ${CONFIG[project_root]}
sudo rm /var/www/html/stage/admin/core/lib/Drupal/Core/Database/Driver/pgsql/Schema.php
sudo cp tools/components/project/assets/Schema.php /var/www/html/stage/admin/core/lib/Drupal/Core/Database/Driver/pgsql/Schema.php                                             