define(function(){
	var module={};
	module.rgbToInt=function(r, g, b) {
		return (r << 16) + (g << 8) + b;
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
			 alert(jqXHR.responseText);
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
	
	return module;
});