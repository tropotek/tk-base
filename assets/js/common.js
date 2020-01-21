/**
 * common.js
 *
 */

// var_dump()
function vd() {
  console.log.apply(this, arguments);
}


jQuery(function ($) {

  //
  project_core.initSugar();
  project_core.initLinkBlur();
  project_core.initTkFormTabs();
  project_core.initGrowLikeAlerts();
  project_core.initDataConfirm();
  project_core.initDataToggle();

  project_core.initTkPanel();
  project_core.initTableDeleteConfirm();
  project_core.initMasqueradeConfirm();

  // Form Field Scripts
  project_core.initDatetimePicker();
  project_core.initDualListBox();
  project_core.initTkFileInput();

  project_core.initTkInputLock();

});



