define(function(){
	//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
	return function(a){
		return a.every(function(e, i, a) { if (i) return e > a[i-1]; else return true; });
	};
});