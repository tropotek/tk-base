/**
 * Plugin: bsConfirm
 *
 * Description:
 *
 * To enable the plugin on your desired selector you can use the following script
 * that defaults to a standard javascript dialog if the plugin is not available.
 *
 * ```
 *   if (typeof $.fn.bsConfirm !== 'undefined') {
 *     $('[data-confirm]').bsConfirm();
 *   }
 * ```
 *
 * All elements containing the data-confirm attribute will have a confirm dialog on the click event.
 *
 * ```
 *   <a href="/home/page/action" title="Action Confirmation Title"
 *      data-confirm="Are you sure you want to complete this action?"
 *      data-confirm-btn="Yep" data-cancel-btn="Nuh">Action</a>
 * ```
 *
 * @author Tropotek <http://www.tropotek.com/>
 * @date 2024-11-20
 * @version 1.3
 */
;(function ($) {
    let bsConfirm = function (element, options, isStatic = false) {

        const templateList = {
            // BS3-BS4 modal template
            template4: /*html*/`
                <div class="modal fade bsConfirm-modal" tabindex="-1" role="dialog" aria-labelledby="bsConfirmModalLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="bsConfirmModalLabel">Confirm</h4>
                      </div>
                      <div class="modal-body"></div>
                      <div class="modal-footer">
                        <button class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary btn-confirm">Confirm</button>
                      </div>
                    </div>
                  </div>
                </div>`,
            // BS5+ modal template
            template5: /*html*/`
                <div class="modal fade bsConfirm-modal" tabindex="-1" role="dialog" aria-labelledby="bsConfirmModalLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="bsConfirmModalLabel">Confirm</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body"></div>
                      <div class="modal-footer">
                        <button class="btn btn-light btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary btn-confirm">Confirm</button>
                      </div>
                    </div>
                  </div>
                </div>`,
        };

        // plugin settings
        const defaults = {
            template: '',
            title: 'Confirm',
            messgage: '',
            color: '',
            confirmBtn: 'Confirm',
            cancelBtn: 'Cancel',
            onCancel: function () {},
            onConfirm: function () {},

            confirmAttr: 'confirm',      // the data-confirm attr
            headerColorMap: {
                primary: 'modal-header-primary',
                success: 'modal-header-success',
                warning: 'modal-header-warning',
                info: 'modal-header-info',
                danger: 'modal-header-danger',
            },
        };

        // Plugin private params
        let plugin = this;
        plugin.settings = {};
        let el = $(element);

        // constructor method
        plugin.init = function () {

            // only available for links and submit buttons
            if (typeof $.fn.tooltip === 'undefined') {
                console.warn('Bootstrap is required for this plugin');
                return;
            }

            $.extend(plugin.settings, defaults, options, el.data());

            // select boostrap modal template if not defined by user
            if (plugin.settings.template === '' && parseInt($.fn.tooltip.Constructor.VERSION.charAt(0)) < 5) {
                plugin.settings.template = templateList.template4;
            } else {
                plugin.settings.template = templateList.template5;
            }

            // remove any existing confirm dialogs
            $('.confirm-modal').remove();

            if (typeof el !== 'undefined') {
                let regs = /(primary|success|warning|info|danger)/.exec(el.attr('class'));
                if (regs !== null && regs.length) {
                    plugin.settings.color = regs[0];
                }
                if (el.data('color')) {
                    plugin.settings.color = el.data('color');
                }

                plugin.settings.title = (el.attr('title') ?? plugin.settings.title);

                if (!isStatic) {
                    plugin.settings.message = el.data(plugin.settings.confirmAttr) ?? '';
                } else {
                    plugin.settings.message = el.attr('hx-confirm') ?? '';
                }
            }

            if (isStatic === true) {     // called statically
                showConfirm();
            } else {            // jquery plugin on element
                el.on('click', function (e) {
                    let btn = $(this);
                    if (!btn.is('a, [type=submit]')) return;

                    $.extend(plugin.settings, {
                        onCancel: function (e) {
                            if (btn.is('a, [type=submit]')) {
                                return false;
                            }
                        },
                        onConfirm: function (e) {
                            if (btn.is('a')) {
                                document.location = btn.attr('href');
                            } else if (btn.is('[type=submit]')) {
                                let form = btn.closest('form');
                                // add the submit button name/value
                                if (btn.attr('name')) {
                                    form.append(`<input type="hidden" name="${btn.attr('name')}" value="${btn.attr('value')}">`);
                                }
                                form.submit();
                            }
                            return true;
                        },
                    });

                    showConfirm();

                    // disable default click action
                    return false;
                });
            }

        };  // END init()

        let showConfirm = function (opts) {
            // create modal element
            let modal = $(plugin.settings.template);

            // set modal markup values
            $('.modal-title', modal).text(plugin.settings.title);
            $('.modal-body', modal).html(plugin.settings.message);
            $('.btn-cancel', modal).text(plugin.settings.cancelBtn);
            $('.btn-confirm', modal).text(plugin.settings.confirmBtn);

            if (plugin.settings.color) {
                $('.modal-header', modal).addClass(plugin.settings.headerColorMap[plugin.settings.color] ?? '');
                $('.btn-confirm', modal).addClass('btn-' + plugin.settings.color);
            }

            $('.btn-cancel', modal).on('click', function (e) {
                plugin.settings.onCancel.apply(modal, arguments);
                modal.modal('hide');
            });

            $('.btn-confirm', modal).on('click', function (e) {
                plugin.settings.onConfirm.apply(modal, arguments);
                modal.modal('hide');
            });

            // Append modal to dom and show
            $('body').append(modal);

            modal.on('hidden.bs.modal', function (e) {
                modal.remove();
            })

            modal.modal('show');
        };

        plugin.init();
    };

    // add the plugin to the jQuery.fn object
    $.fn.bsConfirm = function (options, element) {
        if (this.length == 0 && typeof options === 'object') {
            new bsConfirm(element, options, true);
        }
        return this.each(function () {
            if (undefined === $(this).data('bsConfirm')) {
                let plugin = new bsConfirm(this, options);
                $(this).data('bsConfirm', plugin);
            }
        });
    }

})(jQuery);
