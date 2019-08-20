/**
 * common.js
 *
 */


// vd() for javascript
function vd() {
  // var i = -1, l = arguments.length, args = [], fn = 'console.log(args)';
  // while(++i<l){
  //   args.push('args['+i+']');
  // };
  // fn = new Function('args',fn.replace(/args/,args.join(',')));
  // fn(arguments);
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

});



