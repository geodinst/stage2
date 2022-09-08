define(function(){
	var module={};
	module.rgbToInt=function(r, g, b) {
		return (r << 16) + (g << 8) + b;
	};

	module.hex2rgb=function (hex) {
		var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
		return result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16),
			a: 1
		} : null;
	};

	module.pixelAndTileCoordinate=function(map,latlng){
		//var zoom=map.getZoom();
		//var scale = 1 << zoom;
		var pixelCoordinate = map.project(latlng);
		var TILE_SIZE = 256;
		var tileCoordinate = {
			x:Math.floor(pixelCoordinate.x / TILE_SIZE),
			y:Math.floor(pixelCoordinate.y / TILE_SIZE)
		};

		return {pc:pixelCoordinate,tc:tileCoordinate};
	};

	module.convertToValueText=function(a){
		var b=[];
    $.each(a,function(key,value){
      b.push({value:key,text:value});
    });
		return b;
	};

	module.detachDetachables=function($el){
		$el.find('.detachable').detach();
	};

	/**
	 *see module.get
	 */
	module.post=function(url, data, success, dataType, context){
		$.ajax({
			type: "POST",
			url: url,
			data: data,
			success: success,
			dataType: dataType,
			context:context
		});
	};

	module.button=function(text,buttonClass,iconClass){
		var $button=$('<button/>',{class:"btn",type:"button"});

		if (!buttonClass) buttonClass='btn-default';

		$button.addClass(buttonClass);

		if (iconClass){
			$span=$('<span/>',{class:iconClass,'aria-hidden':"true"});
			$button.append($span);
		}

		if (text){
			$button.append(' '+text);
		}

		return $button;
	};

	module.countDecimals = function (value) {
		if ((value % 1) !== 0)
			return value.toString().split(".")[1].length;
		return 0;
	};

	/**
	* @param {string} url
	* @param {Object} data - GET request parameters, e.g. {a:'blup',b:2}
	* @param {Function} success - onSuccess function
	* @param {string} dataType - data type, e.g. 'json' or 'text'
	* @context {jQuery DOM object} context - progress indicator is rendered to context if context has data-process attribute
	*/
	module.get=function(url, data, success, dataType, context){
		$.ajax({
			type: "GET",
			url: url,
			data: data,
			success: success,
			dataType: dataType,
			context:context
		});
	};

	module.setAjaxError=function(){
		$( document ).ajaxError(function(event, jqXHR) {
			jqXHR && jqXHR.responseText && alert(jqXHR.responseText);
		});
	};

	module.setAjaxSend=function(){

		$( document ).ajaxSend(function( event, jqxhr, settings ) {
			if (settings.context !== undefined) {
				var $el=$(settings.context);
				if ($el.data('process')!==undefined){
					$el.LoadingOverlay("show");
				}
			}
		});
	};

	module.setAjaxComplete=function(){

		$( document ).ajaxComplete(function( event, jqxhr, settings ) {
			if (settings.context !== undefined) {
				var $el=$(settings.context);
				if ($el.data('process')!==undefined){
					$el.LoadingOverlay("hide");
				}
			}
		});
	};

	module.empty=function(data){

		if(typeof(data) == 'number' || typeof(data) == 'boolean')
		{
		  return false;
		}
		if(typeof(data) == 'undefined' || data === null)
		{
		  return true;
		}
		if(typeof(data.length) != 'undefined')
		{
		  return data.length === 0;
		}
		var count = 0;
		for(var i in data)
		{
		  if(data.hasOwnProperty(i))
		  {
			return false;
		  }
		}
		return count === 0;
	};

	module.newCall=function(Cls,a,n) {
		//http://stackoverflow.com/questions/1606797/use-of-apply-with-new-operator-is-this-possible/
		return new (Function.prototype.bind.apply(Cls, [null].concat(Array.prototype.slice.call(a, n))));
		// or even
		// return new (Cls.bind.apply(Cls, arguments));
		// if you know that Cls.bind has not been overwritten
	};

	// Helper function to correctly set up the prototype chain for subclasses.
	// Similar to `goog.inherits`, but uses a hash of prototype properties and
	// class properties to be extended.
	module.extend = function(protoProps, staticProps) {
		var parent = this;
		var child;

		// The constructor function for the new subclass is either defined by you
		// (the "constructor" property in your `extend` definition), or defaulted
		// by us to simply call the parent constructor.
		if (protoProps && _.has(protoProps, 'constructor')) {
		  child = protoProps.constructor;
		} else {
		  child = function(){ return parent.apply(this, arguments); };
		}

		// Add static properties to the constructor function, if supplied.
		_.extend(child, parent, staticProps);

		// Set the prototype chain to inherit from `parent`, without calling
		// `parent`'s constructor function and add the prototype properties.
		child.prototype = _.create(parent.prototype, protoProps);
		child.prototype.constructor = child;

		// Set a convenience property in case the parent's prototype is needed
		// later.
		child.__super__ = parent.prototype;

		return child;
	};

	module.getObjectValue=function(key,object,defaultValue){
		if (defaultValue===undefined) return object[key];
		if (object[key]===undefined) {
			object[key]=defaultValue;
		}
		return object[key];
	};

	module.getHashValue=function(key) {
		var matches = location.hash.match(new RegExp(key+'=([^&]*)'));
		return matches ? matches[1] : null;
	};

	module.postSubmit=function (path, params, method) {
		//https://stackoverflow.com/questions/133925/javascript-post-request-like-a-form-submit
		method = method || "post"; // Set method to post by default if not specified.

		// The rest of this code assumes you are not using a library.
		// It can be made less wordy if you use one.
		var form = document.createElement("form");
		form.setAttribute("method", method);
		form.setAttribute("action", path);

		for(var key in params) {
			if(params.hasOwnProperty(key)) {
				var hiddenField = document.createElement("input");
				hiddenField.setAttribute("type", "hidden");
				hiddenField.setAttribute("name", key);
				hiddenField.setAttribute("value", params[key]);

				form.appendChild(hiddenField);
			 }
		}

		document.body.appendChild(form);
		form.submit();
	};

	//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
	module.numberWithCommas=function (x,separator) {
		return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, separator);
	};

	module.numberWithCommas3=function(x,decimalSign,separatorSign) {
		var parts = x.toString().split(".");
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, separatorSign);
		return parts.join(decimalSign);
	};

	module.changeContextColors=function(context,variableValues,specialValues,cba,cbac){
		var width=context.canvas.width;
		var height=context.canvas.height;

		var lngth=cba.length;

		var features=context.getImageData(0, 0, width, height).data;

			// create our target imagedata
			var output = context.createImageData(width, height);

			for(var i = 0, n = features.length; i < n; i += 4) {
				var fi=module.rgbToInt(features[i],features[i+1],features[i+2]);
				if (fi<16777215)
				{
					var value=variableValues[fi-1];
					var ki=0;
					var color=null;

					  if (value>=cba[0])
					  {
						  for (var k=1;k<lngth;k=k+2)
						  {
							  if (value<=cba[k])
							  {
								  color=cbac[ki];
								  break;
							  }
							  ki++;
						  }
						  if (color===null) color=cbac[ki-1];
					  }

					  if (color===null)
					  {
						var sv=specialValues[fi-1];
						if (sv!==undefined)
						{
							color=sv.color;
						}
					  }

					  if (color===null && $.isNumeric(value)) {
						  if (value <= cba[0]) {
							  color=cbac[0];
						  }
					  }

					  if (color!==null && color!==undefined){

						  output.data[i  ] = color.r;
						  output.data[i+1] = color.g;
						  output.data[i+2] = color.b;
						  output.data[i+3] = color.a;
					  }
				}
			}

			context.putImageData(output, 0, 0);
			return features;
	};

	module.onTileLoad=function(event,variableValues,specialValues,cba,cbac,fdata){
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

		var features=module.changeContextColors(context,variableValues,specialValues,cba,cbac);

		imgelement.setAttribute('data-PixelFilterDone', true);
		imgelement.src = canvas.toDataURL();	//triggers tileload event on this tile, but data-PixelFilterDone attribute is already set therefore context colors are not changed twice
		return features;
	};

	module.latlng2gid=function(map,glay,latlng,cache){
		
		var c=module.pixelAndTileCoordinate(map,latlng);
		var key=glay+'.'+map.getZoom();

		var cobj=cache.get(key);
		if (!cobj) return false;
		var features=cobj[c.tc.x+'.'+c.tc.y];
		if (!features) return false;

		var cp={x:Math.floor(c.pc.x-c.tc.x*256),y:Math.floor(c.pc.y-c.tc.y*256)};
		var cpi=(cp.x+cp.y*256)*4;

		var color=module.rgbToInt(features[cpi],features[cpi+1],features[cpi+2]);

		if (!color) return false;

		return color-1;
	};

	//https://github.com/osano/cookieconsent/blob/dev/src/bundle.js
	module.getCookie = name => {
		const value = ' ' + document.cookie;
		const parts = value.split(' ' + name + '=');
		return parts.length < 2 ? undefined : parts.pop().split(';').shift();
	};
	
	module.setCookie = function (name, value, expiryDays, domain, path, secure) {
		const exdate = new Date();
		exdate.setHours(exdate.getHours() + (typeof expiryDays !== "number" ? 365 : expiryDays) * 24);
		document.cookie = name + '=' + value + ';expires=' + exdate.toUTCString() + ';path=' + (path || '/') + (domain ? ';domain=' + domain : '') + (secure ? ';secure' : '');
	};

	return module;
});
