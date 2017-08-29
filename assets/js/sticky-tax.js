/**
 * Scripting for the various message actions.
 *
 * @package LiquidWeb\StickyTax
 * @author  Liquid Web
 */

( function ( window, document ) {
	'use strict';

	var categoryMetabox = document.getElementById( 'side-sortables' ),
		termLists = {},

		/**
		 * Given a taxonomy name, return the corresponding list element.
		 *
		 * @param {string} tax - The taxonomy to retrieve.
		 * @return {Element} The HTML Element object.
		 */
		getTaxonomyTermList = function ( tax ) {
			if ( ! termLists[ tax ] ) {
				termLists[ tax ] = document.querySelector( '#term-sticky-' + tax + '-group .term-sticky-list' );
			}

			return termLists[ tax ];
		},

		/**
		 * Add a new term into the list of available taxonomy terms.
		 *
		 * @param {int}    id   - The taxonomy term ID.
		 * @param {string} name - The name (label) for the term.
		 * @param {string} tax  - The term's taxonomy.
		 */
		addItem = function ( id, name, tax ) {
			var list = getTaxonomyTermList( tax ),
				li = document.createElement( 'li' ),
				label = document.createElement( 'label' ),
				input = document.createElement( 'input' );

			// Return early if we have nowhere to put the item.
			if ( ! list ) {
				return;
			}

			// Normalize values.
			id   = parseInt( id, 10 );
			name = name.trim();

			// Construct each of the child nodes.
			input.type = 'checkbox';
			input.name = 'sticky-tax-term-id[]';
			input.id = 'list-item-' + id;
			input.value = id;

			label.htmlFor = 'list-item-' + id;
			label.classList.add( 'list-item-label' );
			label.appendChild( input );
			label.appendChild( document.createTextNode( name ) );

			li.id = 'item-' + id;
			li.dataset.termId = id;
			li.dataset.termName = name;
			li.classList.add( 'term-sticky-list-item' );
			li.appendChild( label );

			list.appendChild( li );
		},

		/**
		 * Remove an item from the list of available sticky terms.
		 *
		 * @param {int}    id  - The taxonomy term ID.
		 * @param {string} tax - The taxonomy the term belongs to.
		 */
		removeItem = function ( id, tax ) {
			var list = getTaxonomyTermList( tax );

			if ( ! list ) {
				return;
			}

			list.querySelector( '[data-term-id="' + id + '"]' ).remove();
		},

		/**
		 * Handle the toggling of sticky terms for hierarchical taxonomies.
		 *
		 * @param {Event} e - The event that triggered the callback.
		 */
		handleHierarchicalTaxonomies = function ( e ) {
			var term = e.target,
				tax;

			// Only proceed if the change event happened on an input.
			if ( 'INPUT' !== term.tagName || ! this.id ) {
				return;
			}

			// Get the taxonomy from the container's ID.
			tax = this.id.replace( /^taxonomy-/, '' ).trim();

			if ( term.checked ) {
				addItem( term.value, term.parentNode.innerText, tax );
			} else {
				removeItem( term.value, tax );
			}
		};

	// Add event listeners for hierarchical taxonomy meta boxes.
	document.querySelectorAll( '.categorydiv' ).forEach( function ( el ) {
		el.addEventListener( 'change', handleHierarchicalTaxonomies );
	} );

} ) ( window, document, undefined );

/**
 * Add our newly selected item to the box.
 *
 * @param  integer termID    The ID of the term being added.
 * @param  string  termName  The name of the term being added.
 * @param  string  termType  The taxonomy type of the term.
 *
 * @return HTML
 */
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

/**
 * Remove a selected item from the box.
 *
 * @param  string termName  The name of the term being removed.
 * @param  string termType  The taxonomy type of the term.
 *
 * @return HTML
 */
function stickyTaxRemoveItem( termName, termType ) {

	// Trim my term name to be safe.
	termName = jQuery.trim( termName );

	// Find the item in the term group with our term name.
	jQuery( 'div#term-sticky-' + termType + '-group li[data-term-name="' + termName + '"]' ).filter( function( index ) {

		// Set this element as a variable.
		var $self = jQuery( this );

		// Uncheck the box to be safe.
		$self.find( 'input' ).prop( 'checked', false );

		// Find our list item and remove it.
		$self.remove();
	});

	// Now determine if we have any remaining.
	var itemCount = jQuery( 'ul#list-' + termType ).children().length;

	// And add the note saying there are none if we hit zero.
	if ( itemCount < 1 ) {
		jQuery( 'div#term-sticky-' + termType + '-group' ).find( 'p.term-sticky-list-empty' ).removeClass( 'term-sticky-list-hide' ).addClass( 'term-sticky-list-show' );
	}

}

/**
 * Start the engines.
 */
jQuery( document ).ready( function($) {

	/**
	 * Quick helper to check for an existance of an element.
	 */
	$.fn.divExists = function( callback ) {

		// Slice some args.
		var args = [].slice.call( arguments, 1 );

		// Check for length.
		if ( this.length ) {
			callback.call( this, args );
		}
		// Return it.
		return this;
	};

	/**
	 * Set some variables to use later.
	 */
	var termID;
	var termName;
	var termType;
	var termList;
	var boxNonce;
	var tagName;
	var tagButton;

	/**
	 * Check for category clicking.
	 * Either adds or removes the checkbox.
	 */
	$( 'div#side-sortables' ).divExists( function() {

		// Look for the changing of an input.
		/*$( 'ul.categorychecklist' ).on( 'change', 'input', function( event ) {

			// Set my term ID.
			termID  = $( this ).attr( 'value' );

			// Get my term name.
			termName = $.trim( $( this ).parent( 'label' ).text() );

			// Determine my list item.
			termList = $( this ).parents( 'ul' ).attr( 'id' ).replace( 'checklist', '' );

			// Then determine the type ( have to run the replace twice because of both tabs).
			termType = termList.replace( 'checklist', '' );
			termType = termType.replace( '-pop', '' );

			// Check if we've been added or not.
			if ( $( this ).is( ':checked' ) ) {
				stickyTaxAddItem( termID, termName, termType );
			} else {
				stickyTaxRemoveItem( termName, termType );
			}

		});*/
	});

	/**
	 * Check for post tag clicking.
	 * Either adds or removes the checkbox.
	 */
	$( 'div#tagsdiv-post_tag' ).divExists( function() {

		// Watch for any clicking inside that div.
		$( '#tagsdiv-post_tag' ).on( 'click', function( event ) {

			// Check for the add new button.
			if ( $( event.target ).is( 'input.tagadd' ) ) {

				// Get my term name.
				termName = $( '#post_tag' ).find( '.tagchecklist button:last' ).parent( 'span' ).first().contents().filter( function() {
					return this.nodeType == 3;
				}).text();

				// Trim our term name to be safe.
				termName = $.trim( termName );

				// And fetch my nonce.
				boxNonce = $( 'input#sticky-tax-nonce' ).val();

				// Set my data array.
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
			}//end if

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

		// Check for clicking in the "most used" box.
		$( '#tagsdiv-post_tag' ).on( 'mousedown', function( event ) {

			// Watch the target to see if it's one of our tag cloud items.
			if ( $( event.target ).is( '#tagcloud-post_tag .tag-cloud-link' ) ) {

				// Get my term name.
				termName = $( event.target ).contents().filter( function() {
					return this.nodeType == 3;
				}).text();

				// Trim our term name to be safe.
				termName = $.trim( termName );

				// And fetch my nonce.
				boxNonce = $( 'input#sticky-tax-nonce' ).val();

				// Set my data array.
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
			}//end if
		});

	});

	// We are done here. Go home.
});
