/**
 * Behaviour for the plugin's admin screen.
 *
 * Only used on the confirmation step, when a shop was found on both platforms and the admin
 * has to pick one. Each radio names the form it reveals and the one it hides.
 */
( function () {
	'use strict';

	function toggle( radio ) {
		var shows = document.getElementById( radio.dataset.spreadshopShows );
		var hides = document.getElementById( radio.dataset.spreadshopHides );

		if ( shows ) {
			shows.hidden = false;
		}
		if ( hides ) {
			hides.hidden = true;
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var radios = document.querySelectorAll( '[data-spreadshop-shows]' );

		Array.prototype.forEach.call( radios, function ( radio ) {
			radio.addEventListener( 'change', function () {
				toggle( radio );
			} );

			// The markup ships with one already selected; honour it without a click.
			if ( radio.checked ) {
				toggle( radio );
			}
		} );
	} );
}() );
