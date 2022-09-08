#!/bin/bash
port=8081 #port="$(./next_free_port.sh 8081 8100)" #Quoting (") does matter to preserve multi-line values.
# Config JRE - still needs to be fixed
JAVA_HOME=/usr/lib/jvm/java-1.8.0-openjdk-amd64
export JAVA_HOME

sudo addgroup --system geoserver
sudo adduser --system --ingroup geoserver --no-create-home --disabled-password geoserver

cd /usr/local
echo ' '
echo --- Downloading GeoServer package - please wait ---

sudo rm tmp.zip
wget --no-check-certificate -nv -O tmp.zip http://sourceforge.net/projects/geoserver/files/GeoServer/2.9.1/geoserver-2.9.1-bin.zip

sudo unzip /usr/local/tmp.zip -d ~/.tmp_MafEtbtliG0R
sudo rm -R /opt/geoserver
sudo mv ~/.tmp_MafEtbtliG0R/geoserver-2.9.1 /opt/geoserver
sudo rm tmp.zip

sudo chown -R geoserver:geoserver /opt/geoserver
sed "s/_uid/geoserver/g" /var/www/html/tools/components/os/assets/geoserver_deb | sudo dd of=/etc/init.d/geoserver
sudo chmod +x /etc/init.d/geoserver
sed "s/_uid/geoserver/g" /var/www/html/tools/components/os/assets/geoserver_default | sudo dd of=/etc/default/geoserver
sudo sed -i "s/digest1:D9miJH\/hVgfxZJscMafEtbtliG0ROxhLfsznyWfG38X2pda2JOSV4POi55PQI4tw/plain:geoserver/g" /opt/geoserver/data_dir/security/usergroup/default/users.xml
grep -rl 8080 /opt/geoserver | sudo xargs sed -i "s/$port/8081/g"
sudo chown geoserver:geoserver /opt/geoserver/logs/
sudo cp /var/www/html/tools/components/os/assets/epsg_overrides.properties /opt/geoserver/data_dir/user_projections/
sudo chown geoserver:geoserver /opt/geoserver/data_dir/user_projections/epsg_overrides.properties
sudo update-rc.d geoserver defaults
sudo /etc/init.d/geoserver start

count=0

while true
do
    sleep 4
    sudo netstat -lnp | grep 8080
    if [ $? -eq 0 ]; then
		echo "geoserver is now listening on port 8080"
		break
	elif [ $count -le 10 ]; then
		echo "Geoserver faild to start"
		break

	else
		echo "Waiting for geoserver to start ..."
		(( count++ ))
    fi
done
