/**
 * common.js
 *
 */

jQuery(function ($) {

  //
  project_core.initSugar();
  project_core.initLinkBlur();
  project_core.initTkFormTabs();
  project_core.initGrowLikeAlerts();
  project_core.initDataConfirm();
  project_core.initDataToggle();


  config.tkPanel = {template:
      '<div class="panel panel-default">\n' +
      '  <div class="panel-heading"><h4 class="panel-title"><span><i class="tp-icon"></i> <span class="tp-title"></span></span></h4></div>\n' +
      '  <div class="tp-body panel-body"></div>\n' +
      '</div>'};
  project_core.initTkPanel();
  project_core.initTableDeleteConfirm();
  project_core.initMasqueradeConfirm();

  // Form Field Scripts
  project_core.initDatetimePicker();
  project_core.initDualListBox();
  project_core.initTkFileInput();

});



