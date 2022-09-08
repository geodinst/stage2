#!/bin/bash

sudo apt-get install postgresql libpq5 postgresql-9.5 postgresql-client-9.5 postgresql-client-common postgresql-contrib -y

sudo -u postgres psql -U postgres -d postgres -c "ALTER USER postgres WITH PASSWORD '${CONFIG[postgres_pass]}';"
echo "Password for postgres user updated"

if [ "$ENV" == "DEVELOPMENT" ];
then
    alter_hba="host all all 0.0.0.0/0 md5"
    if grep "$alter_hba" /etc/postgresql/9.5/main/pg_hba.conf > /dev/null
    then
        echo "pg_hba.conf already altered"
    else
        echo "$alter_hba" >> /etc/postgresql/9.5/main/pg_hba.conf
        echo "Altered pg_hba.conf"
    fi

    # create project settings
    sudo touch /etc/postgresql/9.5/main/project_settings.conf
    sudo chmod 666 /etc/postgresql/9.5/main/project_settings.conf
    sudo cat <<EOF > /etc/postgresql/9.5/main/project_settings.conf
# DB Version: 9.5
# OS Type: linux
# DB Type: web
# Total Memory (RAM): 2 GB
# Number of Connections: 15

listen_addresses = '*'
max_connections = 100
shared_buffers = 512MB
effective_cache_size = 1536MB
work_mem = 104857kB
maintenance_work_mem = 128MB
min_wal_size = 1GB
max_wal_size = 2GB
checkpoint_completion_target = 0.7
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 4
EOF
    sudo chmod 644 /etc/postgresql/9.5/main/project_settings.conf

    # alter postgres settings
    alter_conf="include 'project_settings.conf'"
    if grep "$alter_conf" /etc/postgresql/9.5/main/postgresql.conf > /dev/null
    then
        echo "project_settings.conf already added"
    else
        echo "$alter_conf" >> /etc/postgresql/9.5/main/postgresql.conf
        echo "Added project_settings.conf to postgresql.conf"
    fi

    sudo service postgresql restart
fi
