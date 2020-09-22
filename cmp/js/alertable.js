define(function(){
    /**
      * @param op options
      * @param op.title alertable title
      * @param op.prompt html prompt
      * @param op.ok callback on ok
      * @param op.cancel callback on cancel
    */
    return function(op){
        $.alertable.prompt(op.title, {
          prompt:op.prompt
        }).then(function(data) {
          op.ok(data);
        }, function(data) {
          op.cancel(data);
        });
    };
});