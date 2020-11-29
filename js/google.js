(function ($) {

  "use strict";

  /**
   * Google Drive.
   */
  Drupal.behaviors.ExternalMediaGoogleDrive = {
    attach: function (context, settings) {

      $('.form-type-external-media a.' + settings.google_class + ', .webform-submission-form a.' + settings.google_class, context).unbind().click(function(e) {
        var clientId = settings.google_client_id;
        var appId = settings.google_app_id;
        var scope = settings.google_scope;
        var view_id = settings.google_view_id.split(',');
        var mine_only = settings.google_mine_only;
        var nav_hidden = settings.google_nav_hidden;
        var support_drives = settings.google_support_drives;
        var pickerApiLoaded = false;
        var oauthToken;
        var _parent_container = $(this).parent().parent();

        function onAuthApiLoad() {
          window.gapi.auth.authorize({
              'client_id' : clientId,
              'scope'     : scope,
              'immediate' : false
            },
            handleAuthResult
          );
        }

        function onPickerApiLoad() {
          pickerApiLoaded = true;
          createPicker();
        }

        function handleAuthResult(authResult) {
          if (authResult && !authResult.error) {
            oauthToken = authResult.access_token;
          }
          createPicker();
        }

        // Create and render a Picker object for searching images.
        function createPicker() {
          if (pickerApiLoaded && oauthToken) {
            var views = {
              'google.picker.ViewId.DOCS': google.picker.ViewId.DOCS,
              'google.picker.ViewId.DOCS_IMAGES': google.picker.ViewId.DOCS_IMAGES,
              'google.picker.ViewId.DOCS_IMAGES_AND_VIDEOS': google.picker.ViewId.DOCS_IMAGES_AND_VIDEOS,
              'google.picker.ViewId.DOCS_VIDEOS': google.picker.ViewId.DOCS_VIDEOS,
              'google.picker.ViewId.DOCUMENTS': google.picker.ViewId.DOCUMENTS,
              'google.picker.ViewId.DRAWINGS': google.picker.ViewId.DRAWINGS,
              'google.picker.ViewId.FOLDERS': google.picker.ViewId.FOLDERS,
              'google.picker.ViewId.FORMS': google.picker.ViewId.FORMS,
              'google.picker.ViewId.PDFS': google.picker.ViewId.PDFS,
              'google.picker.ViewId.PRESENTATIONS': google.picker.ViewId.PRESENTATIONS,
              'google.picker.ViewId.SPREADSHEETS': google.picker.ViewId.SPREADSHEETS,
            };

            var _plugin = _parent_container.find('a.google-picker').data('plugin'); // required
            var _max_filesize = _parent_container.find('a.google-picker').data('max-filesize');
            var _description = _parent_container.find('a.google-picker').data('description');
            var _extensions = _parent_container.find('a.google-picker').data('file-extentions');
            var _cardinality = _parent_container.find('a.google-picker').data('cardinality');

            var picker = new google.picker.PickerBuilder()
              .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
              .setAppId(appId)
              .setOAuthToken(oauthToken)
              .addView((view_id.indexOf('google.picker.ViewId.DOCS')  == -1) ? false : new google.picker.View(views['google.picker.ViewId.DOCS']).setMimeTypes(_extensions))
              .addView((view_id.indexOf('google.picker.ViewId.DOCS_IMAGES')  == -1) ? false : views['google.picker.ViewId.DOCS_IMAGES'])
              .addView((view_id.indexOf('google.picker.ViewId.DOCS_IMAGES_AND_VIDEOS')  == -1) ? false : views['google.picker.ViewId.DOCS_IMAGES_AND_VIDEOS'])
              .addView((view_id.indexOf('google.picker.ViewId.DOCS_VIDEOS')  == -1) ? false : views['google.picker.ViewId.DOCS_VIDEOS'])
              .addView((view_id.indexOf('google.picker.ViewId.DOCUMENTS')  == -1) ? false : views['google.picker.ViewId.DOCUMENTS'])
              .addView((view_id.indexOf('google.picker.ViewId.DRAWINGS')  == -1) ? false : views['google.picker.ViewId.DRAWINGS'])
              .addView((view_id.indexOf('google.picker.ViewId.FOLDERS')  == -1) ? false : views['google.picker.ViewId.FOLDERS'])
              .addView((view_id.indexOf('google.picker.ViewId.FORMS')  == -1) ? false : views['google.picker.ViewId.FORMS'])
              .addView((view_id.indexOf('google.picker.ViewId.PDFS')  == -1) ? false : views['google.picker.ViewId.PDFS'])
              .addView((view_id.indexOf('google.picker.ViewId.PRESENTATIONS')  == -1) ? false : views['google.picker.ViewId.PRESENTATIONS'])
              .addView((view_id.indexOf('google.picker.ViewId.SPREADSHEETS')  == -1) ? false : views['google.picker.ViewId.SPREADSHEETS'])
              .addView(new google.picker.DocsUploadView())
              // Respect Drupal field `Number of values` value.
              .setMaxItems((_cardinality > 0) ? _cardinality : 100)
              .setOrigin(window.location.protocol + '//' + window.location.host)
              .setCallback(function(data) {
                if (data.action == google.picker.Action.PICKED) {
                  var _links = [];
                  for (var i = 0; i < data.docs.length; i++) {
                    if (data.docs[i].sizeBytes > _max_filesize) {
                      alert(_description);
                    }
                    else {
                      _links.push(_plugin + '::::' + data.docs[i].id + '@@@' + data.docs[i].name + '@@@' + oauthToken);
                    }
                  }

                  // Autosubmit (downloads the file into the file or image field)
                  _parent_container.find('.external-media-widget-wrapper input[type=text]').val(_links.join('|'));
                  if (_links.length) {
                    $('input.form-submit[value=Upload]', _parent_container).mousedown();
                    $('.button', _parent_container).unbind().addClass('disabled');
                  }
                  _parent_container.ajaxComplete(function(event, xhr, settings) {
                    $('.button', _parent_container).removeClass('disabled');
                  });

                }
              })
              .build();
            picker.setVisible(true);
            // Fix Media Library popup overlay issue.
            $('.picker-dialog-bg').css('z-index', 99998);
            $('.picker-dialog').css('z-index', 99999);
          }
        }

        // Google Drive plugin.
        gapi.load('auth', {'callback': onAuthApiLoad});
        if (pickerApiLoaded === false) {
          gapi.load('picker', {'callback': onPickerApiLoad});
        }

        e.preventDefault();

      });

    }
  };

}(jQuery));
