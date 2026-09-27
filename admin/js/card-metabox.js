/* Lemon Catalog Sync — "Kart Görünümü" AJAX save (independent of the post Update). */
( function () {
	'use strict';

	var config = window.lcsCardData;
	if ( ! config ) {
		return;
	}

	var AUTOSAVE_KEY = 'lcsCardAutosave';
	var busy = new WeakMap();

	function boxOf( el ) {
		return el && el.closest ? el.closest( '.lcs-card-fields' ) : null;
	}

	function inputs( box ) {
		return box.querySelectorAll( '.lcs-card-fields__input' );
	}

	function original( box, field ) {
		return box.querySelector( '[data-original="' + field + '"]' );
	}

	function isDirty( box ) {
		return Array.prototype.some.call( inputs( box ), function ( input ) {
			var orig = original( box, input.dataset.field );
			return orig && orig.value !== input.value;
		} );
	}

	function setState( box, text, type ) {
		var state = box.querySelector( '.lcs-card-fields__state' );
		if ( state ) {
			state.textContent = text;
			state.className = 'lcs-card-fields__state' + ( type ? ' is-' + type : '' );
		}
	}

	function setMessage( box, text, type ) {
		var message = box.querySelector( '.lcs-card-fields__message' );
		if ( message ) {
			message.textContent = text;
			message.className = 'lcs-card-fields__message' + ( type ? ' is-' + type : '' );
		}
	}

	// Tolerates stray output printed before the JSON body by another plugin.
	function parseResponse( text ) {
		try {
			return JSON.parse( text );
		} catch ( e ) {
			var start = text.indexOf( '{"success"' );
			if ( start > -1 ) {
				try {
					return JSON.parse( text.slice( start ) );
				} catch ( ignore ) {}
			}
		}
		return null;
	}

	function applySaved( box, data ) {
		Object.keys( data.values || {} ).forEach( function ( field ) {
			var input = box.querySelector( '.lcs-card-fields__input[data-field="' + field + '"]' );
			var orig = original( box, field );
			if ( input ) {
				input.value = data.values[ field ];
			}
			if ( orig ) {
				orig.value = data.values[ field ];
			}
		} );
		Object.keys( data.info || {} ).forEach( function ( field ) {
			var cell = box.querySelector( '[data-info="' + field + '"]' );
			if ( cell ) {
				cell.textContent = data.info[ field ];
			}
		} );
	}

	function save( box ) {
		if ( busy.get( box ) ) {
			return; // Prevents duplicate requests (double click, blur + click).
		}

		var button = box.querySelector( '.lcs-card-fields__save' );
		var nonce = box.querySelector( '.lcs-card-fields__nonce' );
		var body = new FormData();

		body.append( 'action', config.action );
		body.append( 'nonce', nonce ? nonce.value : '' );
		body.append( 'product_id', box.dataset.productId || '' );
		Array.prototype.forEach.call( inputs( box ), function ( input ) {
			body.append( input.dataset.field, input.value );
		} );

		busy.set( box, true );
		if ( button ) {
			button.disabled = true;
		}
		setState( box, config.i18n.saving, 'saving' );
		setMessage( box, '', '' );

		fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} )
			.then( function ( response ) {
				return response.text().then( function ( text ) {
					return parseResponse( text );
				} );
			} )
			.then( function ( json ) {
				if ( json && json.success ) {
					applySaved( box, json.data || {} );
					setState( box, config.i18n.saved, 'success' );
					setMessage( box, config.i18n.savedLong, 'success' );
					return;
				}
				var reason = json && json.data && json.data.message ? json.data.message : config.i18n.failedLong + ' ' + config.i18n.badResponse;
				setState( box, config.i18n.failed, 'error' );
				setMessage( box, reason, 'error' );
			} )
			.catch( function () {
				setState( box, config.i18n.failed, 'error' );
				setMessage( box, config.i18n.failedLong + ' ' + config.i18n.network, 'error' );
			} )
			.then( function () {
				busy.set( box, false );
				if ( button ) {
					button.disabled = false;
				}
			} );
	}

	function autosaveEnabled() {
		try {
			return '1' === window.localStorage.getItem( AUTOSAVE_KEY );
		} catch ( e ) {
			return false;
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest ? event.target.closest( '.lcs-card-fields__save' ) : null;
		var box = boxOf( button );
		if ( box ) {
			event.preventDefault();
			save( box );
		}
	} );

	// Enter in a card field saves the card instead of submitting the post form.
	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Enter' !== event.key || ! event.target.classList || ! event.target.classList.contains( 'lcs-card-fields__input' ) ) {
			return;
		}
		var box = boxOf( event.target );
		if ( box ) {
			event.preventDefault();
			save( box );
		}
	} );

	document.addEventListener( 'input', function ( event ) {
		if ( ! event.target.classList || ! event.target.classList.contains( 'lcs-card-fields__input' ) ) {
			return;
		}
		var box = boxOf( event.target );
		if ( box && ! busy.get( box ) ) {
			setState( box, isDirty( box ) ? config.i18n.unsaved : '', isDirty( box ) ? 'dirty' : '' );
		}
	} );

	// Optional auto-save on blur (off by default; remembered per browser).
	document.addEventListener( 'focusout', function ( event ) {
		if ( ! event.target.classList || ! event.target.classList.contains( 'lcs-card-fields__input' ) || ! autosaveEnabled() ) {
			return;
		}
		var box = boxOf( event.target );
		if ( box && isDirty( box ) ) {
			save( box );
		}
	} );

	document.addEventListener( 'change', function ( event ) {
		if ( ! event.target.classList || ! event.target.classList.contains( 'lcs-card-fields__autosave-toggle' ) ) {
			return;
		}
		try {
			window.localStorage.setItem( AUTOSAVE_KEY, event.target.checked ? '1' : '0' );
		} catch ( e ) {}
	} );

	function init() {
		var enabled = autosaveEnabled();
		Array.prototype.forEach.call( document.querySelectorAll( '.lcs-card-fields__autosave-toggle' ), function ( toggle ) {
			toggle.checked = enabled;
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
