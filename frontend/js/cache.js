define(function(){
	var cache={};
	
	var module={};
	
	module.get=function(key,jQueryObject){
		var value=cache[key];
		if (value===undefined && jQueryObject===true){
			value=$(key);
		}
		return value;
	};
	
	module.set=function(key,value){
		cache[key]=value;
		return value;
	};
	
	module.remove=function(key){
		if (cache[key]!==undefined) delete cache[key];
	};
	
	return module;
});