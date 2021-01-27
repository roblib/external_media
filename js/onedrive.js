(function ($) {

  "use strict";

  /**
   * OneDrive.
   */
  Drupal.behaviors.ExternalMediaOneDrive = {
    attach: function (context, settings) {

      $('.form-type-external-media a.' + settings.onedrive_class + ', .webform-submission-form a.' + settings.onedrive_class, context).unbind().click(function(e) {
        var $parent = $(this).parent().parent();

        var _plugin = $(this).data('plugin'); // required
        var _max_filesize = $(this).data('max-filesize');
        var _description = $(this).data('description');
        var _cardinality = $(this).data('cardinality');
        var _multiselect = $(this).data('multiselect');

        // Because there is no max files limit in OneDrive Picker
        // we have to use this fake limitation.
        var _limit = (_cardinality > 0) ? _cardinality : 100;
        if (_limit > 1) {
          _multiselect = true;
        }

        // OneDrive plugin.
        var pickerOptions = {
          clientId: settings.onedrive_client_id,
          success: function(files) {
            var _links = [];
            var _count = 0;
            for (var i = 0; i < files.value.length; i++) {
              if (files.value[i]['size'] > _max_filesize) {
                alert(_description);
              }
              else {
                if (_count < _limit) {
                  _links.push(_plugin + '::::' + files.value[i]['@microsoft.graph.downloadUrl'] + '@@@' + files.value[i]['name']);
                  _count++;
                }
              }
            }

            // Autosubmit (downloads the file into the file or image field)
            $parent.find('.external-media-widget-wrapper input[type=text]').val(_links.join('|'));
            if (_links.length) {
              $('input.form-submit[value=Upload]', $parent).mousedown();
              $('.button', $parent).unbind().addClass('disabled').click(function(e) {
                e.preventDefault();
              });
            }
            $parent.ajaxComplete(function(event, xhr, settings) {
              $('.button', $parent).removeClass('disabled');
            });

          },

          cancel: function() {
            // handle when the user cancels picking a file
          },

          action: 'download',
          multiSelect: (_multiselect > 0) ? true : false,
          advanced: {
            redirectUri: settings.onedrive_redirect_url,
            scopes: 'Files.ReadWrite'
          },
        };
        if (settings.onedrive_auth_endpoint) {
          pickerOptions.advanced.endpointHint = settings.onedrive_auth_endpoint;
        }

        OneDrive.open(pickerOptions);

        e.preventDefault();

      });

    }
  };

}(jQuery));
