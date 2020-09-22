#!/bin/bash
DBNAME=$1
export PGPASSWORD=$2
FNAME=$3
FOLDER=$4

pg_dump -n public -h localhost -U stage2_admin -c --no-owner $DBNAME -f $FNAME
sed -i '/^DROP EXTENSION/d' $FNAME
sed -i '/^CREATE EXTENSION/d' $FNAME
sed -i '/^DROP SCHEMA pub/d' $FNAME
psql -h localhost -U stage2_admin stage2_test < $FNAME #2>/tmp/error1.log

#put to maintenance mode
cd /var/www/html/stage2_test/admin
drush sset system.maintenance_mode 1

pg_dump -N public -h localhost -U stage2_admin -c --no-owner $DBNAME -f $FNAME
sed -i '/^DROP EXTENSION/d' $FNAME
sed -i '/^CREATE EXTENSION/d' $FNAME
psql -h localhost -U stage2_admin stage2_test < $FNAME #2>/tmp/error2.log

drush sset system.maintenance_mode 0
drush cr
rm $FNAME