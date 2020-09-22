define(function(){

var T=window.app.T;

return function(key){
    var translation=T[key];
    if (translation===undefined){
        console.log('missing translation',key);
        return '{'+key+'}';
    }
    return translation;
}

})