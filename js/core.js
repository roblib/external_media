(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.ExternalMediaCore = {
    attach: function (context, settings) {

      // Hide Upload element. Replace it with custom options.
      $('.form-type-external-media, .webform-submission-form', context).each(function() {
        if ($(this).find('.external-media-widget-wrapper').length) {
          $(this).find('input[type=file]').hide();
          $(this).find('input[type=submit]').hide();
          $(this).find('input[name*=remove]').show();
        }
      });

      // Trigger file upload browser
      $('.form-type-external-media a.browse, .webform-submission-form a.browse', context).unbind().click(function(e) {
        var $parent = $(this).closest('.form-managed-file');
        $parent.find('input[type=file]').click();
        e.preventDefault();
      });

      $('.form-type-external-media input.form-file, .webform-submission-form input.form-file', context).change(function() {
        var $parent = $(this).parent().parent();
        if ($parent.find('.external-media-widget-wrapper').length) {
          setTimeout(function() {
            if(!$('.error', $parent).length) {
              $('input.form-submit[value=Upload]', $parent).mousedown();
              $('.button', $parent).unbind().addClass('disabled');
            }
          }, 100);
        }
      });

    }
  };

}(jQuery, Drupal, drupalSettings));
