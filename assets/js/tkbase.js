/**
 * The tkbase javascript init object.
 *
 * Call the methods of this object to init the javascript function needed in
 * you app.js or similar:
 * ```javascript
 *   $(function () {
 *     tkbase.initSugar();
 *     tkbase.initDialogConfirm();
 *     // ...
 *   });
 * ```
 *
 * NOTE: Requires the tk lib config defined in the site, something like this:
 * ```javascript
 * let config = {
 *   baseUrl        : '/Projects/tk8base',
 *   dataUrl        : '/Projects/tk8base/data',
 *   templateUrl    : '/Projects/tk8base/html',
 *   vendorUrl      : '/Projects/tk8base/vendor',
 *   vendorOrgUrl   : '/Projects/tk8base/vendor/ttek',
 *   debug          : true,
 *   dateFormat: {
 *     jqDatepicker : 'dd/mm/yy',
 *     bsDatepicker : 'dd/mm/yyyy',
 *     sugarjs      : '%d/%m/%Y',
 *   }
 * }
 * ```
 *
 * To enable these functions include the following JS and CSS:
 *
 * CSS:
 * ```html
 *   <link rel="stylesheet" href="/vendor/ttek/tk-base/assets/css/fontawesome/css/fontawesome.min.css" />
 *   <link rel="stylesheet" href="/vendor/ttek/tk-base/assets/js/include/jquery-ui/jquery-ui.min.css" />
 *   <link rel="stylesheet" href="/vendor/studio-42/elfinder/css/elfinder.full.css" />
 * ```
 *
 * Javascript:
 * ```html
 *   <script src="/vendor/ttek/tk-base/assets/js/include/jquery-ui/external/jquery/jquery.min.js"></script>
 *   <script src="/vendor/ttek/tk-base/assets/js/include/jquery-ui/jquery-ui.min.js"></script>
 *   <script src="/vendor/ttek/tk-base/assets/js/include/htmx.min.js"></script>
 *   <script src="/vendor/ttek/tk-base/assets/js/include/sugar.min.js"></script>
 *   <script src="/vendor/ttek/tk-base/assets/js/include/jquery.bsConfirm.js"></script>
 *   <script src="/vendor/ttek/tk-base/assets/js/include/sugar.min.js"></script>
 *   <script src="/vendor/ttek/tk-base/assets/js/include/jquery.tkInputLock.js"></script>
 *
 *   <script src="/vendor/tinymce/tinymce/tinymce.min.js"></script>
 *   <script src="/vendor/ttek/tk-base/assets/js/elfinder/tinymceElfinder.js"></script>
 *   <script src="/vendor/studio-42/elfinder/js/elfinder.min.js"></script>
 *   <script src="/vendor/ttek/tk-base/assets/js/include/jquery.tinymce.min.js"></script>
 *
 *   <script src="/vendor/ttek/tk-base/assets/js/tkbase.js"></script>
 *   <script src="/html/assets/app.js"></script>
 * ```
 */

/**
 * When using AJAX queries to replace forms and tables
 * you can trigger the init function on the element like:
 * ```
 *   $('body').trigger(EVENT_INIT);
 * ```
 * This will then run the tk-init event and all registered scripts should execute
 *
 * @type {string}
 */
const EVENT_INIT            = 'tk-init';        // called to init Tk dynamic elements
const EVENT_INIT_FORM       = 'tk-init-form';
const EVENT_INIT_TABLE      = 'tk-init-table';

// Use this to trigger all events
const EVENT_INIT_ALL        = `${EVENT_INIT} ${EVENT_INIT_FORM} ${EVENT_INIT_TABLE}`;


// Var dump function for debugging
function vd() {
  if (!config.debug) return;
  for(let k in arguments) console.log(arguments[k]);
}

function clearForm(form) {
  $(':input', form).each(function () {
    var type = this.type;
    var tag = this.tagName.toLowerCase(); // normalize case
    if (type == 'text' || type == 'password' || tag == 'textarea')
      this.value = "";
    else if (type == 'checkbox' || type == 'radio')
      this.checked = false;
    else if (tag == 'select')
      this.selectedIndex = 1;
  });
};



let tkbase = function () {
  "use strict";

  /**
   * Enable the sugar utils, date formatting, object exetion functions, etc
   * @link https://sugarjs.com/
   */
  let initSugar = function () {
    if (typeof Sugar === 'undefined') {
      //console.warn('Plugin not loaded: Sugar');
      return;
    }
    Sugar.extend();
  };


  /**
   * Now we can have a button confirmation just by adding an attribute
   *  Eg:
   *    <a href="#" class="btn" data-confirm="Are you sure you want to do this?">Delete</a>
   */
  let initDialogConfirm = function () {
    if ($.fn.bsConfirm === undefined) {
      $(document).on('click', '[data-confirm]', function () {
        return confirm($('<p>' + $(this).data('confirm') + '</p>').text());
      });
    } else {
      $('[data-confirm]').bsConfirm({});
    }
  };


  /**
   * Add a view/hide toggle button to a password field for touch screen access
   */
  let initPasswordToggle = function () {
    function init() {
      $('[type=password]').each(function () {
        let input = $(this);
        let tpl = $(`<div class="input-group" var="is-error input-group">
          <button class="btn btn-outline-secondary border-light-subtle" type="button" var="button" tabindex="-1"><i class="fa fa-fw fa-eye"></i></button>
        </div>`);
        input.before(tpl);
        input.detach();
        $('button', tpl).before(input);
        $('button', tpl).on('click', function () {
          let icon = $('.fa', this);
          if (icon.is('.fa-eye')) {
            icon.removeClass('fa-eye');
            icon.addClass('fa-eye-slash')
            input.attr('type', 'text');
          } else {
            icon.removeClass('fa-eye-slash');
            icon.addClass('fa-eye')
            input.attr('type', 'password');
          }
        });
      });
    }
    init();
    $('body').on(EVENT_INIT_FORM, init);
  };


  /**
   * This is handy for showing and hiding elements for checkboxes:
   *   <input type="checkbox" data-toggle="hide" data-target=".children" />
   */
  let initDataToggle = function () {
    function init() {
      $('[data-toggle="hide"]').each(function () {
        let target = $($(this).data('target'));
        target.each(function () {
          $(this).hide();
        });
        $(this).on('click', function () {
          target.toggle();
        })
      });
      $('[data-toggle="show"]').each(function () {
        let target = $($(this).data('target'));
        target.each(function () {
          $(this).show();
        });
        $(this).on('click', function () {
          target.toggle();
        })
      });
    }
    init();
    $('body').on(EVENT_INIT_FORM, init);
  };


  /**
   * Add an edit lock button to text fields
   * So the user has to click the unlock button b4 editing
   */
  let initTkInputLock = function () {
    if ($.fn.tkInputLock === undefined) {
      console.warn('Plugin not loaded: tkInputLock');
      return;
    }
    function init() {
      $('input.tk-input-lock').tkInputLock();
    }
    init();
    $('body').on(EVENT_INIT_FORM, init);
  };


  /**
   * Tiny MCE setup
   *   See this article for how to create plugins in custom paths and see if it works
   *   Custom plugins: https://stackoverflow.com/questions/21779730/custom-plugin-in-custom-directory-for-tinymce-jquery-plugin
   */
  let initTinymce = function () {
    if (tinymce === undefined) {
      console.warn('Plugin not loaded: jquery.tinymce');
      return;
    }

    function getMceElf(data) {
      let path = data.elfinderPath ?? '/media';
      return new tinymceElfinder({
        // connector URL (Use elFinder Demo site's connector for this demo)
        url: config.vendorOrgUrl + '/tk-base/assets/js/elfinder/connector.minimal.php?path='+ path,
        // upload target folder hash for this tinyMCE
        uploadTargetHash: 'l1_lw',
        // elFinder dialog node id
        nodeId: 'elfinder'
      });
    }

    // Default base tinymce options
    let mceDefaults = {
      height: 500,
      plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'media', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
      ],
      toolbar1:
        'bold italic strikethrough | blocks | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | link image media | removeformat code fullscreen',
      content_css: [
        '//cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css'
      ],
      content_style: 'body {padding: 15px; font-family:Helvetica,Arial,sans-serif; font-size:16px; }',
      extended_valid_elements: 'i[*],em[*],b[*],a[*],div[*],span[*],img[*]',
      statusbar: false,
      image_advtab: true,

      urlconverter_callback : function (url, node, on_save) {
        let parts = url.split(config.baseUrl);
        if (parts.length > 1) {
          url = config.baseUrl + parts[1];
        }
        return url;
      }
    };

    function init () {
      let form = 'form.tk-form';

      // Tiny MCE with only the default editing no upload
      //   functionality with elfinder
      $('textarea.mce-min', form).tinymce({});

      // Full tinymce with elfinder file manager
      $('textarea.mce', form).each(function () {
        let el = $(this);
        el.tinymce($.extend(mceDefaults, {
          file_picker_callback : getMceElf(el.data()).browser,
        }));
      });
    };

    init();
    $('body').on(EVENT_INIT_FORM, init);
  };  // end initTinymce()


  /**
   * Code Mirror setup
   * @todo Implement this into our javascript and css textarea editors
   */
  let initCodemirror = function () {
    if (typeof CodeMirror === 'undefined') {
      console.warn('Plugin not loaded: CodeMirror');
      return;
    }

    function init() {
      let el = this;
      this.cm = CodeMirror.fromTextArea(this, $.extend({}, {
        lineNumbers: true,
        mode: 'javascript',
        smartIndent: true,
        indentUnit: 2,
        tabSize: 2,
        autoRefresh: true,
        indentWithTabs: false,
        dragDrop: false
      }, $(this).data()));

      $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
        this.refresh();
      }.bind(el.cm));
    };

    init();
    $('body').on(EVENT_INIT_FORM, init);
  };  // end initCodemirror()

  return {
    initSugar: initSugar,
    initDialogConfirm: initDialogConfirm,
    initPasswordToggle:initPasswordToggle,
    initDataToggle: initDataToggle,
    initTkInputLock: initTkInputLock,
    initTinymce: initTinymce,
    initCodemirror: initCodemirror,
  }
}();




