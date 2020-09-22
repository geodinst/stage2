# STAGE2 #

This is a source code for the STAGE2 application as found at http://gis.stat.si/stage2/

### Requirements ###
* Drupal 8.2.7 (it doesn't work with higher versions as we are using PostgreSQL schemas different than public, cf. https://www.drupal.org/node/2868013)
* apt-get install apache2 apache2-utils postgis php libapache2-mod-php php-pgsql php-gd php-curl mercurial curl zip php-zip php-mbstring php-bcmath php-memcached #more or less :-)
* Geoserver 

### How do I get set up? ###

* install Drupal 8.2.7
* copy or link admin/modules folder to modules folder of Drupal installation
* install the modules in admin/modules folder
* the schema gets installed in stage2_admin_schema hook, however, the code in the hook is not synchronized with the updates (stage2_admin_update_N) so it's necessary to apply all database updates to the initial schema. The only problem is that initial schema might not be exactly initial and some of the updates might fail.
* in the Geoserver there is to be a postgis store with the same name as the application root pointing to the "ge" schema of the database
* the Geoserver credentials are set in STAGE2 admin
* in the Geoserver there is to be a SLD named `stage_color` with the following content:


```
<?xml version="1.0" encoding="UTF-8"?><sld:StyledLayerDescriptor xmlns="http://www.opengis.net/sld" xmlns:sld="http://www.opengis.net/sld" xmlns:gml="http://www.opengis.net/gml" xmlns:ogc="http://www.opengis.net/ogc" version="1.0.0">
  <sld:NamedLayer>
    <sld:Name>Default Styler</sld:Name>
    <sld:UserStyle>
      <sld:Name>Default Styler</sld:Name>
      <sld:Title>SLD Cook Book: Simple polygon</sld:Title>
      <sld:FeatureTypeStyle>
        <sld:Name>name</sld:Name>
        <sld:Rule>
          <sld:PolygonSymbolizer>
            <sld:Fill>
              <sld:CssParameter name="fill">
                <ogc:PropertyName>idgid</ogc:PropertyName>
              </sld:CssParameter>
            </sld:Fill>
            <sld:Stroke>
              <sld:CssParameter name="stroke">#FF0000</sld:CssParameter>
            </sld:Stroke>
          </sld:PolygonSymbolizer>
        </sld:Rule>
      </sld:FeatureTypeStyle>
    </sld:UserStyle>
  </sld:NamedLayer>
</sld:StyledLayerDescriptor>
```

* in the Geoserver there is to be another SLD named `test1` (I know, you like the name :-)) with the following content:


```
<?xml version="1.0" encoding="UTF-8"?><sld:StyledLayerDescriptor xmlns="http://www.opengis.net/sld" xmlns:sld="http://www.opengis.net/sld" xmlns:gml="http://www.opengis.net/gml" xmlns:ogc="http://www.opengis.net/ogc" version="1.0.0">
  <sld:NamedLayer>
    <sld:Name>Default Styler</sld:Name>
    <sld:UserStyle>
      <sld:Name>Default Styler</sld:Name>
      <sld:Title>SLD Cook Book: Simple polygon</sld:Title>
      <sld:FeatureTypeStyle>
        <sld:Name>name</sld:Name>
        <sld:Rule>
          <ogc:Filter>
               <ogc:PropertyIsEqualTo>
                    <ogc:PropertyName>idgid</ogc:PropertyName>
				<ogc:Function name="env">
				<ogc:Literal>idgid</ogc:Literal>
				<ogc:Literal>-1</ogc:Literal>
			</ogc:Function>
            </ogc:PropertyIsEqualTo>
          </ogc:Filter>
          <sld:PolygonSymbolizer>
            <sld:Stroke>
              <sld:CssParameter name="stroke">#FF0000</sld:CssParameter>
              <sld:CssParameter name="stroke-width">3</sld:CssParameter>
            </sld:Stroke>
          </sld:PolygonSymbolizer>
        </sld:Rule>
      </sld:FeatureTypeStyle>
    </sld:UserStyle>
  </sld:NamedLayer>
</sld:StyledLayerDescriptor>

```

* copy default_locale.js to locale.js and adjust according to your needs
* copy default_proxy.php to proxy.php and adjust according to your configuration (geoserver port ...)
