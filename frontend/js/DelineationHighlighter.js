define(['js/utils','js/controller'],function(U,controller){
	"use strict";
	var Cons=function(map,glay,op){
		this.map=map;
		this.glay=glay;
		this.op=op;
      this.color={r:128,g:128,b:128,a:128};
		if(!this.op) this.op={};
	};

	Cons.prototype.highlight=function(gids){
		if (this.layer!==undefined) this.layer.remove();

      if (gids===undefined || gids.length===0) return;

      this.layer=L.tileLayer.wms('proxy.php?', {
        layers: 'stage:'+this.glay,
        format_options:'antialias:none',
        styles: 'stage_color',
		  _port: controller.port,
        format:'image/png'
      });

      var _gids={};

      for (var i=0,n=gids.length;i<n;++i){
         _gids[gids[i]]=true;
      }

      var self=this;
      this.layer.on('tileload', function(event) {
         onTileLoad(event,self.color,_gids);
      });

		this.layer.addTo(this.map).bringToFront();
	};

   Cons.prototype.setLayer=function(glay){
      this.glay=glay;
   };

	Cons.prototype.remove=function(){
		if(this.layer!==undefined) {
			this.layer.remove();
			delete this.layer;
		}
	};

   function onTileLoad(event,color,gids){
		var imgelement=event.tile;
		if (imgelement.getAttribute('data-PixelFilterDone')) return;

		// copy the image data onto a canvas for manipulation
		var width  = imgelement.width;
		var height = imgelement.height;
		var canvas    = document.createElement("canvas");
		canvas.width  = width;
		canvas.height = height;
		var context = canvas.getContext("2d");
		context.drawImage(imgelement, 0, 0);

		var features=changeContextColors(context,color,gids);

		imgelement.setAttribute('data-PixelFilterDone', true);
		imgelement.src = canvas.toDataURL();
		return features;
	}


   function changeContextColors(context,color,gids){
		var width=context.canvas.width;
		var height=context.canvas.height;

		var features=context.getImageData(0, 0, width, height).data;

      // create our target imagedata
		var output = context.createImageData(width, height);

      for(var i = 0, n = features.length; i < n; i += 4) {

         if (features[i]>250 && features[i+1]===0) {
            output.data[i]=84;output.data[i+1]=84;output.data[i+2]=84;output.data[i+3]=255;
         }
         else{
            var fi=U.rgbToInt(features[i],features[i+1],features[i+2]);

            if (fi<16777215 && gids[fi]!==undefined)
            {
               output.data[i  ] = color.r;
               output.data[i+1] = color.g;
               output.data[i+2] = color.b;
               output.data[i+3] = color.a;
            }
         }
      }

    context.putImageData(output, 0, 0);
    return features;
   }

	return Cons;

});
