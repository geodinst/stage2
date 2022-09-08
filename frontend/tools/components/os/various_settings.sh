#!/bin/bash

if [ "$ENV" == "DEVELOPMENT" ];
then
    echo "Setting default dir"
    home_dir="cd /var/www/html"
    if grep -Fxq "$home_dir" /home/ubuntu/.profile
    then
        echo "Default dir already set"
    else
        sudo echo -e "$home_dir" >> /home/ubuntu/.profile
        echo "Default dir set"
    fi

    echo "Setting path"
    alter_path="PATH=\$PATH:/var/www/html/tools/commands"
    if grep -Fxq "$alter_path" /home/ubuntu/.profile
    then
        echo "Path already set"
    else
        sudo echo -e "$alter_path" >> /home/ubuntu/.profile
        echo "Path updated"
    fi

    echo "Set password"
    echo "ubuntu:ubuntu" | chpasswd
fi
