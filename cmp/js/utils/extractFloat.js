define(function(){
	return function(val,decimalSign,separatorSign){
		
		if (val==='-∞'){
			return Number.NEGATIVE_INFINITY;
		}
		else if (val==='∞'){
			return Number.POSITIVE_INFINITY;
		}
		
		return parseFloat(val.replace(new RegExp("\\"+separatorSign,'g'),'').replace(new RegExp("\\"+decimalSign,'g'),'.'));
	};
});