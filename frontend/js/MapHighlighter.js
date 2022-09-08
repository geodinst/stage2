define(['js/controller'],function(controller){
	"use strict";
	var Cons=function(map,glay,op){
		this.gidSelectors={};
		this.style='test1';
		this.map=map;
		this.glay=glay;
		this.op=op;
		if(!this.op) this.op={};
	};
	
	Cons.prototype.showBorder=function(gid,hidePreviouslySelected){
		if (gid===undefined) return;
		var layer=this.gidSelectors[gid];
		if (!layer){
			layer=this.gidSelectors[gid]=L.tileLayer.wms('proxy.php?', {
				layers: 'stage:'+this.glay,
				styles: this.style,
				env:'idgid:'+(gid+1),
				transparent:'true',
				format:'image/png',
				_port:controller.port
			});
		}
		
		layer.addTo(this.map).bringToFront();
		if (this.op.onShow) this.op.onShow(gid);
		if (hidePreviouslySelected===true && this.selected) this.selected.remove();
		this.selected=layer;
	};
	
	Cons.prototype.hideBorder=function(gid){
		if (gid===undefined) return;
		var layer=this.gidSelectors[gid];
		if (!layer) return;
		layer.remove();
		if (this.op.onHideBorder) this.op.onHideBorder(gid);
	};
	
	Cons.prototype.toggle=function(gid){
		if (gid===undefined) return;
		var layer=this.gidSelectors[gid];
		if (!layer) {
			this.showBorder(gid);
		}
		else{
			if (this.map.hasLayer(layer)) {
				this.hideBorder(gid);
			}
			else{
				this.showBorder(gid);
			}
		}
	};
	
	Cons.prototype.hideAll=function(){
		for (var key in this.gidSelectors){
			var layer=this.gidSelectors[key];
			layer.remove();
		}
		delete this.selected;
	};
	
	return Cons;

});