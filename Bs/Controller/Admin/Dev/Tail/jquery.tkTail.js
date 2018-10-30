/*
 * Plugin: Example
 * Version: 1.0
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2007 Michael Mifsud
 * @source http://stefangabos.ro/jquery/jquery-plugin-boilerplate-revisited/
 */

/**
 * A javascript version of the tail command
 *
 * <div class="tk-tail" id="tail" data-src="http://localhost/~user/project/tailLog.html?refresh=refresh"></div>
 *
 * The returned Text data should be the text appended to the log file since the previous call.
 * This will required knowledge of using file streams and seeking the data
 *
 * PHP Example of refresh and seek functions see: \App\Controller\Admin\Dev\Tail\Log:
 * <code>
 *   public function doRefresh(Request $request)
 *   {
 *      if (!is_readable($this->logPath)) {
 *        echo sprintf('Error: Cannot read log file: ' . $this->logPath . "\n");
 *        exit();
 *      }
 *
 *      $session = \App\Config::getInstance()->getSession();
 *      $handle = fopen($this->logPath, 'r');
 *      if ($session->get('tail-offset')) {
 *        $pos = $session->get('tail-offset');
 *        $data = stream_get_contents($handle, -1, $pos);
 *        echo htmlentities($data);
 *        $pos = ftell($handle);
 *        $session->set('tail-offset', $pos);
 *      } else {
 *        $this->doSeek($request, -1000);
 *      }
 *      exit();
 *   }
 *
 *   public function doSeek(Request $request, $seekAdjust = 0) {
 *       $session = \App\Config::getInstance()->getSession();
 *       $handle = fopen($this->logPath, 'r');
 *       fseek($handle, 0, \SEEK_END);
 *       $pos = ftell($handle);
 *       if ($seekAdjust > 0) {
 *           $pos += $seekAdjust;
 *       }
 *       if ($pos < 0) $pos = 0;
 *       $session->set('tail-offset', $pos);
 *   }
 * </code>
 *
 *
 * <code>
 *   $(document).ready(function() {
 *     // attach the plugin to an element
 *     $('#element').tail({'foo': 'bar'});
 *
 *     // call a public method
 *     $('#element').data('tail').foo_public_method();
 *
 *     // get the value of a property
 *     $('#element').data('tail').settings.foo;
 *   
 *   });
 * </code>
 *
 *
 * @bug: There is a bug where the scroll lock is enabled until the user manually scrolls the
 *       window then the button will work as expected (probably a CSS thingy)???
 *
 *
 */
(function($) {
  var tail = function(element, options) {
    // plugin vars
    var defaults = {
      refreshUrl : '',
      height: '500px',
      interval : 1000,      // Refresh interval in milliseconds 1000 = 1sec
      onRefresh : function(data) { },
      tplOutput: '<pre class="tail-out"></pre>',
      tplControls: '<div class="btn-group" role="group">' +
      '<button type="button" class="btn btn-xs btn-default t-pause" title="Pause"><i class="fa fa-pause"></i></button>' +
      '<button type="button" class="btn btn-xs btn-default t-scroll" title="Scroll Lock"><i class="fa fa-lock"></i></button>' +
      '<button type="button" class="btn btn-xs btn-default t-clear" title="Clear"><i class="fa fa-eraser"></i></button>' +
      '<button type="button" class="btn btn-xs btn-default t-fullscreen" title="Fullscreen"><i class="fa fa-arrows-alt"></i></button>' +
      '</div>'
    };
    var plugin = this;
    plugin.settings = {};

    var output = null;
    var controls = null;

    var scroll = true;
    var enable = true;
    var restart = false;


    // constructor method
    plugin.init = function() {
      plugin.settings = $.extend({}, defaults, options);

      // Code goes here
      if (!plugin.settings.refreshUrl && $(element).data('src')) {
        plugin.settings.refreshUrl = $(element).data('src');
      }

      if (!plugin.settings.refreshUrl) {
        alert('No refresh url set. Use the element attribute `data-src` or settings `refreshUrl` to set the refresh ajax url.');
      }

      $(element).css({
        position: 'relative'
      });

      // setup display window
      output = $(plugin.settings.tplOutput);
      output.css({
        overflowY: 'scroll',
        overflowX: 'auto',
        height: plugin.settings.height
      });
      $(element).append(output);

      controls = $(plugin.settings.tplControls);
      controls.css({
        position: 'absolute',
        top: '10px',
        right: '30px'
      });
      controls.find('.btn').css({
        width: '30px'
      });

      $(element).append(output);
      $(element).append(controls);

      controls.find('.t-pause').click(function () {
        var i = $(this).find('i');
        if ($(this).hasClass('active')) {
          $(this).removeClass('active');
          i.removeClass('fa-play');
          i.addClass('fa-pause');
          enable = true;
          restart = true;
        } else {
          $(this).addClass('active');
          i.removeClass('fa-pause');
          i.addClass('fa-play');
          enable = false;
        }
        $(this).blur();
      });

      controls.find('.t-scroll').click(function () {
        var i = $(this).find('i');
        if ($(this).hasClass('active')) {
          $(this).removeClass('active');
          i.removeClass('fa-unlock');
          i.addClass('fa-lock');
          scroll = true;
        } else {
          $(this).addClass('active');
          i.removeClass('fa-lock');
          i.addClass('fa-unlock');
          scroll = false;
        }
        $(this).blur();
      });

      controls.find('.t-clear').click(function () {
        $(this).blur();
        output.empty();
      });

      controls.find('.t-fullscreen').click(function () {
        var i = $(this).find('i');
        if ($(this).hasClass('active')) {
          $(this).removeClass('active');
          $(element).css({
            position: 'relative',
            width: 'auto',
            height: 'auto'
          });
          output.css({
            width: 'auto',
            height: plugin.settings.height
          });
        } else {
          $(this).addClass('active');
          $(element).css({
            position: 'fixed',
            top: 0,
            left: 0,
            bottom: 0,
            right: 0,
            zIndex: '9999'
          });
          output.css({
            width: '100%',
            height: '100%'
          });
        }
        $(this).blur();
      });

      // Start the tail loop
      doRefresh();

    };


    // private methods
    var doRefresh = function() {
      if (enable) {
        var url = plugin.settings.refreshUrl;
        if (restart) {
          url += '&seek=0';
          restart = false;
        }

        $.get(url, function (data) {
          if (data === '') return; // do nothing if no log data

          // fire user event
          plugin.settings.onRefresh.apply(output, [data]);

          // remove ascii colors/styles
          data = data.replace(/[\u001b\u009b][[()#;?]*(?:[0-9]{1,4}(?:;[0-9]{0,4})*)?[0-9A-ORZcf-nqry=><]/g, "");
          // remove non-ascii characters
          data = data.replace(/[^\x00-\x7F]/g, "");

          output.append(data);

          if (scroll) {
            output.animate({scrollTop: output.get(0).scrollHeight}, '5000', function () { /* ON ANIMATION COMPLETED */ });
            //output.scrollTop(output.get(0).scrollHeight);
          }
        });
      }
      setTimeout(doRefresh, plugin.settings.interval);
    };


    // public methods
    //plugin.foo_public_method = function() { };

    // call the "constructor" method
    plugin.init();
  };

  // add the plugin to the jQuery.fn object
  $.fn.tail = function(options) {
    return this.each(function() {
      if (undefined === $(this).data('tail')) {
        var plugin = new tail(this, options);
        $(this).data('tail', plugin);
      }
    });
  }

})(jQuery);
