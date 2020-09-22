define(function(){
	//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
	return function findMinMax(values) {
		var mm={min: values[0],max: values[0]};
		for (var i=1,c=values.length;i<c;++i){
		  if (values[i]<mm.min) mm.min=values[i];
		  if (values[i]>mm.max) mm.max=values[i];
		}
		return mm;
	};
});