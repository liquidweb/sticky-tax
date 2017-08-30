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
		 * Given a term name and its taxonomy, return the term ID.
		 *
		 * If the value isn't available in the cache, a new entry will be added with the name as
		 * both the key and value.
		 *
		 * @param {string} name - The taxonomy term name.
		 * @param {string} tax  - The taxonomy the term belongs to.
		 * @return {int} The term ID, or 0 if no matching term was found.
		 */
		getTermIdByName = function ( name, tax ) {
			if ( ! stickyTax.terms[ tax ][ name ] ) {
				stickyTax.terms[ tax ][ name ] = name;
			}

			return stickyTax.terms[ tax ][ name ];
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
			return str.replace( /[^0-9A-Z-_]/i, '' );
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
				inputId, newTag, sanitizedClassName;

			// Normalize values.
			id      = parseInt( id, 10 );
			name    = name.trim();
			newTag  = isNaN( id );

			if ( newTag ) {
				sanitizedClassName = sanitizeClassName( name );
			}
			inputId = 'list-item-' + ( newTag ? sanitizedClassName : id );

			// Return early if we have nowhere to put the item or it already exists.
			if ( ! list || null !== document.getElementById( inputId ) ) {
				return;
			}

			// Construct each of the child nodes.
			input.type = 'checkbox';
			input.name = 'sticky-tax-term-id[]';
			input.id = inputId;
			input.value = newTag ? tax + ':' + name : id;

			label.htmlFor = inputId;
			label.classList.add( 'list-item-label' );
			label.appendChild( input );
			label.appendChild( document.createTextNode( name ) );

			li.id = 'item-' + ( newTag ? sanitizedClassName : id );
			li.dataset.termId = newTag ? sanitizedClassName : id;
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

					addItem( getTermIdByName( name, tax ), name, tax );
				} );

			} else if ( e.target.classList.contains( 'ntdelbutton' ) ) {
				removeItem( getTermIdByName( e.target.parentNode.lastChild.textContent, tax ), tax );
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
				addItem( getTermIdByName( e.target.innerText, tax ), e.target.innerText, tax );
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
