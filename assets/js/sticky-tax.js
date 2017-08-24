//********************************************************
// Add our newly selected item to the box.
//********************************************************
function stickyTaxAddItem( termID, termName, termType ) {

	// Trim my term name to be safe.
	termName = jQuery.trim( termName );

	// Set an empty var.
	var newItem = '';

	// Build my new list item.
	newItem += '<li id="item-' + termID + '" data-term-id="' + termID + '" data-term-name="' + termName + '" class="term-sticky-list-item">';
	newItem += '<label class="list-item-label" for="list-item-' + termID + '">';
	newItem += '<input type="checkbox" name="sticky-tax-term-id[]" id="list-item-' + termID + '" value="' + termID + '" />'
	newItem += termName;
	newItem += '</li>';

	// Add it to the end of the list.
	jQuery( 'ul#list-' + termType ).append( newItem );

	// And hide the note saying there are none.
	jQuery( 'div#term-sticky-' + termType + '-group' ).find( 'p.term-sticky-list-empty' ).removeClass( 'term-sticky-list-show' ).addClass( 'term-sticky-list-hide' );
}

//********************************************************
// Remove a selected item from the box.
//********************************************************
function stickyTaxRemoveItem( termName, termType ) {

	// Trim my term name to be safe.
	termName = jQuery.trim( termName );

	// Find the item in the term group with our term name.
	jQuery( 'div#term-sticky-' + termType + '-group li[data-term-name="' + termName + '"]' ).filter( function( index ) {

		// Uncheck the box to be safe.
		jQuery( this ).find( 'input' ).prop( 'checked', false );

		// Find our list item and remove it.
		jQuery( this ).remove();
	});

	// Now determine if we have
	var itemCount = jQuery( 'ul#list-' + termType ).children().length;

	// And add the note saying there are none if we hit zero.
	if ( itemCount < 1 ) {
		jQuery( 'div#term-sticky-' + termType + '-group' ).find( 'p.term-sticky-list-empty' ).removeClass( 'term-sticky-list-hide' ).addClass( 'term-sticky-list-show' );
	}

}

//********************************************************
// Start the engines.
//********************************************************
jQuery(document).ready(function($) {

//********************************************************
// Quick helper to check for an existance of an element.
//********************************************************
	$.fn.divExists = function(callback) {

		// Slice some args.
		var args = [].slice.call( arguments, 1 );

		// Check for length.
		if ( this.length ) {
			callback.call( this, args );
		}
		// Return it.
		return this;
	};

//********************************************************
// Set some variables to use later.
//********************************************************
	var termID;
	var termName;
	var termType;
	var boxNonce;
	var tagName;
	var tagButton;

//********************************************************
// Check for category clicking.
//********************************************************
	$( 'div#categorydiv' ).divExists( function() {

		// Look for the changing of an input.
		$( 'ul.categorychecklist' ).on( 'change', 'input', function( event ) {

			// Set my term ID.
			termID  = $( this ).attr( 'value' );

			// Get my term name.
			termName = $.trim( $( this ).parent( 'label' ).text() );

			// Check if we've been added or not.
			if ( $( this ).is( ':checked' ) ) {
				stickyTaxAddItem( termID, termName, 'category' );
			} else {
				stickyTaxRemoveItem( termName, 'category' );
			}

		});
	});

//********************************************************
// Check for post tag clicking.
//********************************************************
	$( 'div#tagsdiv-post_tag' ).divExists( function() {

		// Watch for any clicking inside that div.
		$( '#tagsdiv-post_tag' ).on( 'click', function( event ) {

			// Check for the add new button.
			if ( $( event.target ).is( 'input.tagadd' ) ) {

				// Get my term name.
				termName = $( '.tagchecklist button:last' ).parent( 'span' ).first().contents().filter( function() {
					return this.nodeType == 3;
				}).text();

				// Trim our term name to be safe.
				termName = $.trim( termName );

				// And fetch my nonce.
				boxNonce = $( 'input#sticky-tax-nonce' ).val();

				// Set my data array,
				var data = {
					action:    'stickytax_get_id_from_name',
					term_name: termName,
					term_type: 'post_tag',
					nonce:     boxNonce,
				};

				// Now handle the response.
				jQuery.post( ajaxurl, data, function( response ) {

					// Handle the failure.
					if ( response.success !== true ) {
						return false;
					}

					// We got a term ID, so use it.
					if ( response.data.term_id !== '' ) {
						stickyTaxAddItem( response.data.term_id, termName, 'post_tag' );
					}

				});
			}

			// Check for the removal button.
			if ( $( event.target ).is( 'button.ntdelbutton' ) ) {

				// Get my term name.
				termName = $( event.target ).parent( 'span' ).first().contents().filter( function() {
					return this.nodeType == 3;
				}).text();

				// And remove the item.
				stickyTaxRemoveItem( termName, 'post_tag' );
			}
		});

	});

//********************************************************
// we are done here. go home
//********************************************************
});
