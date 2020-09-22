(function ($){
	// link for empty tree
				$(document).on("click", "#link4empty", function(){
					$.post("post_redirect", {"type":"saveTree","parent_id":"0","position":"0","text":"New item"}, function(){
						location.reload();
					});
				});
})(jQuery);
