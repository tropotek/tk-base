/**
 * The tkbase javascript init object.
 *
 * Call the methods of this object to init the javascript function needed in
 * you app.js or similar:
 * ```javascript
 *   $(function () {
 *     tkbase.initDialogConfirm();
 *     // ...
 *   });
 * ```
 *
 */

let tkConfig = {
  hostUrl: '',
  baseUrl: '',
  isProd: false,
  isAuth: false,
  // todo: refactor, all dates should be returned in yyy-mm-dd format
  dateFormat: {
    jqDatepicker: 'dd/mm/yy',
    bsDatepicker: 'dd/mm/yyyy',
  },
};

// Var dump function for debugging
function vd() {
  if (!tkConfig.debug) return;
  for (let k in arguments) console.log(arguments[k]);
}

function copyToClipboard(text) {
  if (navigator.clipboard) {
    // Modern versions of Chromium browsers, Firefox, etc.
    navigator.clipboard.writeText(text);
  } else if (window.clipboardData) {
    // Internet Explorer.
    window.clipboardData.setData('Text', text);
  } else {
    // Fallback method using Textarea.
    var textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.top = '-999999px';
    textArea.style.left = '-999999px';
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
 * after an AJAX call and HTML elements have been replaced removing
 * any initalised plugins.
 */
let tkInits = [];

/**
 * Register and execute an init function to the tkInit queue.
 *
 * To register and execute an init function:
 * ```
 *  tkRegisterInit(function() {
 *    $('forms', this).each(function() {
 *      // init form elements, etc..
 *    });
 *  });
 * ```
 * After you have replaced an element you can then call `tkInit(element)` to
 * call the init functions that have been registered.
 *
 * @param func
 * @param elm (optional) document is used by default
 * @param execute (optional) If false the function will not be executed only added
 * @returns {*}
 */
function tkRegisterInit(func, elm, execute = true) {
  tkInits.push(func);
  if (execute) {
    elm = $(elm).get(0) ?? null;
    if (!elm) elm = document;
    return func.apply(elm);
  }
}

/**
 * Execute registered init functions on an element or document.
 * Call this function to execute the tkInit queue on an element.
 *
 * ```
 * tkInit(form);
 * tkInit(table);
 * ```
 *
 * @param elm (optional) document is used by default
 */
function tkInit(elm) {
  elm = $(elm).get(0) ?? null;
  if (!elm) elm = document;
  for (var i in tkInits) {
    tkInits[i].apply(elm);
  }
}


/**
 * tkbase is the javascript object to init the tkBase script
 * In your app.js call the init functions that you will be using
 */
let tkbase = function () {
  "use strict";


  /**
   * Creates bootstrap 5 tabs around the \Tk\Form renderer groups (.tk-form-group) output
   */
  let initTkFormTabs = function () {
    if (typeof $.fn.tktabs === 'undefined') {
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
    if (typeof $.fn.bsConfirm === 'undefined') {
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
    if (typeof $.fn.datepicker === 'undefined') {
      console.warn('jquery-ui.js is not installed.');
      return;
    }

    tkRegisterInit(function () {
      let defaults = {dateFormat: tkConfig.dateFormat.jqDatepicker};
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
        let feedback = $(this).parent().find('.invalid-feedback');
        let tpl = $(`<div class="input-group" var="is-error input-group">
          <button class="btn btn-outline-secondary border-light-subtle" type="button" var="button" tabindex="-1"><i class="fa fa-fw fa-eye"></i></button>
        </div>`);
        input.before(tpl);

        input.detach();
        feedback.detach();
        $('button', tpl).before(input);
        $('button', tpl).after(feedback);

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
    if (typeof $.fn.tkInputLock === 'undefined') {
      console.warn('Plugin not loaded: tkInputLock');
      return;
    }
    tkRegisterInit(function () {
      $('input.tk-input-lock', this).tkInputLock();
    });

  };

  /**
   * Setup bsconfirm dialog for HTMX buttons
   */
  let initHtmxConfirmDialog = function () {
    if (typeof $.fn.bsConfirm === 'undefined') {
      console.warn('Plugin not loaded: bsConfirm');
      return;
    }

    $(document).on('htmx:confirm', function (e) {
      if (e.defaultPrevented) return;
      if (!e.detail.elt.hasAttribute('hx-confirm')) return;
      e.preventDefault();
      $.fn.bsConfirm({
        onConfirm: function () {
          e.detail.issueRequest(true);
        }
      }, e.detail.elt);
    });

  };


  /**
   * Tiny MCE setup
   *   See this article for how to create plugins in custom paths and see if it works
   *   Custom plugins: https://stackoverflow.com/questions/21779730/custom-plugin-in-custom-directory-for-tinymce-jquery-plugin
   */
  let initTinymce = function () {
    if (typeof (tinymce) === 'undefined') {
      console.warn('Plugin not loaded: jquery.tinymce');
      return;
    }

    function getMceElf(data) {
      // NOTE: The custom path sent to the GET request should be relative to the `/data` path
      let path = data.elfinderPath ?? '/media';
      return new tinymceElfinder({
        // connector URL (Use elFinder Demo site's connector for this demo)
        url: tkConfig.baseUrl + '/vendor/ttek/tk-base/assets/js/elfinder/connector.minimal.php?path=' + path,
        // upload target folder hash for this tinyMCE
        uploadTargetHash: 'l1_lw',
        // elFinder dialog node id
        nodeId: 'elfinder'
      });
    }

    // Default base tinymce options
    let mceDefaults = {
      license_key: 'gpl',
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
      //contextmenu: 'link image template inserttable | cell row column deletetable',
      contextmenu: false,
      extended_valid_elements: 'i[*],em[*],b[*],a[*],div[*],span[*],img[*]',
      image_advtab: true,
      statusbar: false,
      //content_security_policy: "default-src 'self'",
      skin: 'tinymce-5',

      urlconverter_callback: function (url, node, on_save) {
        if (url.startsWith(tkConfig.hostUrl)) {
          url = url.replace(tkConfig.hostUrl, '')
        }
        return url;
      }
    };

    tkRegisterInit(function () {
      $('textarea.mce, textarea.mce-min', this).each(function () {
        let el = $(this);

        // remove any existing tinymce instance
        if (typeof el.tinymce == 'function' && el.tinymce() !== null) {
          tinymce.remove('#' + el.prop('id'));
        }

        let cfg = {
          license_key: 'gpl',
          plugins: ['link', 'image', 'code', 'fullscreen'],
          contextmenu: false,
          statusbar: false,
          extended_valid_elements: 'i[*],em[*],b[*],a[*],div[*],span[*],img[*]',
        };

        if (el.is('[readonly]') || el.is('[disabled]')) {
          cfg.readonly = true;
          cfg.body_class = 'text-bg-light';
        }

        if (el.is('.mce-min')) {
          // Tiny MCE with only the default editing no upload
          //   functionality with elfinder
          el.tinymce(cfg);
        } else {
          // Full tinymce with elfinder file manager
          if (!el.is('.mce-no-fm')) {   // disable the elFinder file manager
            let elf = getMceElf(el.data());
            cfg.file_picker_callback = elf.browser;
            cfg.images_upload_handler = elf.uploadHandler;
          }
          el.tinymce($.extend(cfg, mceDefaults));
        }
      });
    });

  };  // end initTinymce()

  return {
    initDialogConfirm: initDialogConfirm,
    initDatepicker: initDatepicker,
    initPasswordToggle: initPasswordToggle,
    initDataToggle: initDataToggle,
    initTkInputLock: initTkInputLock,
    initTinymce: initTinymce,
    initTkFormTabs: initTkFormTabs,
    initHtmxConfirmDialog: initHtmxConfirmDialog,
  }
}();
