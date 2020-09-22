  requirejs.config({
      urlArgs: "bust=" + (new Date()).getTime(),
      paths:{
        cmp:'../../modules/stage2_admin/js/cmp'
      }
  });