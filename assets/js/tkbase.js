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

// Var dump function for debugging
function vd() {
  if (!tkConfig.debug) return;
  for(let k in arguments) console.log(arguments[k]);
}

function copyToClipboard(text) {
  if(navigator.clipboard) {
    // Modern versions of Chromium browsers, Firefox, etc.
    navigator.clipboard.writeText(text);
  } else if (window.clipboardData) {
    // Internet Explorer.
    window.clipboardData.setData('Text', text);
  } else {
    // Fallback method using Textarea.
    var textArea = document.createElement('textarea');
    textArea.value          = text;
    textArea.style.position = 'fixed';
    textArea.style.top      = '-999999px';
    textArea.style.left     = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
      if (!document.execCommand('copy')) {
        console.warn('Could not copy text to clipboard');
      }
    } catch (error) {
      console.warn('Could not copy text to clipboard');
    }
    document.body.removeChild(textArea);
  }
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


/**
 * tkRegisterInit() and tkInit()
 *
 * These functions are used to allow us to re-init elements
 * after an AJAX call and HTML has been replaced.
 * Register an init function:
 * ```
 *  tkRegisterInit(function() {
 *    // this = documnet or supplied element from tkInit(...)
 *    let forms = $('forms', this);
 *    forms.each(function() {
 *      // init form elements, etc..
 *    });
 *  });
 * ```
 * After you have replaced an element you can then call tkInit(element) to
 * call the init functions that have been registered.
 *
 * Note: callint tkInit() with no arguments uses the `document` by default.
 */
let tkInits = [];
/**
 * register and execute an init function
 * @param func
 * @param execute (optional) default true
 * @returns {*}
 */
function tkRegisterInit(func, execute = true) {
  tkInits.push(func);
  if (execute) return func.apply(document);
}
/**
 * execute registered init functions on an object/documnet
 * @param obj (optional) doument is used if not supplied
 */
function tkInit(obj) {
  if (!obj) obj = document;
  for (var i in tkInits) {
    tkInits[i].apply(obj);
  }
}


let tkbase = function () {
  "use strict";

  /**
   * Enable the sugar utils, date formatting, object exetion functions, etc
   * @link https://sugarjs.com/
   */
  let initSugar = function () {
    if (typeof Sugar === 'undefined') {
      console.warn('Plugin not loaded: Sugar');
      return;
    }
    Sugar.extend();
  };


  /**
   * Creates bootstrap 5 tabs around the \Tk\Form renderer groups (.tk-form-group) output
   */
  let initTkFormTabs = function () {
    if ($.fn.tktabs === undefined) {
      console.warn('jquery.tktabs.js is not installed.');
      return;
    }
    tkRegisterInit(function () {
      $('.tk-form', this).tktabs();
    });
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
   * Setup the jquery datepicker UI
   */
  let initDatepicker = function () {
    if ($.fn.datepicker === undefined) {
      console.warn('jquery-ui.js is not installed.');
      return;
    }

    tkRegisterInit(function () {
      let defaults = { dateFormat: tkConfig.dateFormat.jqDatepicker };
      $('input.date', this).each(function () {
        let settings = $.extend({}, defaults, $(this).data());
        $(this).datepicker(settings);
      });
    });
  };


  /**
   * Add a view/hide toggle button to a password field for touch screen access
   */
  let initPasswordToggle = function () {

    tkRegisterInit(function () {
      $('[type=password]', this).each(function () {
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
    });

  };


  /**
   * This is handy for showing and hiding elements for checkboxes:
   *   <input type="checkbox" data-toggle="hide" data-target=".children" />
   */
  let initDataToggle = function () {

    tkRegisterInit(function () {
      $('[data-toggle="hide"]', this).each(function () {
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
    });

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
    tkRegisterInit(function () {
      $('input.tk-input-lock', this).tkInputLock();
    });

  };

  /**
   * Tiny MCE setup
   *   See this article for how to create plugins in custom paths and see if it works
   *   Custom plugins: https://stackoverflow.com/questions/21779730/custom-plugin-in-custom-directory-for-tinymce-jquery-plugin
   */
  let initTinymce = function () {
    if (typeof(tinymce) === 'undefined') {
      console.warn('Plugin not loaded: jquery.tinymce');
      return;
    }

    function getMceElf(data) {
      let path = data.elfinderPath ?? '/media';
      return new tinymceElfinder({
        // connector URL (Use elFinder Demo site's connector for this demo)
        url: tkConfig.vendorOrgUrl + '/tk-base/assets/js/elfinder/connector.minimal.php?path='+ path,
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
      //content_security_policy: "default-src 'self'",

      urlconverter_callback : function (url, node, on_save) {
        let parts = url.split(tkConfig.baseUrl);
        if (parts.length > 1) {
          url = tkConfig.baseUrl + parts[1];
        }
        return url;
      }
    };

    tkRegisterInit(function () {
      // Tiny MCE with only the default editing no upload
      //   functionality with elfinder
      $('textarea.mce-min', this).tinymce({});

      // Full tinymce with elfinder file manager
      $('textarea.mce', this).each(function () {
        let el = $(this);
        el.tinymce($.extend(mceDefaults, {
          file_picker_callback : getMceElf(el.data()).browser,
        }));
      });
    });

  };  // end initTinymce()


  return {
    initSugar: initSugar,
    initDialogConfirm: initDialogConfirm,
    initDatepicker: initDatepicker,
    initPasswordToggle:initPasswordToggle,
    initDataToggle: initDataToggle,
    initTkInputLock: initTkInputLock,
    initTinymce: initTinymce,
    initTkFormTabs: initTkFormTabs,
  }
}();
