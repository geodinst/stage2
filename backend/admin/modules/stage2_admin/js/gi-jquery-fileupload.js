/**
 * @file
 * gi-jquery-fileupload feature.
 */

(function ($, Drupal) {

  'use strict';
  $('#file_upload_errors').empty().hide();

  function input2table(){
    var upld=$('#uploaded_files_names').val();
    if (!upld) return;
    var $tbody=$('<tbody/>');
    var cls='odd';
    upld=JSON.parse(upld);
    upld.forEach(function(fname){
      var $tr=$('<tr/>',{class:cls});

      if (cls=='odd')
        cls='even';
      else
        cls='odd';

      var $td=$('<td/>');
      $td.text(fname);
      $tr.append($td);
      $td=$('<td/>');
      $td.html('<a href="#" class="remove-row" data-name="'+fname+'"><span class="ui-icon ui-icon-trash"></span></a>');
      $tr.append($td);
      $tbody.append($tr);
    });

    if (upld.length>0)
      $('#get_headers_btn').show();
    else
      $('#get_headers_btn').hide();

    $('#uploaded_files_list').html($tbody);
    $('.remove-row').click(function(){
      $('#file_upload_errors').empty().hide();
      var fname=$(this).data('name');
      var upld=$('#uploaded_files_names').val();
      upld=JSON.parse(upld);
      var inx=upld.indexOf(fname);
      if (inx > -1) {
        upld.splice(inx, 1);
        $('#uploaded_files_names').val(JSON.stringify(upld));
        input2table();
      }
	  $.post(drupalSettings.path.baseUrl+"s2c/delete_file", {"file_name":fname});
	  // remove from sentFiles
	  if(sentFiles){
		  var index = sentFiles.indexOf(fname);
		  if(index > -1){
			  sentFiles.splice(index,1);
		  }
	  }
    });
    if (window.onInput2table) window.onInput2table();
  }

  var op=drupalSettings.gIjQueryFileUpload;
      $('#fileupload').click(function(){
        $('#file_upload_errors').empty().hide();
      });

      input2table();

	  var base_url = drupalSettings.stage2_admin[drupalSettings.stage2_admin.form_name].$base_url;
      //var base_url = drupalSettings.stage2_admin.StageGeospatialLayerEditForm.$base_url;
      var url = base_url+'large_upload';
      var sentFiles=[];
      $('#fileupload').fileupload({
          url: url,
          maxChunkSize: op.maxChunkSize,
          dataType: 'json',
          acceptFileTypes: new RegExp('(\.|\/)('+op.acceptFileTypes+')$', 'i'),
          done: function (e, data) {
              $.each(data.result.response.files, function (index, file) {
                  var _files = $('#uploaded_files_names').val();
                  _files = _files.replace(/[[\]]/g,'');
                  _files = JSON.parse("[" + _files + "]");
                  _files.push(file.name);
                  if (_files.length !==0){
                    $('#uploaded_files_names').show();
                    $('#edit-names-column').select2().val(null).trigger('change');
                    $('#edit-geo-code').select2().val(null).trigger('change');
                    $('#select_columns_container').hide();
                    $('#get_headers_btn').show();
                    $('#uploaded_files_names').val(JSON.stringify(_files));
                    input2table();
                  }
              });
            },
          processfail: function (e, data) {
            data.files.forEach(function(err){
              $('#file_upload_errors').append(err.name+' <b>'+err.error+'</b>.<br>');
            });
            $('#file_upload_errors').addClass("errors messages messages--error").show();
          },
          beforeSend: function(xhr,data){
            var sendData=true;
            var fname;
            for (var i=0,c=data.files.length;i<c;++i){
              fname=data.files[i].name;
              if (sentFiles.indexOf(fname)!==-1){
                sendData=false;
                break;
              }
              sentFiles.push(fname);
            }

            if (sendData===false) {
              xhr.abort();
              $('#file_upload_errors').append(fname+' <b>'+'The file with this name was already uploaded'+'</b>.<br>');
              $('#file_upload_errors').addClass("errors messages messages--error").show();
            }

          },
          progressall: function (e, data) {
              var progress = parseInt(data.loaded / data.total * 100, 10);
              $('.progress').show();
              $('#progress .progress-bar').css(
                  'width',
                  progress + '%'
              );
          },
          stop: function (e, data) {
              $('.progress').hide();
            },
      }).prop('disabled', !$.support.fileInput)
          .parent().addClass($.support.fileInput ? undefined : 'disabled');

})(jQuery, Drupal);
