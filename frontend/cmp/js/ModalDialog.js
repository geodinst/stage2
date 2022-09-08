define(function(){
	var tpl='<div class="modal-dialog modal-lg">'+
			'<div class="modal-content">'+
			  '<div class="modal-header">'+
				'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">'+
				  '<span class="pficon pficon-close"></span>'+
				'</button>'+
				'<h4 class="modal-title" id="myModalLabel"></h4>'+
			  '</div>'+
			  '<div>'+
				'<div class="modal-body">'+
				'</div>'+
				'<div class="modal-footer">'+
				'</div>'+
			  '</div>'+
			'</div>'+
		  '</div>';
		  
	var tpl2='<div class="modal-content">'+
                '<div class="popup_header">'+
                    '<span class="close">&times;</span>'+
					'<h4 class="modal-title"></h4>'+
                '</div>'+
                '<div class = "popup_content_container modal-body">'+
                '</div>'+
				'<div class = "popup_content_container modal-footer">'+
                '</div>'+
              '</div>';
		  
	var ModalDialog=function(op){
		if (!op) op={};
		var id='mdlg-'+(new Date()).getTime();
		var $modal=this.$modal=$('<div/>',{
			'class':op.tpl==2?"overlay":"modal fade",
			//'tabindex':"-1", //has to be removed in order for select2 search box to work
			'role':"dialog",
			'aria-hidden':"true",
			id:id
		});
		
		if (op.tpl==2)
			$modal.html(tpl2);
		else
			$modal.html(tpl);
		
		if (op.size){
			$modal.find('.modal-dialog').removeClass('modal-lg').addClass(op.size);	
		}
		
		this.$body=$modal.find('.modal-body').first();
		this.$title=$modal.find('.modal-title').first();
		this.$footer=$modal.find('.modal-footer').first();
		this.$contentDiv=$modal.find('.modal-content').first();
		
		if (op.onShown){
			$modal.on('shown.bs.modal',function(){
				op.onShown();
			});
		}
		
		var that=this;
		
		if (op.acceptBtn){
			var $btn=$('<button type="button" class="btn btn-default">'+op.acceptBtn.text+'</button>');
			
			if (op.acceptBtn.typeClass!==undefined) {
				$btn.removeClass('btn-default').addClass('btn-'+op.acceptBtn.typeClass);
			}
			
			this.$footer.append($btn);
			$btn.click(function(){
				if (op.acceptBtn.callback) op.acceptBtn.callback();
				that.hide();		
			});
		}
    
		if (op.onClose){
			$modal.on('hidden.bs.modal',function(){
				op.onClose();
			});
		}
		
		if (op.tpl==2) {
			$modal.modal=function(action){
				if (action==='show'){
					that.$contentDiv.parent().css({'display': 'inline'});
					that.$modal.trigger('shown.bs.modal');
				}
				else if (action==='hide'){
					that.$contentDiv.parent().css({'display': 'none'});
				}
			};
			
			$modal.find('.close').click(function(){
				that.$contentDiv.parent().css({'display': 'none'});
			});
			
			$('body').prepend($modal);
		}
	};
	
	ModalDialog.prototype.destroy=function(){
		var $backdrop=this.$modal.data()['bs.modal'].$backdrop;
		var $dialog=this.$modal.data()['bs.modal'].$dialog;
		var $element=this.$modal.data()['bs.modal'].$element;
		if ($backdrop) $backdrop.remove();
		if ($dialog) $dialog.remove();
		if ($element) $element.remove();
		this.$modal.data('bs.modal',null);
		this.$modal.remove();
	};
		
	ModalDialog.prototype.show=function(){
		this.$modal.modal('show');
	};
		
	ModalDialog.prototype.hide=function(){
		this.$modal.modal('hide');
	};
	
	return ModalDialog;
});