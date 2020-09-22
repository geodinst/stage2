define(function(){
	//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
	return function (x,separator) {
		return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, separator);
	};
});