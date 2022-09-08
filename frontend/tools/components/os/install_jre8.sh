# Install JRE for GeoServer
echo ' '
echo --- Installing JRE ---
sudo add-apt-repository ppa:openjdk-r/ppa -y
sudo apt-get update
sudo apt-get install -y openjdk-8-jre -y
# sleep 20s
