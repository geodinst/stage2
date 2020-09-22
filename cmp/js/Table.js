define(function(){
	"use strict";
	var trashIcon='<i class="fa fa-trash" aria-hidden="true"></i>';
	var Table=function(op){
		if (!op) op={};
		this.rid=0;
		this.$tbody=$('<tbody/>');
		this.op=op;
		this.$selectedRow=null;
		this.$table=null;

		var $table=$('<table class="table table-striped table-bordered"></table>');
		this.$table=$table;
		this.updateOptions(op);
		$table.append(this.$tbody);
	};

	Table.prototype.val=function(){
		var a=[];
		this.$tbody.find('tr').each(function(){
			var b=[];
			$('td', $(this)).each(function(){
				b.push($(this).text());
			});
			a.push(b);
		});
		return a;
	};

	Table.prototype.updateOptions=function(op){
		$.extend(this.op, op);
		this.removeAllRows();

		var header=this.op.header;
		if (this.op.removeDefaultClasses) this.$table.removeClass();
		if (this.op.addClass) this.$table.addClass(this.op.addClass);
		if (this.op.trashColumn) header.push(trashIcon);

		if (!this.op.hideHeader){
			var $thead=$('<thead/>');
			var $tr=$('<tr/>');
      if (this.op.checkable===true) $tr.append('<th></th>');

			header.forEach(function(cname){
				$tr.append('<th>'+cname+'</th>');
			});

			$thead.html($tr);
			this.$table.html($thead);

      if (this.op.checkable===true){
        var $th=$thead.find('th:first');
        this._$headCheckBox=$('<input type="checkbox">"');
        var $table=this.$table;
        this._$headCheckBox.click(function(){
          if ($(this).prop('checked')){
            $table.find('.cb-row').prop('checked',true);
          }
          else{
            $table.find('.cb-row').prop('checked',false);
          }
        });
        $th.html(this._$headCheckBox);
      }
    }
	};

	Table.prototype.$el=function(){
		return this.$table;
	};

  Table.prototype.getSelectedRows=function(){
    var checked=[];
    this.$table.find('.cb-row:checked').each(function(){
      checked.push($(this).data('id'));
    });
    return checked;
  };

	Table.prototype.removeAllRows=function(){
		this.$tbody.empty();
		this.rid=0;
		this.$selectedRow=null;
	};

	Table.prototype.addRow=function(rowValues,afterRowId,dataID){
		var id=this.rid++;
		var $tr=$('<tr/>',{id:id});

    if (this.op.checkable===true){
      var $cb=$('<input type="checkbox">"').addClass('cb-row');
      $cb.data('id',dataID);
      var $headCheckBox=this._$headCheckBox;
      var $table=this.$table;
      $cb.click(function() {
        var numAll=$table.find('.cb-row').length;
        if ($table.find('.cb-row:checked').length==numAll){
          $headCheckBox.prop('checked',true);
        }
        else{
          $headCheckBox.prop('checked',false);
        }
      });
      var $td=$('<td/>');
      $td.html($cb);
      $tr.append($td);
    }

		rowValues.forEach(function(td){
      var $td=$('<td/>');
      $td.html(td);
			$tr.append($td);
		});
		if (this.op.trashColumn){
			var $trashIcon=$(trashIcon);
			$trashIcon.data('rid',id);
			$tr.append($('<td/>').html($trashIcon));
			$trashIcon.click($.proxy(function(){
				this.removeRow(id);
			},this));
		}
		if (this.op.selectRowOnClick){
			var that=this;
			$tr.click(function(){
				that.selectRow(id,$tr);
			});
		}

		if (afterRowId!==undefined && afterRowId > -1){
			var $r=this.$tbody.find('tr#'+afterRowId);
			if ($r.length===1){
				$r.after($tr);
				return;
			}
		}

		this.$tbody.append($tr);
		return $tr;
	};

	Table.prototype.selectRow=function(rid,$selectedRow){
		if (this.$selectedRow) this.$selectedRow.removeClass('selected');
		if ($selectedRow)
			this.$selectedRow=$selectedRow;
		else
			this.$selectedRow=this.$tbody.find('#'+rid);

		this.$selectedRow.addClass('selected');

		if (this.op.onRowSelected) this.op.onRowSelected(id);
	};

	Table.prototype.removeRow=function(rid,selected){
		var remove=true;
		
		if (selected!==true && this.op.selectBeforeRemove===true){
			this.selectRow(rid);
			setTimeout(function(){this.removeRow(rid,true);}.bind(this),73);
			return;
		}
		
		if (this.op.confirmBeforeRemove){
			var msg=this.op.confirmBeforeRemoveString!==undefined?
							this.op.confirmBeforeRemoveString:
							'Do you really want to remove selected row?';
			remove=confirm(msg);
		}

		if (remove) {
			var $el=this.$tbody.find('#'+rid);
			var data=$el.data();
			$el.remove();
			if (this.op.onRowRemoved) this.op.onRowRemoved(rid,data);
		}
	};

	return Table;
});
