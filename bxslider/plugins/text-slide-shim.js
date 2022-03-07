/**
 * Shim to adjust vertical aspect of text-only slides in bxslider
 * to fit the 1000x300 aspect ratio at various widths.
 */

(function ($, window) {
  var Coop_Text_Shim = function () {
    this.init();
  };

  Coop_Text_Shim.prototype = {
    init: function () {
    },

    onload: function (index) {
      var w = $('.bx-viewport').width();
      var h = parseInt(0.3 * w) + 'px';

      $('.slide.text').css('height', h);
      $('.bx-wrapper').css('height', h);
      $('.bx-viewport').css('height', h);

      var display = $('.alpha-pager.vertical').css('display');

      $('.alpha-pager.vertical').css('display', 'none');
      $('.alpha-pager.vertical').css('top', '-' + h);
      $('.alpha-pager.vertical').css('height', h);
      $('.alpha-pager.vertical').css('display', display);
    },

    reset: function ($slideEl, oldIdx, newIdx) {
      if ($slideEl.hasClass('text')) {
        var h = parseInt(0.3 * $slideEl.width()) + 'px';

        $slideEl.css('height', h);
        $('.bx-wrapper').css('height', h);
      }
    },
  };

  /**
   * Ready
   */
  $(function() {
    window.coop_slider = new Coop_Text_Shim();
  });
})(jQuery, window);
