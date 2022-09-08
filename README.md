# STAGE2
STAGE2 geospatial data dissemination application, cf. https://gis.stat.si/#lang=en

# Getting started

Probably the most straightforward way is to set up the Docker services as provided in the https://github.com/geodinst/stage2/blob/main/docker/docker-compose.yml file. After the Docker services are up and running you should setup the initial state of the database (https://github.com/geodinst/stage2/blob/main/backend/admin/modules/stage2_admin/stage2_admin.install should get you going) and create default workspace in the Geoserver named `stage` with the following properties:

- default workspace: stage
New postgis data store data source in the stage workspace named `stage2`:
- host: stage2-db:5432
- port 5432
- database: stage2rc
- schema: ge
- user: stage2_admin
- passwd: password



