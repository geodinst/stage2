define(function(){
	//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
	return function (value) { 
		if ((value % 1) !== 0) 
			return value.toString().split(".")[1].length;  
		return 0;
	};
});