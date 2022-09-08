<?xml version="1.0" encoding="UTF-8"?>
<StyledLayerDescriptor version="1.0.0"
    xsi:schemaLocation="http://www.opengis.net/sld StyledLayerDescriptor.xsd"
    xmlns="http://www.opengis.net/sld"
    xmlns:ogc="http://www.opengis.net/ogc"
    xmlns:xlink="http://www.w3.org/1999/xlink"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <NamedLayer>
    <Name>Simple polygon</Name>
    <UserStyle>
      <Title>SLD Cook Book: Simple polygon</Title>
      <FeatureTypeStyle>
        <Rule>
          <PolygonSymbolizer>
            <Fill>
              <CssParameter name="fill">
                <ogc:PropertyName>idgid</ogc:PropertyName>
              </CssParameter>
              <CssParameter name="fill-opacity">
                <ogc:Literal>1.0</ogc:Literal>
              </CssParameter>
            </Fill>
            <Stroke>
           <CssParameter name="stroke">#FF0000</CssParameter>
           <CssParameter name="stroke-width">1</CssParameter>
         </Stroke>
          </PolygonSymbolizer>
        </Rule>
      </FeatureTypeStyle>
    </UserStyle>
  </NamedLayer>
</StyledLayerDescriptor>