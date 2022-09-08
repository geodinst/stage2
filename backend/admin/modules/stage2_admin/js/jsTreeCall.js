(function ($){

	'use strict';

	Drupal.behaviors.gimOlMapContentElement = {
		attach: function (context, settings){


			console.log($('#jstree'));

			var dataMenuRaw = $('#jsTreeData').data("menu");
			var dataMenu = [];
			var i = 0;

			for(i;i<Object.keys(dataMenuRaw).length;i++){
				var subData = dataMenuRaw[i].data;
				delete dataMenuRaw[i]["data"];
				dataMenu.push($.extend(dataMenuRaw[i], subData));
			}

			dataMenu = (!jQuery.isEmptyObject(dataMenu))?dataMenu:[{ "id" : "0", "parent" : "#", "text" : "Root" }];
			$('#jstree').once().jstree({
				  "core" : {
					"animation" : 200,
					"check_callback" : true,
					"themes" : { "stripes" : false },
					"data": dataMenu,
					check_callback : function (op, node, par, pos, more) {
					  if(more && more.dnd && (op === 'move_node' || op === 'copy_node') && par.id == "#"/* && ... conditions regarding node and parent ... */) {
						return false;
						  }
						  return true;
						}
				  },
				  "plugins" : [
					"contextmenu", "dnd", "search",
					"state", "types","unique"
				  ],
				  "contextmenu": {
					   "items": function(node) {
							   var tmp = $.jstree.defaults.contextmenu.items();
								tmp.settings = {
									'label' : 'Set variable settings',
									'action' : function (e, data) {
										window.location.replace(node.original.link);
									}
								};
								tmp.deleteentry = {
									'label' : 'Delete',
									'action' : function (e, data) {
										window.location.replace(node.original.delete);
									}
								};
							   delete tmp.remove;
							   delete tmp.ccp;
							   if(!$.isEmptyObject(node.children)){
								   tmp.deleteentry._disabled = true;
							   }
							   return tmp;
							}
						}
				})
				.on('create_node.jstree', function (e, data) {
					var update_positions ={};
					var treeData = $('#jstree').jstree(true).get_json('#', {flat:true})
					$.each(treeData,function(key,value){
						update_positions[value.id] = key;
					});
					$.post('post_redirect', { 'type':'saveTree', 'parent_id' : data.node.parent, 'update_positions' : update_positions, 'text' : data.node.text })
						.done(function (d) {
							console.log(d);
							data.instance.set_id(data.node, d);
							location.reload();
						})
						.fail(function () {
							data.instance.refresh();
							// window.location.reload();
						});
				}).on('rename_node.jstree', function (e, data) {
					$.post('post_redirect', { 'type':'updateNameTree', 'id' : data.node.id, 'text' : data.text })
						.fail(function () {
							data.instance.refresh();
							window.location.reload();
						});
				})
				// .on('delete_node.jstree', function (e, data) {
				// 	$.post('post_redirect', { 'type':'deleteTreeItem','id' : data.node.id })
				// 		.done(function(d){
				// 			var content = $('#jstree').jstree(true).get_json('#', { 'flat': true });
				// 			if ( content.length == 0 ) {
				// 				location.reload();
				// 			}
				// 		})
				// 		.fail(function () {
				// 			data.instance.refresh();
				// 		});
				// })
				.on('move_node.jstree', function (e, data) {
					var update_positions ={};
					var treeData = $('#jstree').jstree(true).get_json('#', {flat:true})
					$.each(treeData,function(key,value){
						update_positions[value.id] = key;
					});

					var data_parent = (data.parent == "#")?null:data.parent;
					$.post('post_redirect', { 'type':'moveTreeItem','id' : data.node.id, 'parent' : data_parent ,'update_positions':update_positions})
						.fail(function () {
							data.instance.refresh();
							window.location.reload();
						});
				}).on("select_node.jstree",
					 function(evt, data){
						  // parse selected value to drupal form element
						  $("#jstree_value").val(String(data.node.id));
					 }
				);
		}
	}
})(jQuery);
