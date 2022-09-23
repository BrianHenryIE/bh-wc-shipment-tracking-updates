(function( $ ) {
	'use strict';

	// Click handler for marking orders paid when on the my-account orders list.
	// Add a confirmation dialog before marking orders completed, since some customer seem to have inadvertently done so.
	$(function() {
		$('a.mark-completed').on('click', function () {
			return confirm('Are you sure you want to mark this order completed?');
		});
	});

})( jQuery );
