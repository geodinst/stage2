#!/bin/bash

# echo --- Installing geonetwork ---
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"
sudo apt-get update
sudo apt-cache policy docker-ce
sudo apt-get install -y docker-ce
sudo docker pull geonetwork
sudo docker run --name geonetwork -d -p 8090:8080 -e DATA_DIR=/var/lib/geonetwork_data -v /host/geonetwork-docker:/var/lib/geonetwork_data geonetwork
#setup reverse proxy on apache
# svez ubuntu 16.04
# sudo rm /var/cache/apt/archives/*
# apt-get install vse potrebno, /var/cache/apt/archives/ se napolni z deb datotekami
# deb datoteke se skopira v en začasni direktorij na gis.stat.si
# v tem začasnem direktoriju zaženeš dpkg -i *.deb
