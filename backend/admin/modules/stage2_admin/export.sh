#!/bin/bash
#DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
export PGPASSWORD=$(php ../protected/utils/dbp.php)
DB=$(php ../protected/utils/dbn.php)
DBUSER=$(php ../protected/utils/dbu.php)
export SHAPE_ENCODING="UTF-8"
if [ $4 == 0 ] ; then
    ogr2ogr -f "ESRI Shapefile" "$3.shp" PG:"host=localhost user=$DBUSER dbname=$DB" -sql "select $2,gid,ST_Force2D(ST_Transform(ST_SetSRID(geom, 4326), 3912)) from \"$1\""
else
    psql -h localhost -U $DBUSER -d $DB -c "COPY (select $2,gid from \"$1\") TO STDOUT WITH DELIMITER E'\t' CSV HEADER" > "$3.csv"
fi

#use Drupal\Core\Database\Database;
#$has_connection = (bool) Database::getConnectionInfo('default');