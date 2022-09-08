# STAGE2
STAGE2 geospatial data dissemination application, cf. https://gis.stat.si/#lang=en

# Requirements
- Drupal 8 (last version known to work with this code is included to this repository in the folder [backend/admin](https://github.com/geodinst/stage2/tree/main/backend/admin)
- Geoserver (the docker-compose is using image provided from https://github.com/kartoza/docker-geoserver)
- Postgresql with PostGIS (we are using postgis/postgis:10-2.5 image https://registry.hub.docker.com/r/postgis/postgis/)
- Memcached (https://registry.hub.docker.com/_/memcached)

# Getting started

Probably the most straightforward way is to set up the Docker services as provided in the [docker-compose.yml](https://github.com/geodinst/stage2/blob/main/docker/docker-compose.yml) file. After the Docker services are up and running you should setup the initial state of the database for the Drupal8 and provided modules ([stage2_admin.install](https://github.com/geodinst/stage2/blob/main/backend/admin/modules/stage2_admin/stage2_admin.install) should get you going) and create default workspace in the Geoserver named `stage` with the following properties:

- default workspace: stage
New postgis data store data source in the stage workspace named `stage2`:
- host: stage2-db:5432
- port 5432
- database: stage2rc
- schema: ge
- user: stage2_admin
- passwd: password



