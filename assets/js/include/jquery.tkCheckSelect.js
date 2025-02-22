/**
 * @name tkCheckSelect
 * @version 1.0.0
 * @date 2024-11-01
 * @author Tropotek <http://www.tropotek.com/>
 * @license Copyright 2007 Tropotek
 *
 * Description:
 *   {Add a good description so you can identify the plugin when reading the code.}
 *
 * ```javascript
 *   $(document).ready(function() {
 *     // attach the plugin to an element
 *     $('#element').tkCheckSelect({'foo': 'bar'});
 *
 *     // call a public method
 *     $('#element').data('tkCheckSelect').foo_public_method();
 *
 *     // get the value of a property
 *     $('#element').data('tkCheckSelect').settings.foo;
 *
 *   });
 * ```
 *
 * Note: when changing a select value via js, trigger the `change` event manually:
 * ```
 *   $(select).val('hold').trigger('change');
 * ```
 */

;(function ($) {

  /**
   * @param Element element
   * @param options
   */
  let tkCheckSelect = function (el, options) {

    let plugin = this;
    let selectEl = $(el); // reference to the jQuery version of DOM element
    plugin.settings = {};

    // plugin's default options
    let defaults = {
      search: true,
      selectAll: true,
      dropdownTemplate: `
        <div class="dropdown tkCheckSelect">
          <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-auto-close="outside" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span><span class="btn-text">-- Select --</span> &nbsp; <i class="fa fa-caret-down"></i></span>
          </button>
          <ul class="dropdown-menu" aria-labelledby="dropdownMenuOffset">
            <li class="dropdown-item search" style="position: relative;">
              <input type="text" class="input-search" style="width: 100%;" placeholder="Search..."/>
              <a href="#" class="btn-clear" style="position: absolute;top: 8px; right: 25px;"><i class="fa fa-times"></i></a>
            </li>
            <li class="dropdown-item select-all">
              <label>
                <input type="checkbox" class="select-all-checkbox" /> <span>-- Select All --</span>
              </label>
            </li>
          </ul>
        </div>`,
      optionTemplate: `
    <li class="dropdown-item option">
      <label><input type="checkbox"> <span class="text"></span></label>
    </li>
      `,
    };

    // the checkbox dropdown element
    let checkEl = null;
    let placeholder = 'Select';

    // the "constructor" method that gets called when the object is created
    plugin.init = function () {

      // the plugin's final properties are the merged default and
      // user-provided options (if any)
      plugin.settings = $.extend({}, defaults, selectEl.data(), options);

      // check we have a valid select element
      if (!selectEl.is('select')) {
        return
      }
      if (!selectEl.prop('multiple')) {
        console.warn('element does not have the multiple attribute');
        return;
      }

      // create checkSelect el and hide/show search and select all
      checkEl = $(plugin.settings.dropdownTemplate);

      // search events
      if (!plugin.settings.search) {
        $('li.search', checkEl).remove();
      } else {
        $('.btn-clear', checkEl).on('click', function () {   // clear search
          $('.input-search', checkEl).val('').trigger('keyup');
        });
        $('.input-search', checkEl).on('keyup', function () {   // search input
          var terms = $(this).val();
          var list = $('.option', checkEl);
          if (!terms) {
            list.show();
            return;
          }
          list.hide();
          list.filter(function () {
            return $(this).text().toLowerCase().indexOf(terms.toLowerCase()) >= 0;
          }).show();
        });
      }

      // select all events
      if (!plugin.settings.selectAll) {
        $('li.select-all', checkEl).remove();
      }
      $('.select-all-checkbox', checkEl).on('click', function () {   // clear search
        $('.option [type=checkbox]', checkEl).prop('checked', $(this).prop('checked'));
        $('.option [type=checkbox]', checkEl).first().trigger('change');
      });

      // Set the placeholder button text
      if (selectEl.attr('placeholder')) {
        placeholder = selectEl.attr('placeholder');
      }
      $('button .btn-text', checkEl).text(placeholder);

      // show checkbox select element
      selectEl.hide().after(checkEl);

      // add checkbox select events
      selectEl.on('change', updateFromSelect).trigger('change');
      $('.option [type=checkbox]', checkEl).on('change', updateFromDropdown);

    };

    let updateFromSelect = function (e) {
      // update the dropdown menu when select changed
      $('.option', checkEl).remove();
      $('option', this).each(function () {
        let optEl = $(plugin.settings.optionTemplate);
        $('.text', optEl).text($(this).text());
        $('[type=checkbox]', optEl).data('value', $(this).val()).attr('data-value', $(this).val());
        if ($(this).is(':selected')) {
          $('[type=checkbox]', optEl).prop('checked', true);
        }
        $('.dropdown-menu', checkEl).append(optEl);
      });

      updateButton();
    };

    let updateFromDropdown = function (e) {
      // update select elm when checkbox changed
      let vals = [];
      $('.option [type=checkbox]:checked', checkEl).each(function () {
        vals.push($(this).data('value'));
      });
      selectEl.val(vals);

      updateButton();
    };

    let updateButton = function() {
      if (selectEl.val().length) {
        $('button', checkEl).removeClass('btn-light').addClass('btn-dark');
      } else {
        $('button', checkEl).removeClass('btn-dark').addClass('btn-light');
      }
    };

    // init plugin
    plugin.init();
  };


  // add the plugin to the jQuery.fn object
  $.fn.tkCheckSelect = function (options) {
    return this.each(function () {
      if (undefined === $(this).data('tkCheckSelect')) {
        let plugin = new tkCheckSelect(this, options);
        $(this).data('tkCheckSelect', plugin);
      }
    });
  }
})(jQuery);
