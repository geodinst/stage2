define(function(){
	var module={};
	var width=560;
	var height=315;
	var $iframe;
	
	/**
	 *@param $div jQuery div DOM object into which the iframe is rendered
	 *@param {Object} op initial parameters {width,height,deepLink} deepLink is a required parameter
	 */
	module.init=function($div,op){
		if (!op.deepLink) return false;
		if (op.width) width=op.width;
		if (op.height) height=op.height;
		
		$iframe=$('<iframe/>',{width:width,
								height:height,
								src:op.deepLink+'&ifrm=1',
								frameborder:"0"});
		
		$div.html($iframe);
	};
	
	module.getIframe=function(){
		return $iframe;
	};
	
	return module;
});