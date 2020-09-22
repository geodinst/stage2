define(function(){
	/**
	 *@param $el jQuery DOM element into which the select box is rendered
	 *@param {Array} data select box data, Array of objects {id,text} or {value,text}
	 *@param badge select box title, rendered above select box as <span class="badge">param badge value</span>
	 *@param op options, currently supported options are: onSelect, multiple, selectedValue
	 * onSelect: function  executed on the select event
	 * multiple: true to enable multiple selection
	 * selectedValue: initially selected value
	 */
	return function($el,data,badge,op){
		if (!($el instanceof jQuery)){
			op=badge;
			badge=data;
			data=$el;
			$el=$('<div/>');
		}
		$el.empty();
		if (op===undefined) op={};
		if (badge){
			$el.append('<span class="badge">'+badge+'</span><br>');
		}
		
		var key;
		
		if (data.key!==undefined){
			key=data.key;
			data=data.data;
		}
		
		var $select=$('<select/>');
		var options="";
		for (var i=0,len=data.length;i<len;++i){
			var v=data[i];
			var val=v;
			var vtext=v;
			
			if (v.value!==undefined) {
				val=v.value;
			}
			else if (v.id!==undefined) {
				val=v.id;
			}
			
			if (v.text!==undefined) vtext=v.text;
			options+='<option value="'+val+'">'+vtext+'</option>';
		}
		$select.html(options);
		
		if (op.multiple===true){
			$select.attr('multiple',"multiple");
		}
		
		$el.append($select);
		
		var onSelect=op.onSelect;
		if (onSelect) {
			$select.off('change');
			$select.on('change',function(){
				onSelect($(this).val());
			});
		}
		
		var selectedValue=op.selectedValue;
		
		if (selectedValue!==undefined) {
			$select.val(selectedValue).trigger("change");
		}
		
		this.emptyElement=function(){
			$el.empty();
		};
		this.getDataKey=function(){
			return key;
		};
		this.val=function(a,silent){
			if (a!==undefined){
				$select.val(a);
				if (!silent) $select.trigger("change");
			}
			return $select.val();
		};
		
		this.getData=function(){
			//not implemented yet
		};
		
		this.getTextFromID=function(id){
			return $select.find('option[value="'+id+'"]').text();
		};
		
		this.$el=function(){
			return $el;
		};
		
		this.selectFirstItem=function(){
			$select.val(null);
			var op=$select.find('option').first();
				op.prop('selected',true);
			$select.trigger('change');
		};
		
		this.resetOnSelect=function(fun){
			onSelect=fun;
		};
		
		this.reinit=function(data,textf){
			if (data.length===0) return false;
			if (textf===undefined) textf='text';
			var valf='id';
			if (data[0][valf]===undefined) valf=textf;
			var $tmpSelect=$('<select/>');
			$.each(data,function(key,op){
				$tmpSelect.append($('<option/>',{value:op[valf]}).text(op[textf]));
			});
			$select.html($tmpSelect.html());
			return true;
		};
		
		if (op.initialValue!==undefined){
			this.val(op.initialValue);
		}
		
		this.enable=function(enable){
			$select.prop('disabled',!enable);	
		};

		this.setByText = function(text){
			var op=$select.find('option').filter(function () { return $(this).html() == text; }).val();
			if (op!==undefined){
				$select.val(op).trigger('change');
			}
		};
	};
});