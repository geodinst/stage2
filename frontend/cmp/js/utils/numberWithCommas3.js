define(function(){
	//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
	return function(x,decimalSign,separatorSign) {
		var parts = x.toString().split(".");
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, separatorSign);
		return parts.join(decimalSign);
	};
});