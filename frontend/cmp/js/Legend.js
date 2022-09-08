define(['./giss','./Table','./utils/numberWithCommas3','./ModalDialog','./utils/extractFloat','./utils/findMinMax'],function(giss,Table,numberWithCommas3,ModalDialog,extractFloat,findMinMax){
	"use strict";
	if (!$) $=jQuery;

	var or_less,or_more;

	var lessOrEqual = function (f1, f2, eps = 1e-6) {
		if (Math.abs(f1-f2) < eps) return true;
		
		if (f1 <= f2) return true;

		return false;
	}

	var format=function (v,dc,decimalSign,separatorSign){
		if (v===undefined) return;

		if (v===Number.NEGATIVE_INFINITY){
			return '-'+'∞';
		}
		else if (v===Number.POSITIVE_INFINITY){
			return '∞';
		}

		return numberWithCommas3(parseFloat(v).toFixed(dc),decimalSign,separatorSign);
	};

	var _format=function(v,prop){
		return format(v,prop.decimals,prop.decimalSign,prop.separatorSign);
	};

	var prepareItems=function(cba,cbac,prop,categorize){

		if (cba.length<2) return [];
		var ci=0;
		var c1;
		var items=[];
		$.each(cba,function(i,v){
			var item={};
			if (i%2===0){
				c1=v;
			}
			else{
				item.lvalue=c1;
				item.rvalue=v;
				item.cbaInx=i-1;

				if (categorize===true){
					item.text=_format(c1,prop);
				}
				else{
					item.text=_format(c1,prop)+'&nbsp;–&nbsp;'+_format(v,prop);
				}

				item.color=cbac[ci++];
				items.push(item);
			}
		});

		if (categorize && prop.cb.legend_or_less!==undefined) or_less=prop.t['or less'];
		if (categorize && prop.cb.legend_or_more!==undefined) or_more=prop.t['or more'];

		items[0]={text:_format(cba[1],prop)+' '+or_less,color:cbac[0]};
		items[0].lvalue=cba[0];
		items[0].rvalue=cba[1];
		items[0].cbaInx=0;

		var lastInx=items.length-1;
		items[lastInx]={text:_format(cba[cba.length-2],prop)+' '+or_more,color:cbac[cbac.length-1]};
		items[lastInx].lvalue=cba[cba.length-2];
		items[lastInx].rvalue=cba[cba.length-1];
		items[lastInx].cbaInx=cba.length-2;
		return items;
	};

	/**
	 * Creates a legend.
	 * @param {Array} cba legend class regions, e.g. legend with 3 entries "1 or less","2-3","4 or more" is represented as [0,1,2,3,4,5]
	 * @param {Array} cbac legend colors represented as rgba quadruplets, e.g. the legend with two entries (class ranges):[{r:254,g:240,b:217,a:1},{r:3,g:240,b:217,a:1}]. The length of this array should equal cba.length/2
	 */
	var Legend=function(variableValues,prop,op){
		if (!op) op={};

		prop.cm=parseInt(prop.cm);
		if (!prop.t) {
			prop.t={'.':'.',',':',','or less':'or less','or more':'or more','Input a value from the closed interval':'Input a value from the closed interval','Invalid value. Please check if the value falls in the respective interval!':'Invalid value. Please check if the value falls in the respective interval!',
			'Input a value from the interval':'Input a value from the interval'
			};
		}
		
		if (op.t!==undefined) prop.t=op.t;

		function t(key){
			if (prop.t[key]===undefined){
				console.log('Missing translation: '+key);
				return key;
			}

			return prop.t[key];
		}

		or_less=prop.t['or less'];
		or_more=prop.t['or more'];

		if (!prop.decimalSign) prop.decimalSign=prop.t['.'];
		if (!prop.separatorSign) prop.separatorSign=prop.t[','];

		var lvalues=giss.preprocessValues(variableValues,prop);
		var accuracy=parseFloat((Math.pow(10,-prop.decimals)).toFixed(prop.decimals));
		
		if ((variableValues.length>0 && variableValues.length<3) || (lvalues.length>0 && lvalues.length<3) || prop.cm===8) {
			or_less='';
			or_more='';
			op.edit=false;
		}

		var cbs;
		var cba;
		var cbac;

		if (prop.cm===0 || prop.cba || (variableValues.length>0 && variableValues.length<3) || (lvalues.length>0 && lvalues.length<3) || prop.cm===8){
			var mm;
			if (!prop.cba){
				prop.cba=[];

				if (variableValues.length<3 || lvalues.length<3){
					mm=findMinMax(lvalues);
				}
				else{
					mm={min:Number.NEGATIVE_INFINITY, max:Number.POSITIVE_INFINITY};
				}

				if (prop.cm===8){
					var legend_or_less=prop.cb.or_less;
					var legend_or_more=prop.cb.or_more;
					if (legend_or_less!==undefined && $.isNumeric(legend_or_less)){
						legend_or_less=parseFloat(legend_or_less);
						or_less=prop.t['or less'];
						prop.cb.legend_or_less=legend_or_less;
						prop.cba.push(Number.NEGATIVE_INFINITY, legend_or_less);
					}
					else{
						legend_or_less=Number.NEGATIVE_INFINITY;
					}

					if (legend_or_more!==undefined && $.isNumeric(legend_or_more)){
						or_more=prop.t['or more'];
						legend_or_more=parseFloat(legend_or_more);
						prop.cb.legend_or_more=legend_or_more;
					}
					else{
						legend_or_more=Number.POSITIVE_INFINITY;
					}

					lvalues=_.unique(lvalues);
					lvalues.sort(function(a, b){return a - b;});
					for(var i=0,c=lvalues.length;i<c;++i){
						var val=lvalues[i];
						if (val>legend_or_less && val<legend_or_more){
							prop.cba.push(val);
							prop.cba.push(val);
						}
					}

					if (legend_or_more!==undefined && $.isNumeric(legend_or_more)){
						prop.cba.push(legend_or_more,Number.POSITIVE_INFINITY);
					}
				}
				else{
					prop.cba.push(mm.min);
					if (variableValues.length<3) prop.cba.push(mm.min);
					prop.cba.push(mm.max);
					if (variableValues.length<3) prop.cba.push(mm.max);
				}
			}

			if (!prop.cbac){
				var nc2=parseInt(prop.cba.length/2);
				prop.cbac= prop.inverse_pallete_checkbox ? giss.getColorPalette(prop.cp,nc2).reverse() : giss.getColorPalette(prop.cp,nc2);

				if (nc2>prop.cbac.length) {
					prop.cba.splice(prop.cbac.length*2);

					if (prop.cm===8){
						prop.cb.legend_or_more=prop.cba[prop.cba.length-1];
					}
				}
			}

			cbs={m:prop.cm!==undefined?prop.cm:0,nc:prop.cbac.length};
			cba=prop.cba;
			cbac=prop.cbac;
			if (mm!==undefined && variableValues.length<3 && (Math.abs(mm.min-mm.max)).toFixed(prop.decimals)<accuracy){
				cbac=[cbac[0]];
				cba=[cba[0],cba[0]];
				prop.cbac=cbac;
				prop.cba=cba;
			}
		}
		else{
			if (lvalues.length===0){
				cba=[];cbac=[];cbs={m:prop.cm,nc:parseInt(prop.cb)};
			}
			else{
				cbs=giss.getClassBreaks(lvalues,prop);
				
				if (cbs===false) {
					//try something else
					prop.cm=8;
					Legend.call(this,variableValues,prop,op);
					return;
				}
				
				cba=prop.cba=giss.cb2legend(cbs.cb,prop.decimals);
				if (cba.length>1){
					cba[0]=Number.NEGATIVE_INFINITY;
					cba[cba.length-1]=Number.POSITIVE_INFINITY;
				}
				cbac=prop.cbac=cbs.colors;
			}
		}

		cbac.forEach(function(colorObj){
			colorObj.a=parseInt(parseFloat(colorObj.a)*255);
		});

		if (prop.format){
			format=prop.format;
		}

		//fix class breaks accuracy
		for (var i=0,c=cba.length;i<c;i++){
			cba[i]=parseFloat(cba[i].toFixed(prop.decimals));
		}

		var items=prepareItems(cba,cbac,prop,prop.cm===8,variableValues.length);


		var header=['',''];
		var trashColumn=op.edit;

		if (items.length<2) trashColumn=false;

		var table=this.table=new Table({trashColumn:trashColumn,
									   header:['',''],
									   hideHeader:true,
									   removeDefaultClasses:true,
									   addClass:"table table-bordered",
									   confirmBeforeRemove:op.confirmBeforeRemove,
									   confirmBeforeRemoveString:t['Do you really want to remove selected interval?'],
									   selectBeforeRemove:true,
									   onRowRemoved:onRowRemoved});

		this.table.$el().css('width','100%');

		var $eic;

		if (op.edit){
			header=['','',''];
			var dlgHtml='<div id="edit-interval-content">'+
							'<div class="ilabel"><%=t("Selected interval before edit")%>:</div>'+
							'<div id="ibase" class="ivalue"></div>'+
							'<hr>'+
							'<div class="ilabel"><%=t("Lower interval limit")%>:</div>'+
							'<span class="ilabel helper-text" id="lvalue-help"></span>'+
							'<input id="lvalue">'+
							'<div class="ilabel"><%=t("Upper interval limit")%>:</div>'+
							'<span class="ilabel helper-text" id="rvalue-help"></span>'+
							'<input id="rvalue">'+
							'<div class="ilabel"><%=t("Split the interval at value")%>:</div>'+
							'<span class="ilabel helper-text" id="isplit-help"></span>'+
							'<input id="isplit">'+
						'</div>';
			op.intervalEditContainer.html(_.template(dlgHtml)({t:t}));
			$eic=op.intervalEditContainer.find('#edit-interval-content');
		}

		var onKeyUp=function(e,llimit,rlimit,$inputs,item,itemInx){
			var split=false;
			if (e.which==13) {
				split=true;
			}
			var $this=$(this);
			$this.removeClass('legend-error');
			var strval=$this.val();

			if (strval.substr(strval.length-1)!==prop.decimalSign){
				var val=extractFloat(strval,prop.decimalSign,prop.separatorSign);
				if (val){
					var dc=prop.decimals;
					if (strval.indexOf(prop.decimalSign)==-1) dc=0;
					$this.val(format(val,dc,prop.decimalSign,prop.separatorSign));
				}
			}

			onBlur(llimit,rlimit,$inputs,item,itemInx,split,true);
			return true;
		};

		function editInterval(item,itemInx,editStart,unformatted){
			if (editStart===true) {
				$eic.show();
				table.selectRow(itemInx);
				$eic.find('.legend-error').removeClass('legend-error');
			}

			var intervalParenthesesLeft='[';
			var intervalParenthesesRight=']';
			var $lvalueInput=$eic.find('#lvalue').prop('disabled',false);
			var $rvalueInput=$eic.find('#rvalue').prop('disabled',false);
			var $isplitInput=$eic.find('#isplit').prop('disabled',false);

			$eic.find('.helper-text').html('');

			var $inputs={$lvalueInput:$lvalueInput,$rvalueInput:$rvalueInput,$isplitInput:$isplitInput};

			var llimit,rlimit;

			if (item.lvalue===Number.NEGATIVE_INFINITY) {
				$lvalueInput.prop('disabled',true);
				intervalParenthesesLeft='(';
			}
			else{
				var previousInterval=items[itemInx-1];
				if (previousInterval!==undefined){
					if (previousInterval.lvalue===Number.NEGATIVE_INFINITY) {
						llimit=Number.NEGATIVE_INFINITY;
					}
					else{
						llimit=previousInterval.lvalue+2*accuracy;
						if ((llimit-accuracy)-previousInterval.lvalue < accuracy){ //if previous interval width is less than accuracy
							llimit=item.lvalue;
						}
					}
				}
				
				$eic.find('.helper-text#lvalue-help').html(_.template(t('Input a value betwen <%=m1%> and <%=m2%>'))({m1:_format(llimit,prop),m2:_format(item.rvalue-accuracy,prop)}));
			}

			if (item.rvalue===Number.POSITIVE_INFINITY) {
				$rvalueInput.prop('disabled',true);
				intervalParenthesesRight=')';
			}
			else{
				var nextInterval=items[itemInx+1];
				if (nextInterval!==undefined){
					if (nextInterval.rvalue===Number.POSITIVE_INFINITY) {
						rlimit=Number.POSITIVE_INFINITY;
					}
					else{
						rlimit=nextInterval.rvalue-2*accuracy;
						if (nextInterval.rvalue-(rlimit+accuracy) < accuracy){ //if next interval width is less than accuracy
							rlimit=item.rvalue;
						}
					}
				}
				
				$eic.find('.helper-text#rvalue-help').html(_.template(t('Input a value betwen <%=m1%> and <%=m2%>'))({m1:_format(item.lvalue+accuracy,prop),m2:_format(rlimit,prop)}));
			}

			$isplitInput.val('');
			if (editStart===true){
				$eic.find('#ibase').html(intervalParenthesesLeft+
								 _format(item.lvalue,prop)+', '+
								 _format(item.rvalue,prop)+
								 intervalParenthesesRight
								 );
			}

			if (editStart===true || unformatted!==true){	//prevent double formatting after keyup event
				$lvalueInput.val(_format(item.lvalue,prop));
				$rvalueInput.val(_format(item.rvalue,prop));
			}

			if (item.rvalue-item.lvalue<=2*accuracy) {
				$isplitInput.prop('disabled',true);
			}
			else{
				$eic.find('.helper-text#isplit-help').html(_.template(t('Input a value betwen <%=m1%> and <%=m2%> and press the ENTER key.'))({m1:_format(item.lvalue+accuracy,prop),m2:_format(item.rvalue-2*accuracy,prop)}));
			}

			$lvalueInput.off('blur').on('blur',function(){onBlur(llimit,rlimit,$inputs,item,itemInx);});
			$rvalueInput.off('blur').on('blur',function(){onBlur(llimit,rlimit,$inputs,item,itemInx);});
			$isplitInput.off('blur').on('blur',function(){onBlur(llimit,rlimit,$inputs,item,itemInx);});

			$lvalueInput.off('keyup').on('keyup',function(e){onKeyUp.bind($(this))(e,llimit,rlimit,$inputs,item,itemInx);});
			$rvalueInput.off('keyup').on('keyup',function(e){onKeyUp.bind($(this))(e,llimit,rlimit,$inputs,item,itemInx);});
			$isplitInput.off('keyup').on('keyup',function(e){onKeyUp.bind($(this))(e,llimit,rlimit,$inputs,item,itemInx);});
		}

		function onBlur(llimit,rlimit,$inputs,item,itemInx,split,unformatted){
			var result={lvalue:extractFloat($inputs.$lvalueInput.val(),prop.decimalSign,prop.separatorSign),
				rvalue:extractFloat($inputs.$rvalueInput.val(),prop.decimalSign,prop.separatorSign),
				isplit:extractFloat($inputs.$isplitInput.val(),prop.decimalSign,prop.separatorSign),
			};

			$.each($inputs,function(key,$obj){$obj.removeClass('legend-error');});

			var hasErrors=false;

			/*
			 * puts defaultValue if currentValue isNaN
			 */
			function defaultInputValue(currentValueKey,defaultValue,$input){
				if (isNaN(result[currentValueKey])) {
					result[currentValueKey]=defaultValue;
					$input.val(_format(defaultValue,prop));
					$input.select();
				}
			}

			//check lvalue
			if ($inputs.$lvalueInput.prop('disabled')===false){
				defaultInputValue('lvalue',item.lvalue,$inputs.$lvalueInput);
				if (!(lessOrEqual(llimit, result.lvalue) && lessOrEqual(result.lvalue, item.rvalue-accuracy))){
					$inputs.$lvalueInput.addClass('legend-error');
					hasErrors=true;
				}
			}

			//check rvalue
			if ($inputs.$rvalueInput.prop('disabled')===false){
				defaultInputValue('rvalue',item.rvalue,$inputs.$rvalueInput);
				if (!(lessOrEqual(item.lvalue+accuracy,result.rvalue) && lessOrEqual(result.rvalue, rlimit))) {
					$inputs.$rvalueInput.addClass('legend-error');
					hasErrors=true;
				}
			}

			//check split value
			if ($inputs.$isplitInput.prop('disabled')===false){
				if ($inputs.$isplitInput.val()!=''){
					if (!(lessOrEqual(item.lvalue+accuracy, result.isplit) && lessOrEqual(result.isplit, item.rvalue-2*accuracy))){
						$inputs.$isplitInput.addClass('legend-error');
						hasErrors=true;
					}
				}
			}

			if (hasErrors) return false;

			if (itemInx===0 && $inputs.$rvalueInput.prop('disabled')===false) {
				cba[itemInx*2+1]=result.rvalue;
				cba[itemInx*2+2]=result.rvalue+accuracy;
			}
			else if (itemInx==(items.length-1) && $inputs.$lvalueInput.prop('disabled')===false){
				cba[itemInx*2-1]=result.lvalue-accuracy;
				cba[itemInx*2]=result.lvalue;
			}
			else if ($inputs.$lvalueInput.prop('disabled')===false && $inputs.$rvalueInput.prop('disabled')===false){
				cba[itemInx*2-1]=result.lvalue-accuracy;
				cba[itemInx*2]=result.lvalue;
				cba[itemInx*2+1]=result.rvalue;
				cba[itemInx*2+2]=result.rvalue+accuracy;
			}

			var editStart=false;
			if ($inputs.$isplitInput.val()!='' && split===true){ //split
				cba.splice(itemInx*2+1, 0, result.isplit);
				cba.splice(itemInx*2+2, 0, result.isplit+accuracy);
				cbac=giss.getColorPalette(prop.cp,cbac.length+1);
				editStart=true;
			}

			var changedItems=prepareItems(cba,cbac,prop);

			if (_.isEqual(items,changedItems)) return false;

			items=changedItems;

			table.removeAllRows();

			if (items.length>1) table.updateOptions({trashColumn:true});

			refreshTable(items);
			table.selectRow(itemInx);

			editInterval(items[itemInx],itemInx,editStart,unformatted);

			return true;
		}

		function _getData(){
			var cb=prop.cm==8?prop.cb:cbs.nc;
			return {decimals:prop.decimals,cba:cba,cbac:cbac,cm:cbs.m,cb:cb,cp:prop.cp,t:prop.t};
		}

		function refreshTable(items){
			var iconPlus=true;
			if (items.length==9) iconPlus=false;
			items.forEach(function(item,itemInx){
				addItemToTable(item,itemInx,iconPlus);
			});

			if (op.onTableRefreshed){
				op.onTableRefreshed(_getData());
			}
		}

		function onRowRemoved(rid,data){
			var cbaInx=rid*2;
			if (cbaInx>0) {
				cba[cbaInx-1]=cba[cbaInx+1];
				cba.splice(cbaInx+1, 1);
				cba.splice(cbaInx, 1);
			}
			else {
				cba.splice(cbaInx+1, 1);
				cba.splice(cbaInx+1, 1);
			}

			cba[0]=Number.NEGATIVE_INFINITY;
			cba[cba.length-1]=Number.POSITIVE_INFINITY;

			cbac=giss.getColorPalette(prop.cp,cbac.length-1);

			table.removeAllRows();
			items=prepareItems(cba,cbac,prop);

			if (items.length==1){
				table.updateOptions({trashColumn:false});
			}

			refreshTable(items);
			$eic.hide();
		}

		var addItemToTable=function(item,itemInx,iconPlus){
			if (iconPlus===undefined) iconPlus=true;
			var row;
			var ctd;
			if (op.edit===true){
				ctd=1;
				var $pencil=$('<i class="fa fa-pencil" aria-hidden="true"></i>');
				row=[$pencil,'',item.text];
				$pencil.click(function(){
					editInterval(item,itemInx,true);
					if (op.onIntervalEdit) op.onIntervalEdit();
				});
			}
			else{
				ctd=0;
				row=['',item.text];
			}
			var $tr=table.addRow(row);
			var $tdc=$tr.find('td').eq(ctd);
			$tdc.css('background-color','rgb('+item.color.r+','+item.color.g+','+item.color.b+')');
			$tdc.css('width','3em');
		};

		refreshTable(items);

		this.$eic=$eic;	//interval editor div

		this.getData=function(){
			return _getData();
		};
	};

	Legend.prototype.$el=function(){
		return this.table.$el();
	};

	Legend.prototype.hideManualIntervalEditor=function(){
		if (this.$eic!==undefined){
			this.$eic.hide();
		}
	};

	Legend._addSpecialValue=function(sv,table,addEye){
		sv.color.a=parseInt(parseFloat(sv.color.a)*255);
		var row;
		var eq;
		if (addEye===true){
			row=['<i class="icon ion-eye"></i>','',sv.legend_caption];
			eq=1;
		}
		else{
			row=['',sv.legend_caption];
			eq=0;
		}

		var $tr=table.addRow(row);
		var $tdc=$tr.find('td').eq(eq);
			$tdc.css('background-color','rgb('+sv.color.r+','+sv.color.g+','+sv.color.b+')');
			$tdc.css('width','3em');
	};

	Legend.prototype.addSpecialValue=function(sv){
		Legend._addSpecialValue(sv,this.table);
	};

	return Legend;
});
