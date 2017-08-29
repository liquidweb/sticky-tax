<?php
/**
 * Handle the marking of posts as sticky for a given taxonomy term.
 *
 * @package LiquidWeb\StickyTax
 * @author  Liquid Web
 */

namespace LiquidWeb\StickyTax\Meta;

use WP_Term;

/**
 * Make a post object sticky within a given term ID.
 *
 * @param int         $post_id The ID of the post object being sticked.
 * @param int|WP_Term $term_id Either the term object or the term ID.
 * @return bool True if the post meta was updated, false otherwise. If this post was already sticky
 *              for the given term, the return value will be false.
 */
function sticky_post_for_term( $post_id, $term_id ) {
	if ( $term_id instanceof WP_Term ) {
		$term_id = $term_id->term_id;
	}

	if ( ! term_exists( $term_id ) ) {
		return false;
	}

	// Don't add the post meta if this post is already sticky for this term.
	$sticky = array_map( 'intval', get_post_meta( $post_id, '_sticky_tax', false ) );

	if ( in_array( $term_id, $sticky, true ) ) {
		return false;
	}

	return (bool) add_post_meta( $post_id, '_sticky_tax', (int) $term_id );
}

/**
 * Fetch the taxonomies tied to a particular object.
 *
 * @param  object  $object  The object we're looking against.
 * @param  string  $output  The format to output.
 * @param  boolean $public  Whether to remove the non-public ones or not.
 *
 * @return array
 */
function get_taxonomies_for_object( $object, $output = 'objects', $public = true ) {

	// Set the arguments for getting all the available taxonomies.
	$taxonomies = get_object_taxonomies( $object, 'objects' );

	// Look to see if we have non-public ones, if requested.
	if ( ! empty( $taxonomies ) && ! empty( $public ) ) {

		// Loop the taxonomies and unset the ones that are not public.
		foreach ( $taxonomies as $type => $single ) {

			// If it's empty, unset.
			if ( empty( $single->public ) ) {
				unset( $taxonomies[ $type ] );
			}
		}
	}

	/**
	 * Retrieve an array of taxonomies that may possess sticky posts.
	 *
	 * @param array $taxonomies Taxonomies for which posts should be able to stick.
	 */
	$taxonomies = apply_filters( 'stickytax_taxonomies', $taxonomies );

	// Return the array or false.
	return ! empty( $taxonomies ) ? $taxonomies : false;
}

/**
 * Register the Sticky meta box on applicable post types.
 *
 * @param string  $post_type The post type of the current post object.
 * @param WP_Post $post      The current post object.
 */
function register_meta_boxes( $post_type, $post ) {

	/**
	 * Retrieve an array of post types that should be eligible for taxonomy-based stickiness.
	 *
	 * @param array $post_types Post types that should be able to use Sticky Tax.
	 */
	$post_types = apply_filters( 'stickytax_post_types', array( 'post' ) );

	// Bail if we have no post types.
	if ( empty( $post_types ) ) {
		return;
	}

	// Attempt to get our taxonomies and bail without them.
	$taxonomies = get_taxonomies_for_object( $post, 'objects' );

	if ( empty( $taxonomies ) ) {
		return;
	}

	// Thin down the list to the taxonomy and the label.
	$tax_data   = wp_list_pluck( $taxonomies, 'label', 'name' );

	// Now call the actual meta box.
	add_meta_box(
		'sticky-tax',
		_x( 'Sticky', 'meta box title', 'sticky-tax' ),
		__NAMESPACE__ . '\render_meta_box',
		$post_types,
		'side',
		'default',
		$tax_data
	);
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\register_meta_boxes', 10, 2 );

/**
 * Get an array of all terms from non-hierarchical taxonomies that the given $post supports.
 *
 * @param WP_Post $post_type The current post type.
 * @return array A nested array, keyed by the taxonomy.
 */
function get_non_hierarchical_term_mapping( $post_type ) {
	$taxonomies = get_taxonomies_for_object( $post_type );
	$terms      = array();

	if ( ! $taxonomies ) {
		return $terms;
	}

	foreach ( $taxonomies as $tax => $object ) {

		// Filter out hierarchical taxonomies.
		if ( $object->hierarchical ) {
			continue;
		}

		$terms[ $tax ] = array_flip( get_terms( array(
			'taxonomy'   => $tax,
			'hide_empty' => false,
			'fields'     => 'id=>name',
		) ) );
	}

	return $terms;
}

/**
 * Render the Sticky meta box on a post edit screen.
 *
 * @param WP_Post $post     The current post object.
 * @param array   $meta_box The settings for the meta box, including an 'args' key.
 */
function render_meta_box( $post, $meta_box ) {

	// Bail if no args are passed. Shouldn't get this far, but ¯\_(ツ)_/¯.
	if ( empty( $meta_box['args'] ) ) {
		return;
	}

	// Fetch any selected terms.
	$selected = array_map( 'intval', get_post_meta( $post->ID, '_sticky_tax' ) );

	// Set an empty options array.
	$options  = array();

	// Loop the taxonomy data provided.
	foreach ( $meta_box['args'] as $tax_name => $tax_label ) {

		// Fetch my post terms.
		$terms = wp_get_post_terms( $post->ID, $tax_name, array() );

		// Set our items based on whether we have selected items or not.
		$items  = empty( $terms ) || is_wp_error( $terms ) ? '' : wp_list_pluck( $terms, 'name', 'term_id' );

		// Set my options array accordingly.
		$options[ $tax_name ] = [
			'label' => $tax_label,
			'items' => $items,
		];
	}

	// Bail without options data to show.
	if ( empty( $options ) ) {
		return;
	}

	// Wrap the whole thing in a div.
	echo '<div class="term-sticky-list-wrap">';

	// Loop the grouped term items.
	foreach ( $options as $type => $data ) {

		// Set a class for the message.
		$class  = ! empty( $data['items'] ) ? 'term-sticky-list-hide' : 'term-sticky-list-show';

		// Wrap each group in a div.
		echo '<div id="term-sticky-' . esc_attr( $type ) . '-group" class="term-sticky-list-group" data-term-type="' . esc_attr( $type ) . '" >';

			// Handle my label.
			echo '<h4>' . esc_html( $data['label'] ) . '</h4>';

			// Open my unordered list wrapper.
			echo '<ul id="list-' . esc_attr( $type ) . '" class="term-sticky-list term-sticky-list-' . esc_attr( $type ) . '">';

		// Now check we have items to show.
		if ( ! empty( $data['items'] ) ) {

			// Now loop the items into a key/value pair.
			foreach ( $data['items'] as $term_id => $term_name ) {

				// Wrap each checkbox in a list item.
				echo '<li id="item-' . absint( $term_id ) . '" data-term-id="' . absint( $term_id ) . '" data-term-name="' . esc_attr( $term_name ) . '" class="term-sticky-list-item">';

					// Opening with the label.
					echo '<label class="list-item-label" for="list-item-' . absint( $term_id ) . '">';

						// The checkbox itself.
						echo '<input type="checkbox" name="sticky-tax-term-id[]" class="list-item-input" id="list-item-' . absint( $term_id ) . '" value="' . absint( $term_id ) . '" ' . checked( in_array( $term_id, $selected, true ), true, false ) . ' />';

						// Echo out the text.
						echo esc_html( $term_name );

					// Close the label.
					echo '</label>';

				// And close the list item.
				echo '</li>';
			}//end foreach
		}//end if

			// Close my unordered list wrapper.
			echo '</ul>';

			// If we had no items, show the message.
			echo '<p class="term-sticky-list-empty ' . esc_attr( $class ) . '">' . esc_html__( 'No terms have been applied to this post.', 'sticky-tax' ) . '</p>';

		// Close my group div.
		echo '</div>';
	}//end foreach

	// Close my div.
	echo '</div>';

	// And my nonce.
	wp_nonce_field( 'sticky-tax', 'sticky-tax-nonce' );
}

/**
 * Save the settings within the Sticky meta box.
 *
 * @param int $post_id The post being saved.
 */
function save_post( $post_id ) {
	if (
		( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| ! isset( $_POST['sticky-tax-nonce'] )
		|| ! wp_verify_nonce( $_POST['sticky-tax-nonce'], 'sticky-tax' )
	) {
		return;
	}

	if ( ! isset( $_POST['sticky-tax-term-id'] ) ) {
		$_POST['sticky-tax-term-id'] = array();
	}

	$new_meta      = array_map( 'intval', (array) $_POST['sticky-tax-term-id'] );
	$existing_meta = array_map( 'intval', get_post_meta( $post_id, '_sticky_tax' ) );

	// Return early if there are no changes.
	if ( ! empty( $existing_meta ) && $existing_meta === $new_meta ) {
		return;
	}

	// Remove old entries.
	delete_post_meta( $post_id, '_sticky_tax' );

	// Save new sticky assignments.
	foreach ( $_POST['sticky-tax-term-id'] as $term_id ) {
		sticky_post_for_term( $post_id, (int) $term_id );

		wp_cache_delete( 'term_' . (int) $term_id, 'sticky-tax' );
	}

	// Clear caches for anything that's changed.
	foreach ( array_diff( $existing_meta, $new_meta ) as $term_id ) {
		wp_cache_delete( 'term_' . (int) $term_id, 'sticky-tax' );
	}
}
add_action( 'save_post', __NAMESPACE__ . '\save_post' );

/**
 * Register the scripts and styles used by the plugin.
 *
 * @param string $hook The current page being loaded.
 */
function register_scripts( $hook ) {
	$pages = array( 'post.php', 'post-new.php' );

	if ( empty( $hook ) || ! in_array( $hook, $pages, true ) ) {
		return;
	}

	// The filenames and version numbers are dependent on whether or not SCRIPT_DEBUG is true.
	$debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	$file  = $debug ? 'sticky-tax' : 'sticky-tax.min';
	$vers  = $debug ? null : STICKY_TAX_VERS;

	wp_enqueue_script(
		'sticky-tax-admin',
		STICKY_TAX_URL . 'assets/js/' . $file . '.js',
		array( 'jquery' ),
		$vers,
		true
	);

	// Include the non-hierarchical terms, mapped to their IDs.
	$terms = get_non_hierarchical_term_mapping( get_post_type() );
	wp_localize_script( 'sticky-tax-admin', 'stickyTax', array(
		'terms' => $terms,
	) );

	wp_enqueue_style(
		'sticky-tax-admin',
		STICKY_TAX_URL . 'assets/css/' . $file . '.css',
		null,
		$vers,
		'all'
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_scripts' );
