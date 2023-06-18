/**
 * @name tkInputLock
 * @version 1.0
 * @author Tropotek http://www.tropotek.com/
 *
 * Use this script to create a lockable input text field.
 * The user must click the unlock button before they are able to edit its value
 *
 * To access plugin functions:
 * ```
 *   $(document).ready(function() {
 *     // attach the plugin to an element
 *     $('#element').tkInputLock({'foo': 'bar'});
 *
 *     // call a public method
 *     $('#element').data('tkInputLock').foo_public_method();
 *
 *     // get the value of a property
 *     $('#element').data('tkInputLock').settings.foo;
 *   });
 * ```
 */

(function ($) {
  let tkInputLock = function (element, options) {

    let defaults = {
      lockIcon: 'fa-lock',
      unlockIcon: 'fa-unlock',
      groupTpl: /*html*/`
<div class="input-group tki-iga">
    <button type="button" class="btn btn-outline-secondary border" title="Click to edit field" tabindex="-1"><i class="fa"></i></button>
</div>
`,
    };

    let $element = $(element);
    let plugin = this;
    plugin.settings = {};
    let form = null;

    // constructor method
    plugin.init = function () {
      if ($element.is('.is-invalid')) return;

      plugin.settings = $.extend({}, defaults, $(element).data(), options);
      form = $(element).closest('form');

      let group = $(plugin.settings.groupTpl);
      group.insertBefore($element);

      $element.detach();
      group.prepend($element);

      $('button', group).on('click', function () {
        $(this).blur();
        let editable = $('button .fa', group).is('.'+plugin.settings.unlockIcon);
        if (plugin.settings.readonly && editable) return;

        if ($('input', group)) {
          updateInput(group, !editable);
        }
      });

      $element.on('change', function () {
        updateInput(group, $('input', group).val());
      });
      updateInput(group, !$('input', group).val());

    };  // END init()

    let updateInput = function(group, editable) {
      if (editable) {
        $('button .fa', group).removeClass(plugin.settings.lockIcon).addClass(plugin.settings.unlockIcon);
        $('input', group).css({'pointerEvents': 'inherit', 'background': 'inherit' });
      } else {
        $('button .fa', group).removeClass(plugin.settings.unlockIcon).addClass(plugin.settings.lockIcon);
        $('input', group).css({'pointerEvents': 'none', 'background': '#EEE' });
      }
    };

    plugin.init();
  };

  // add the plugin to the jQuery.fn object
  $.fn.tkInputLock = function (options) {
    return this.each(function () {
      if (undefined === $(this).data('tkInputLock')) {
        let plugin = new tkInputLock(this, options);
        $(this).data('tkInputLock', plugin);
      }
    });
  }

})(jQuery);
