#!/bin/bash
gspwd=${CONFIG[geoserver_password]}
port=${CONFIG[geoserver_port]}
dbname=${CONFIG[db_name]}
dbuser=${CONFIG[db_user]}
dbpass=${CONFIG[db_pass]}

while true
do
	echo "Waiting for geoserver to strat $port"
    sleep 2
    sudo netstat -lnp | grep $port
    if [ $? -eq 0 ]; then
	echo "geoserver is now listening on port $port"
        break
    fi
done

curl -u admin:$gspwd -XDELETE http://localhost:$port/geoserver/rest/workspaces/cite.html?recurse=true
curl -u admin:$gspwd -XDELETE http://localhost:$port/geoserver/rest/workspaces/it.geosolutions.html?recurse=true
curl -u admin:$gspwd -XDELETE http://localhost:$port/geoserver/rest/workspaces/tiger.html?recurse=true
curl -u admin:$gspwd -XDELETE http://localhost:$port/geoserver/rest/workspaces/sde.html?recurse=true
curl -u admin:$gspwd -XDELETE http://localhost:$port/geoserver/rest/workspaces/topp.html?recurse=true
curl -u admin:$gspwd -XDELETE http://localhost:$port/geoserver/rest/workspaces/sf.html?recurse=true
curl -u admin:$gspwd -XDELETE http://localhost:$port/geoserver/rest/workspaces/nurc.html?recurse=true

curl -u admin:$gspwd -v -XPOST -H "Content-type: text/xml" -d "<workspace><name>stage</name></workspace>" http://localhost:$port/geoserver/rest/workspaces
curl -v -u admin:$gspwd -XPOST -H "Content-type: text/xml" -d "<dataStore><name>stage</name><connectionParameters><host>localhost</host><port>5432</port><database>$dbname</database><schema>ge</schema><user>$dbuser</user><passwd>$dbpass</passwd><dbtype>postgis</dbtype></connectionParameters></dataStore>" http://localhost:$port/geoserver/rest/workspaces/stage/datastores

curl -u admin:$gspwd -XDELETE http://localhost:$port/geoserver/rest/styles/test1?purge=true
curl -v -u admin:$gspwd -XPOST -d "<style><name>test1</name><filename>test1.sld</filename></style>" -H "Content-type: text/xml" http://localhost:$port/geoserver/rest/styles
curl -v -u admin:$gspwd -XPUT -H "Content-type: application/vnd.ogc.sld+xml" -d @/var/www/html/tools/components/os/assets/test1.sld http://localhost:$port/geoserver/rest/styles/test1

curl -u admin:$gspwd -XDELETE http://localhost:$port/geoserver/rest/styles/stage_color?purge=true
curl -v -u admin:$gspwd -XPOST -d "<style><name>stage_color</name><filename>stage_color.sld</filename></style>" -H "Content-type: text/xml" http://localhost:$port/geoserver/rest/styles
curl -v -u admin:$gspwd -XPUT -H "Content-type: application/vnd.ogc.sld+xml" -d @/var/www/html/tools/components/os/assets/stage_color.sld http://localhost:$port/geoserver/rest/styles/stage_color
