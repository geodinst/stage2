define('jquery', [], function() {
  return jQuery;
});

if ( !('ontouchstart' in window || navigator.maxTouchPoints)) {
  $(document).tooltip();
};
window.alert = function(msg) {
  $overlay = $('#overlay');
  $overlay.css({
    'display': 'inline'
  });
  $('#popup-content').html(msg);
}

$overlay = $('#overlay').click(function(e) {
  if (e.target !== this)
    return;
  $('#popup-content').empty();
  $('#overlay').css({
    display: 'none'
  });
});

if (location.hash.indexOf("chart") >= 0) {
  window.location.replace(window.location.href.replace("#chart=true", "chart.html"));
}

requirejs.config({
  baseUrl: '.',
  urlArgs: "bust=" + (new Date()).getTime(),
  paths: {
    text: 'lib/text',
    cmp: 'cmp/js'
  },
  stubModules: ['text']
});
//node ../../lib/r.js -o name='main' out=main-built.js mainConfigFile=app.js

window.app = {};

window.app.getHashValue = function(key) {
  var matches = location.hash.match(new RegExp(key + '=([^&]*)'));
  return matches ? decodeURIComponent(matches[1]) : null;
};

window.app.lang = window.app.getHashValue('lang');
window.app.isadmin = (window.app.getHashValue('stage_admin') === 'true');

require(['js/utils', 'locale'], function(U, locale) {
  if (locale.title!==undefined) {
     document.title = locale.title;
  }

  if (window.app.getHashValue('lang')===null && locale.defaultLang!==undefined) {
	window.app.lang=locale.defaultLang;
  }

  window.app.s2c = locale.s2c;
  window.app.delineation = locale.delineation;
  window.app.opentab = locale.opentab;
  window.app.center = locale.center;
  U.get(window.app.s2c + 'translations', {
      lang: window.app.lang
    }, function(data) {
      window.app.T = data.translations;
      window.app.lang = data.lc;
      window.app.decimalSign = window.app.T['.'];

      if (window.app.decimalSign === '.')
        window.app.separatorSign = ',';
      else
        window.app.separatorSign = '.';

      if (!locale.debug) {
        $('#atlwdg-trigger').hide();
      }

      require(['js/map'], function(mm) {
        U.setAjaxSend();
        U.setAjaxComplete();
        U.setAjaxError();

        $(window).on('hashchange', function() {
          var newLang = U.getHashValue('lang');
          if (newLang && window.app.lang !== newLang) {
            window.app.lang = newLang;
            window.location.reload();

          } else if (U.getHashValue('tid') && U.getHashValue('vid') && U.getHashValue('sid')) {
            window.location.reload();

          }
        });

        $(function() {
          window.onbeforeunload=null;
          mm.init('mapid');
        });
      });


      if (locale.cookie.cookie) {
        window.cookieconsent.initialise({
          "palette": {
            "popup": {
              "background": "#edeff5",
              "text": "#838391"
            },
            "button": {
              "background": "#4b81e8"
            }
          },
          onStatusChange: function(status) {
            //window.location.reload();
            let cookie_status = U.getCookie('cookieconsent_status');
            if(cookie_status === 'dismiss' && locale.analytics){
              require(['analytics'], function() {});
            }
          },
          "type": "opt-out",
          "content": {
            message: locale.cookie[window.app.lang].nap,
            dismiss: locale.cookie[window.app.lang].ok,
            deny: locale.cookie[window.app.lang].deny,
            link: '',
          }
        });
      }
      $('#cookie-detail').click(() => {
        $('#btn_about').click();
        //$('.sidebar-tabs #group2 a i').first().click();
        const $el=$('.help_container h3:contains('+locale.cookie[window.app.lang].detailHeader+')');
        if (!$el.hasClass('ui-state-active')) $el.first().click();
      });
    },
    'json', $('body'));
    
    let cookie_status = U.getCookie('cookieconsent_status');

    console.log('cookie status', cookie_status)
    
    if(cookie_status === 'dismiss' && locale.analytics){
      require(['analytics'], function() {});
    }
});
