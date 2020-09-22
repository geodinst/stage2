define(['../lib/geostats.min','../lib/colorbrewer.v1.min','./utils/isMonotonicallyIncreasing','./utils/countDecimals','./utils/hex2rgb'],function(geostats,colorbrewer,_isMonotonicallyIncreasing,_countDecimals,_hex2Rgb){
	"use strict";
	var module={};

	/**
	 * Converts class breaks to legend spans.
	 * e.g.: [44222, "72494", "142483", "259726", "323119", 533213] -> [44222, 72494, 72495, 142483, 142484, 259726, 259727, 323119, 323120, 533213]
	 * @param {Array} tcb class breaks as obtained from getClassBreaks or autoClass function
	 * @param g legend resolution (number of decimal places to the next class span)
	 */
	function cb2legend(tcb,g)
	{
		g=Math.pow(10,-g);
		if (tcb.length===0) return tcb;
		var cb=tcb.slice(0);
		var _cb=[];
		var cbinx=0;

		var first=parseFloat(cb.shift());

		for (var i=0;cbinx<cb.length;++i)
		{
			if (i%2===0)
				_cb.push(parseFloat(cb[i-(cbinx++)]));
			else
				_cb.push(parseFloat(_cb[i-1])+g);
		}

		_cb.unshift(first);

		return _cb;
	}

	/**
	 * Returns class breaks only - without calling the checkClassBreaks function
	 * @param {Array} fvval numerical array of variable values to get the class breaks for
	 * @param method 1=quantiles,2=equal intervals,4=jenks natural breaks
	 * @param nc number of classes
	 *
	 */
	function autoClass(fvval,method,nc){
		var gs=new geostats(fvval);
		var cb=[];
		try
		{
			if (method==1)
				cb=gs.getQuantile(nc);
			else if (method==2)
				cb=gs.getEqInterval(nc);
			else if (method==4)
				cb=gs.getJenks(nc);
		}
		catch(err)
		{
			cb=[];
		}
		return cb;
	}

	/**
	 * Checks if class breaks are monotonically increasing and if not it iterates the method and number of classes parameter to get a monotonically increasing class breaks.
	 */

	function checkClassBreaks(fvval,cb,nc,g,method){
		var _cb;
		if (cb.length>0)
		{
			_cb=cb2legend(cb,g);
			if (_isMonotonicallyIncreasing(_cb)) {
				return {cb:cb,nc:nc,m:method};
			}
		}

		var methods=[1,2,4];
		var index = methods.indexOf(method);
		if (index > -1) methods.splice(index, 1);
		methods.unshift(method);

		for (var i=0;i<methods.length;++i)
		{
			method=methods[i];
			for (var tnc=nc-1;tnc>0;tnc--) {
				var ccb=autoClass(fvval,method,tnc);
				_cb=cb2legend(ccb,g);
				if (_cb.length>0)
				{
					if (_isMonotonicallyIncreasing(_cb)) {
						return {cb:ccb,nc:tnc,m:method};
					}
				}
			}
		}
		return false;
	}

	module.cb2legend=function(tcb,g){
		return cb2legend(tcb,g);
	};

	/**
	 * Returns Object {cb,colors} where cb = class breaks using checkClassBreaks function and colors= Array of rgba objects {r,g,b,a};
	 * the length of cb array equals the length of colors array + 1
	 * e.g.: {cb: [1,2,3],colors:[{r:254,g:240,b:217,a:1},{r:3,g:240,b:217,a:1}]}
	 * @param {Array} fvval numerical array of variable values to get the class breaks for
	 * @param {Object} prop properties {cm:classification method, cb: class breaks, cp: color palette}
	 */
	module.getClassBreaks=function(fvval,prop){
		var gs=new geostats(fvval);
		var method=parseInt(prop.cm);
		var nc=parseInt(prop.cb);
		var g=1;	// number of decimal places - used for legend resolution in function cb2legend (see above); TODO: pass number of decimal places as a function parameter
		var cb=[];
		try{
			if (method==1)
				cb=gs.getQuantile(nc);
			else if (method==2)
				cb=gs.getEqInterval(nc);
			else if (method==4)
				cb=gs.getJenks(nc);
		}
		catch(err){
			cb=[];
		}

		var cbr=checkClassBreaks(fvval,cb,nc,g,method);
		if (cbr!==false){
			cbr.colors=module.getColorPalette(prop.cp,cbr.nc);
		}

		return cbr;
	};
	
	/**
	 * Returns color from the input color array that maps to the passed value according to class breaks
	 * @param {Number} a value from the interval [cba[0],cba[cba.length-1]]
	 * @param {Array} cba array of class breaks, e.g. [367, 2425, 2426, 3951, 3952, 6108, 6109, 12213, 12214, 288919]
	 * @param {Array} cbac array of colors, e.g. [{r:254,g:240,b:217,a:255},{r:3,g:240,b:217,a:255},...]
	 */

	module.getColorFromValue=function(value,cba,cbac){
		var ki=0;
		var lngth=cba.length;
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
		
		if (color===null && $.isNumeric(value)) {
			if (value <= cba[0]) {
				color=cbac[0];
			}
		}
		
		return color;
	};
	
	module.getColorPalette=function(cp,nc){
		var nc_real=nc;
		var cp_length=parseInt(_.max(_.keys(colorbrewer[cp])));
		if (nc<3) {
			nc=3;
		}
		else if (nc>cp_length){
			nc=cp_length;
		}
		
		var colors=colorbrewer[cp][nc].slice(0);
		for (var i=0,len=colors.length;i<len;++i){
			colors[i]=_hex2Rgb(colors[i]);
		}
		
		if (nc_real==2){
			colors.splice(1,1);
		}
		else if (nc_real==1){
			colors.splice(1,2);
		}
		
		return colors;
	};
	
	module.preprocessValues=function(variableValues,prop){
		var lvalues=[];
		var g=0;	//the number of decimal places
		variableValues.forEach(function(val){
			if (!isNaN(val)){
				if (prop.decimals===undefined){
					var gtest=_countDecimals(val);
					if (gtest>g) g=gtest;
				}
				lvalues.push(val);
			}
		});
		
		if (prop.decimals===undefined) prop.decimals=g;
		
		return lvalues;
	};

	return module;
});
