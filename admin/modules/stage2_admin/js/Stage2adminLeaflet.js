(function ($) {
  "use strict";
  var map = L.map('wms-map').setView([51.505, -0.09], 13);

  L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  var bounds=JSON.parse(drupalSettings.wms.extent);
  map.fitBounds([bounds.coordinates[0][0],bounds.coordinates[0][2]]);

  var proxy=drupalSettings.stage2_admin.StageGeospatialLayerEditForm.$base_url;
  proxy=location.origin+proxy+'../proxy.php';

  L.tileLayer.wms(proxy+'?', {
    layers: 'stage:'+drupalSettings.wms.tname,
    _port: drupalSettings.wms.port
  }).addTo(map);

})(jQuery);
