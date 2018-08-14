/**
 * The core javascript object for this site
 *
 */

var project_core = function () {
  "use strict";

  /**
   * Dual select list box renderer
   */
  var initDualListBox = function () {
    if ($.fn.DualListBox === undefined) {
      console.warn('DualListBox plugin not available.');
      return;
    }
    $('select.tk-dualSelect, select.tk-dual-select').DualListBox();
  };

  /**
   * init the file field renderer
   */
  var initTkFileInput = function () {
    if ($.fn.tkFileInput === undefined) {
      console.warn('tkFileInput plugin not available.');
      return;
    }
    $('.tk-imageinput').tkImageInput({dataUrl: config.dataUrl});
    $('.tk-multiinput').tkMultiInput({dataUrl: config.dataUrl});
    $('.tk-fileinput:not(.tk-imageinput)').tkFileInput({});

  };

  /**
   * Init the datetime plugin
   * for single dates and date range fields
   * `.date` = single date text field
   * `.input-datetimerange` = 2 text box range field group
   */
  var initDatetimePicker = function () {
    if ($.fn.datetimepicker === undefined) {
      console.warn('datetimepicker plugin not available.');
      return;
    }

    if (!config.datepickerFormat)
      config.datepickerFormat = 'dd/mm/yyyy';

    // single date
    $('.date').datetimepicker({
      format: config.datepickerFormat,
      autoclose: true,
      todayBtn: true,
      todayHighlight: true,
      initialDate: new Date(),
      minView: 2,
      maxView: 2
    });

    $('.input-daterange').each(function () {
      // TODO we need to fix the initialDate bug when the date format has the time.
      var inputGroup = $(this);
      var start = inputGroup.find('input').first();
      var end = inputGroup.find('input').last();
      start.datetimepicker({
        todayHighlight: true,
        format: config.datepickerFormat,
        autoclose: true,
        todayBtn: true,
        //initialDate: new Date(),
        initialDate: start.val(),
        minView: 2,
        maxView: 2
      });
      end.datetimepicker({
        todayHighlight: true,
        format: config.datepickerFormat,
        autoclose: true,
        todayBtn: true,
        //initialDate: new Date(),
        initialDate: end.val(),
        minView: 2,
        maxView: 2
      });

      start.datetimepicker().on('changeDate', function (e) {
        //end.datetimepicker('setStartDate', e.date);
        var startDate = start.datetimepicker('getDate');
        var endDate = end.datetimepicker('getDate');
        if (startDate > endDate) {
          end.datetimepicker('setDate', startDate);
        }
      });
      end.datetimepicker().on('changeDate', function (e) {
        //start.datetimepicker('setEndDate', e.date);
        var startDate = start.datetimepicker('getDate');
        var endDate = end.datetimepicker('getDate');
        if (endDate < startDate) {
          start.datetimepicker('setDate', endDate);
        }
      });
    });


    $('.input-datetimerange').each(function () {
      var inputGroup = $(this);
      var start = inputGroup.find('input').first();
      var end = inputGroup.find('input').last();
      start.datetimepicker({
        todayHighlight: true,
        format: config.datepickerFormat + ' hh:ii',
        autoclose: true,
        todayBtn: true,
        //startDate: new Date(),
        minuteStep: 5,
        initialDate: start.val()
      });
      end.datetimepicker({
        todayHighlight: true,
        format: 'dd/mm/yyyy hh:ii',
        autoclose: true,
        todayBtn: true,
        //startDate: new Date(),
        minuteStep: 5,
        initialDate: end.val()
      });

      start.datetimepicker().on('changeDate', function (e) {
        //end.datetimepicker('setStartDate', e.date);
        var startDate = start.datetimepicker('getDate');
        var endDate = end.datetimepicker('getDate');
        if (startDate > endDate) {
          end.datetimepicker('setDate', startDate);
        }
      });
      end.datetimepicker().on('changeDate', function (e) {
        //start.datetimepicker('setEndDate', e.date);
        var startDate = start.datetimepicker('getDate');
        var endDate = end.datetimepicker('getDate');
        if (endDate < startDate) {
          start.datetimepicker('setDate', endDate);
        }
      });
    });

  };

  /**
   * Code Mirror setup
   */
  var initCodemirror = function () {
    if (CodeMirror === undefined) {
      console.warn('CodeMirror plugin not available.');
      return;
    }
    $('textarea.code').each(function () {
      var el = this;
      this.cm = CodeMirror.fromTextArea(this, $.extend({}, {
        lineNumbers: true,
        mode: 'javascript',
        smartIndent: true,
        indentUnit: 2,
        tabSize: 2,
        autoRefresh:true,
        indentWithTabs: false,
        dragDrop: false
      }, $(this).data()) );

      $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function() {
        this.refresh();
      }.bind(el.cm));
    });
  };



  /**
   * Tiny MCE setup
   */
  var initTinymce = function () {
    if ($.fn.tinymce === undefined) {
      console.warn('tinymce plugin not available.');
      return;
    }

    /**
     * private elFinder callback function
     * @returns {boolean}
     * @private
     */
    var _elFinderPickerCallback = function (callback, value, meta) {
      tinymce.activeEditor.windowManager.open({
        file: config.siteUrl + '/vendor/ttek/tk-base/assets/js/elFinder/elfinder.html', // use an absolute path!
        title: 'File Manager',
        width: 900,
        height: 430,
        resizable: false,
        config: config
      }, {
        oninsert: function (file, fm) {
          var url, reg, info;
          // URL normalization
          url = fm.convAbsUrl(file.url);
          // Make file info
          info = file.name;
          // Provide file and text for the link dialog
          if (meta.filetype === 'file') {
            callback(url, {text: info, title: info});
          }
          // Provide image and alt text for the image dialog
          if (meta.filetype === 'image') {
            callback(url, {alt: info});
          }
          // Provide alternative source and posted for the media dialog
          if (meta.filetype === 'media') {
            callback(url);
          }
        }
      });
      return false;
    };

    var mceOpts = {
      theme: 'modern',
      plugins: [
        'advlist autolink autosave link image lists charmap print preview hr anchor',
        'searchreplace code fullscreen insertdatetime media nonbreaking codesample',
        'table directionality emoticons template paste textcolor colorpicker textpattern visualchars visualblocks'
      ],
      toolbar1: 'undo redo | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect | bullist numlist | outdent indent',
      toolbar2: 'cut copy paste searchreplace | link unlink anchor image media | hr subscript superscript | forecolor backcolor blockquote',
      toolbar3: 'table | visualchars visualblocks ltr rtl | nonbreaking insertdatetime | charmap emoticons | print preview | removeformat fullscreen code codesample',
      content_css: [
        '//fonts.googleapis.com/css?family=Lato:300,300i,400,400i'
        , '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css'
      ],
      menubar: false,
      toolbar_items_size: 'small',
      image_advtab: true,
      content_style: 'body {padding: 10px}',
      convert_urls: false,
      browser_spellcheck: true,
      file_picker_callback: _elFinderPickerCallback
    };
    $('textarea.mce, textarea.mce-med, textarea.mce-min').each(function () {
      var el = $(this);
      var opts = $.extend({}, mceOpts, {});
      if (el.hasClass('mce-min')) {
        opts = $.extend({}, opts, {
          plugins: ['advlist autolink autosave link image lists charmap hr anchor code textcolor colorpicker textpattern'],
          toolbar1: 'bold italic underline strikethrough | alignleft aligncenter alignright ' +
          '| bullist numlist | link unlink | removeformat code',
          toolbar2: '',
          toolbar3: ''
        });
      } else if (el.hasClass('mce-med')) {
        opts = $.extend({}, opts, {
          //plugins: ['advlist autolink autosave link image lists charmap hr anchor code textcolor colorpicker textpattern'],
          plugins: [
            'advlist autolink autosave link image lists charmap print preview hr anchor',
            'searchreplace code fullscreen insertdatetime media nonbreaking codesample',
            'table directionality emoticons template paste textcolor colorpicker textpattern visualchars visualblocks'
          ],
          toolbar1: 'bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright ' +
          '| bullist numlist | link unlink | removeformat',
          toolbar2: '',
          toolbar3: '',
          menubar: true
        });

        opts.height = el.data('height') ? el.data('height') : 400;
      } else {
        opts.height = el.data('height') ? el.data('height') : 500;
      }
      el.tinymce(opts);
    });
  };


  /**
   * remove focus on menu links
   */
  var initLinkBlur = function () {
    $('body').on('click', 'a[role=tab]', function () {
      $(this).blur();
    });
    //$('a[role=tab]').click(function() { $(this).blur(); });
  };

  /**
   * TODO: use data-confirm
   */
  var initMasqueradeConfirm = function () {
    $('body').on('click', '.tk-msq, .tk-masquerade', function () {
      return confirm('You are about to masquerade as the selected user?');
    });
  };

  /**
   * TODO: use data-confirm
   */
  var initTableDeleteConfirm = function () {
    $('body').on('click', '.tk-remove', function () {
      return confirm('Are you sure you want to remove this item?');
    });
  };

  /**
   * Now we can have a button confirmatino just by adding an attribute
   *  Eg:
   *    <a href="#" class="btn" data-confirm="Are you sure you want to do this?">Delete</a>
   */
  var initDataConfirm = function () {
    $('body').on('click', '[data-confirm]', function () {
      return confirm($(this).data('confirm'));
    });
  };


  /**
   * Create a standard bootstrap alert box and then add the 'growl' class to the alert div
   * and the alert will react similar to growl type alerts
   */
  var initGrowLikeAlerts = function () {
    // Growl like alert messages that fade out.

    $('.tk-alert-container').each(function () {
      var growlContainer = $('<div class="tk-growl-container"></div>');
      var alertContainer = $(this);
      alertContainer.before(growlContainer);

      growlContainer.updateAlerts = function () {
        alertContainer.find('.alert.growl').each(function () {
          var alert = $(this);
          alert.detach().appendTo(growlContainer);
        });

        $(this).find('.alert').not('.hiding').each(function () {
          var a = $(this);
          $(this).addClass('hiding');
          setTimeout(function () {
            a.fadeOut(1000, function() { $(this).remove(); });
          }, 4000);
        });
      };
      growlContainer.updateAlerts();
      // TODO: make this a plugin so we can dynamically add the alers from other scripts
      function addAlert(msg, type) {
        var alert = $('<div class="alert alert-'+type+' growl">\n' +
          '    <button class="close noblock" data-dismiss="alert">&times;</button>\n' +
          //'    <h4><i choice="icon" var="icon"></i> <strong var="title">This is a test</strong></h4>\n' +
          '    <span>'+msg+'</span>\n' +
          '  </div>');
        growlContainer.append(alert);
        growlContainer.updateAlerts();
      }
      // setTimeout(function () {
      //   addAlert('This is a test message', 'info');
      // }, 1000);
    });

  };


  /**
   * Create a bootstrap 3 panel around a div. Update the template to add your own panel
   * Div Eg:
   *   <div class="tk-panel" data-panel-title="Panel Title" data-panel-icon="fa fa-building-o"></div>
   *
   */
  var initTkPanel = function () {

    if (config.tkPanel === undefined) {
      config.tkPanel = {};
    }
    if (config.tkPanel.template === undefined) {
      config.tkPanel.template =
        '<div class="panel panel-default">\n' +
        '  <div class="panel-heading"><i class="tp-icon"></i> <span class="tp-title"></span></div>\n' +
        '  <div class="tp-body panel-body"></div>\n' +
        '</div>';
    }

    $('.tk-panel').each(function () {
      var element = $(this);
      element.hide();
      var defaults = {
        panelTemplate: config.tkPanel.template
      };
      var settings = $.extend({}, defaults, element.data());
      if (settings.panelTitle === undefined && $('.page-header').length)
        settings.panelTitle = $('.page-header').text();

      var tpl = $(settings.panelTemplate);
      tpl.hide();
      if (settings.panelIcon !== undefined) {
        tpl.find('.tp-icon').addClass(settings.panelIcon);
      }
      if (settings.panelTitle !== undefined) {
        tpl.find('.tp-title').text(settings.panelTitle);
      }
      element.before(tpl);
      element.detach();
      tpl.find('.tp-body').append(element);
      element.show();
      tpl.show();


    });
  };


  /**
   * Creates bootstrap tabs around the \Tk\Form renderer output
   */
  var initTkFormTabs = function () {
    // create bootstrap tab elements around a tabbed form
    $('.formTabs').each(function (id, tabContainer) {
      var ul = $('<ul class="nav nav-tabs"></ul>');
      var errorSet = false;

      $(tabContainer).find('.tab-pane').each(function (i, tbox) {
        var name = $(tbox).attr('data-name');
        var li = $('<li class="nav-item"></li>');
        var a = $('<a class="nav-link"></a>');
        a.attr('href', '#' + tbox.id);
        a.attr('data-toggle', 'tab');
        a.text(name);
        li.append(a);

        // Check for errors
        if ($(tbox).find('.has-error').length) {
          li.addClass('has-error');
        }
        if (i === 0) {
          $(tbox).addClass('active');
          li.addClass('active');
          a.addClass('active');
        }
        ul.append(li);
      });
      $(tabContainer).prepend(ul);
      $(tabContainer).find('li.has-error a');

      //$(tabContainer).find('li.has-error a').tab('show'); // shows last error tab
      $(tabContainer).find('li.has-error a').first().tab('show');   // shows first error tab
    });

    // Deselect tab
    $('.formTabs li a').on('click', function (e) {
      $(this).trigger('blur');
    });

  };

  /**
   *
   */
  var initDataToggle = function () {

    $('[data-toggle="hide"]').each(function () {
      var el = $(this);
      var target = $(el.data('target'));
      target.each(function() {
        $(this).hide();
      });
      el.on('click', function () {
        target.toggle();
      })
    });
    $('[data-toggle="show"]').each(function () {
      var el = $(this);
      var target = $(el.data('target'));
      target.each(function() {
        $(this).show();
      });
      el.on('click', function () {
        target.toggle();
      })
    });
  };

  return {
    initDatetimePicker: initDatetimePicker
    , initLinkBlur: initLinkBlur
    , initTkFileInput: initTkFileInput
    , initDualListBox: initDualListBox
    , initCodemirror: initCodemirror
    , initTinymce: initTinymce
    , initMasqueradeConfirm: initMasqueradeConfirm
    , initTableDeleteConfirm: initTableDeleteConfirm
    , initGrowLikeAlerts: initGrowLikeAlerts
    , initTkPanel: initTkPanel
    , initDataToggle: initDataToggle
    , initTkFormTabs: initTkFormTabs
    , initDataConfirm: initDataConfirm
  }

}();




