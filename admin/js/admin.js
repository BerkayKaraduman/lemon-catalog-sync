/* Lemon Catalog Sync — admin helpers (vanilla JS, no build step). */
( function () {
	'use strict';

	var i18n = window.lcsAdmin || {};

	// Prevent double submits on long-running actions and give feedback.
	document.querySelectorAll( 'form[data-lcs-busy]' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function () {
			var kind = form.getAttribute( 'data-lcs-busy' );
			var label = 'sync' === kind ? i18n.syncing : i18n.testing;
			var buttons = Array.prototype.slice.call( form.querySelectorAll( 'button[type="submit"]' ) );

			if ( form.id ) {
				buttons = buttons.concat( Array.prototype.slice.call( document.querySelectorAll( 'button[form="' + form.id + '"]' ) ) );
			}

			// Defer so the browser still submits the clicked button.
			window.setTimeout( function () {
				buttons.forEach( function ( button ) {
					button.disabled = true;
					if ( label ) {
						button.textContent = label;
					}
				} );
			}, 0 );
		} );
	} );
}() );
