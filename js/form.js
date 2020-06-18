(function ($) {

  "use strict";
  
  Drupal.behaviors.ExternalMediaSettingsForm = {
    attach: function (context) {
      var $context = $(context);

      $context.find('form.external-media-settings .vertical-tabs__pane').each(function() {
        var _plugin_name = $(this).data('drupal-selector');
        $context.find('#' + _plugin_name).drupalSetSummary(function (context) {
          if ($(context).find('input[type=checkbox]:first:checked').length) {
            return Drupal.t('Enabled');
          }
          else {
            return Drupal.t('Disabled');
          }
        });

      });

    }
  };

}(jQuery));
