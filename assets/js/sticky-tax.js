/**
 * Scripting for the various message actions.
 *
 * @package LiquidWeb\StickyTax
 * @author  Liquid Web
 */
/* global stickyTax */

( function ( window, document, undefined ) {
	'use strict';

	var termLists = {},

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
		 * Given a string, prepare a version that can be used in HTML class names.
		 *
		 * The goal isn't to create a "slug", necessarily, but rather a value that can be used when
		 * creating IDs for terms that don't have numeric IDs (yet).
		 *
		 * @param {string} str - The string to work on.
		 * @return {string} A version of the string that's safe to use in ID attributes.
		 */
		sanitizeClassName = function ( str ) {
			return str.replace( /[^0-9A-Z-_]/gi, '' );
		},

		/**
		 * Given a term and its taxonomy, return the ID that should be used.
		 *
		 * @param {string|int} id  - Either the term ID the term name.
		 * @param {string}     tax - The taxonomy name. Only required if the provided ID is a string.
		 * @return {string|int} Either an integer term ID or a concatenation of {tax}:{id}.
		 */
		getTermValue = function ( id, tax ) {
			var int = parseInt( id, 10 );

			return isNaN( int ) || ! int ? tax.trim() + ':' + id.trim() : int;
		},

		/**
		 * Build a new list item for the Sticky Tax meta box.
		 *
		 * @param {int}    id   - The taxonomy term ID.
		 * @param {string} name - The name (label) for the term.
		 * @param {string} tax  - The term's taxonomy.
		 * @return {Element} The newly-constructed HTML Element.
		 */
		addItem = function ( id, name, tax ) {
			var list = getTaxonomyTermList( tax ),
				li = document.createElement( 'li' ),
				label = document.createElement( 'label' ),
				input = document.createElement( 'input' ),
				newTag = isNaN( parseInt( id, 10 ) ),
				termVal, termId, inputId;

			// Non-numeric ID, but we already have it in our list.
			if ( newTag && list.querySelector( '[data-term-name="' + name.trim() + '"]' ) ) {
				return;
			}

			// Assemble some other helpful variables.
			termVal = getTermValue( newTag ? name : id, tax ),
			termId = sanitizeClassName( termVal ),
			inputId = 'list-item-' + termId;

			// Return early if we have nowhere to put the item or it already exists.
			if ( ! list || null !== document.getElementById( inputId ) ) {
				return;
			}

			name = name.trim();

			// Construct each of the child nodes.
			input.type = 'checkbox';
			input.name = 'sticky-tax-term-id[]';
			input.id = inputId;
			input.value = termVal;

			label.htmlFor = inputId;
			label.classList.add( 'list-item-label' );
			label.appendChild( input );
			label.appendChild( document.createTextNode( name ) );

			li.id = 'item-' + termId;
			li.dataset.termId = termId;
			li.dataset.termName = name;
			li.classList.add( 'term-sticky-list-item' );
			li.appendChild( label );

			list.appendChild( li );
		},

		/**
		 * Remove an item from the list of available sticky terms.
		 *
		 * @param {int|string} id  - The taxonomy term ID. For terms without IDs, this can also be
		 *                           the string, prefixed with the taxonomy (e.g. "post_tag:My Tag").
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
		},

		/**
		 * Handle non-hierarchical taxonomy meta boxes.
		 *
		 * @param {Event} e -The event that triggered this callback.
		 */
		handleNonHierarchicalTaxonomies = function ( e ) {
			var tax = this.id;

			if ( e.target.classList.contains( 'tagadd' ) && tax ) {
				this.querySelectorAll( '.tagchecklist > span' ).forEach( function ( term ) {
					var name;

					if ( ! term.lastChild ) {
						return;
					}

					name = term.lastChild.textContent;

					addItem( null, name, tax );
				} );

			} else if ( e.target.classList.contains( 'ntdelbutton' ) ) {
				removeItem( e.target.parentNode.lastChild.textContent, tax, tax );
			}
		},

		/**
		 * Handle tag clouds, which can be used by non-hierarchical taxonomies.
		 *
		 * @param {Event} e - The term taxonomy.
		 */
		handleTagClouds = function( e ) {
			var tax = this.querySelector( '.tagsdiv' ).id;

			if ( e.target.classList.contains( 'tag-cloud-link' ) ) {
				addItem( null, e.target.innerText, tax );
			}
		};

	// Add event listeners for hierarchical taxonomy meta boxes (e.g. categories).
	document.querySelectorAll( '.categorydiv' ).forEach( function ( el ) {
		el.addEventListener( 'change', handleHierarchicalTaxonomies );
	} );

	// Event listeners for non-hierarchical taxonomies (e.g. post_tags).
	document.querySelectorAll( '.tagsdiv' ).forEach( function ( el ) {
		el.addEventListener( 'click', handleNonHierarchicalTaxonomies );

		// The tagcloud div lives just outside the .tagsdiv, so we'll attach it to the parent.
		el.parentElement.addEventListener( 'mousedown', handleTagClouds );
	} );

} ( window, document ) );
