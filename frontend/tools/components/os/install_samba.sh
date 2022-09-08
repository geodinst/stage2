#!/bin/bash

if [ "$ENV" == "DEVELOPMENT" ];
then
    sudo apt-get install samba -y

    alter_smb_conf="[development-share]"
    if grep -Fxq "$alter_smb_conf" /etc/samba/smb.conf
    then
        echo "smb.conf already altered"
    else
        cat <<EOT >> /etc/samba/smb.conf
$alter_smb_conf
    comment = development
    path = ${CONFIG[project_root]}
    guest ok = yes
    force user = root
    browseable = yes
    read only = no
    writeable = yes
    create mask = 0777
    directory mask = 0777
    force create mode = 777
    force directory mode = 777
    force security mode = 777
    force directory security mode = 777
EOT
        echo "Altered smb.conf"
    fi

    echo "Restart Samba daemon"
    sudo service smbd restart

    echo "Set samba to boot"
    sudo update-rc.d samba defaults
fi
