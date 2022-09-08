#!/bin/bash
cd ${CONFIG[project_root]}
sudo cp stage/client/proxy.php stage/proxy.php
# echo "Installing plupload"
# drush dl plupload
# drush en plupload -y
#
# echo "Installing plupload library"
# mkdir -p libraries/
# cd libraries
# wget -O plupload_tmp.zip https://github.com/moxiecode/plupload/archive/v2.3.6.zip
# unzip plupload_tmp.zip
# rm plupload_tmp.zip
# mv plupload-2.3.6/ ./plupload/
# rm -rf plupload/examples/
