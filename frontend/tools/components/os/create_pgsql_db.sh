#!/bin/bash

# create user
if sudo -u postgres psql -t -c '\du' | cut -d \| -f 1 | grep -qw ${CONFIG[db_user]};
then
    echo "User already exists"
else
    sudo -u postgres psql -U postgres -c "CREATE USER ${CONFIG[db_user]} WITH PASSWORD '${CONFIG[db_pass]}';"
    echo "User created"
fi

# create database
if sudo -u postgres psql -lqt | cut -d \| -f 1 | grep -qw ${CONFIG[db_name]};
then
    echo "Database already exists"
else
    sudo -u postgres createdb -O ${CONFIG[db_user]} ${CONFIG[db_name]}
    sudo -u postgres psql -c "CREATE EXTENSION postgis; CREATE EXTENSION postgis_topology;" ${CONFIG[db_name]}
    echo "Database created"
fi
