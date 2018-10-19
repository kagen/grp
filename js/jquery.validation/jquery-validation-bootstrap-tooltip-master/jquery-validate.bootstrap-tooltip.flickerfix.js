/*!
 * jQuery Validation Bootstrap Tooltip extention v0.8.0 - flicker fix
 *
 * https://github.com/Thrilleratplay/jQuery-Validation-Bootstrap-tooltip
 *
 * Copyright 2015 Tom Hiller
 * Released under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 */
(function($) {
  $.extend(true, $.validator, {
    prototype:{
      defaultShowErrors: function() {
        var _this = this;

        $.each(this.errorList, function(index, value) {
          var currentElement = $(value.element);
          if (currentElement.data('bs.tooltip') !== undefined) {
            currentElement.data('bs.tooltip').options.title = value.message;
          } else {
            currentElement.tooltip(_this.applyTooltipOptions(value.element, value.message));
          }

          $(value.element).removeClass(_this.settings.validClass).addClass(_this.settings.errorClass).tooltip('show');

          if (_this.settings.highlight) {
            _this.settings.highlight.call(_this, value.element, _this.settings.errorClass, _this.settings.validClass);
          }
        });

        $.each(_this.validElements(), function(index, value) {
          $(value).removeClass(_this.settings.errorClass).addClass(_this.settings.validClass).tooltip('destroy');
          if (_this.settings.unhighlight) {
            _this.settings.unhighlight.call(_this, value, _this.settings.errorClass, _this.settings.validClass);
          }
        });
      },

      applyTooltipOptions: function(element, message) {
        var options = {
          /* Using Twitter Bootstrap Defaults if no settings are given */
          animation: $(element).data('animation') || true,
          html: $(element).data('html') || false,
          placement: $(element).data('placement') || 'top',
          selector: $(element).data('animation') || false,
          title: $(element).attr('title') || message,
          trigger: $.trim('manual ' + ($(element).data('trigger') || '')),
          delay: $(element).data('delay') || 0,
          container: $(element).data('container') || false
        };
        if (this.settings.tooltip_options && this.settings.tooltip_options[element.name]) {
          $.extend(options, this.settings.tooltip_options[element.name]);
        }
        /* jshint ignore:start */
        if (this.settings.tooltip_options && this.settings.tooltip_options['all']) {
          $.extend(options, this.settings.tooltip_options['all']);
        }
        /* jshint ignore:end */

        return options;
      }
    }
  });
}(jQuery));
