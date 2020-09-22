define(['text!tpl/sidebarTabHeader.html'],function(sidebarTabHeader){
	return function(_ulID,_tabID,iconClass,title,tpl,tt){
		var tabID=_tabID;
		var ulID=_ulID;
		var $li=$('<li/>',{class:'disabled'});
		var $a=$('<a/>',{href:'#'+tabID,role:'tab'});
		if (iconClass){
			var $i=$('<i/>',{class:iconClass,title:tt});
			$a.html($i);
		}
		$li.html($a);

		if (tpl) {
			var $div=$('<div/>',{class:'sidebar-pane',id:tabID});
			var htpl=_.template(sidebarTabHeader);
			$div.html(htpl({title:title})+tpl);
		}

		this.$a=function(){
			return $a;
		};
		
		this.hasIcon=function(){
			return $a.find('i').length>0?true:false;
		};

		this.id=function(){
			return tabID;
		};

		this.$div=function(){
			return $div;
		};

		this.enable=function(enable){
			if (enable){
				$li.removeClass("disabled");
			}
			else{
				$li.addClass("disabled");
			}
		};

		this.appendTo=function($tabs,$content){
			var $ul=$tabs.find('#'+ulID);
			if ($ul.length===0) {
				$ul=$('<ul/>',{role:'tablist',id:ulID});
				$tabs.append($ul);
			}
			if (iconClass){
				$ul.append($li);
			}
			if ($content){
				$content.append($div);
			}
		};
	};
});
