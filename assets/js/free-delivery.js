(function ($) {
	'use strict';

	function animateBars() {
		$('.sts-free-delivery-bar-wrap').each(function () {
			var $wrap       = $(this);
			var $fill       = $wrap.find('.sts-free-delivery-fill');
			var targetWidth = $fill.data('target-width') || '0%';
			var reached     = $wrap.data('reached') === 1 || $wrap.data('reached') === '1';

			$fill.css('width', '0%');

			setTimeout(function () {
				$fill.css('width', targetWidth);
				if (reached) {
					$fill.addClass('sts-free-delivery-fill--complete');
				}
			}, 50);
		});
	}

	$(document).ready(function () {
		animateBars();
	});

	$(document.body).on('wc_fragment_refresh updated_cart_totals', function () {
		animateBars();
	});

}(jQuery));
