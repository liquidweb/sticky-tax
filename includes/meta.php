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
 * @return bool True if the post meta was updated, false otherwise.
 */
function sticky_post_for_term( $post_id, $term_id ) {
	if ( $term_id instanceof WP_Term ) {
		$term_id = $term_id->term_id;
	}

	if ( ! term_exists( $term_id ) ) {
		return false;
	}

	return (bool) add_post_meta( $post_id, '_sticky_tax', (int) $term_id );
}

/**
 * Register the Sticky meta box on applicable post types.
 */
function register_meta_boxes() {
	$taxonomies = array_keys( get_taxonomies( [
		'public' => true,
	] ) );

	/**
	 * Retrieve an array of taxonomies that may possess sticky posts.
	 *
	 * @param array $taxonomies Taxonomies for which posts should be able to stick.
	 */
	$taxonomies = apply_filters( 'stickytax_taxonomies', $taxonomies );

	// If there are no taxonomies eligible for Sticky Tax, return early.
	if ( empty( $taxonomies ) ) {
		return;
	}

	/**
	 * Retrieve an array of post types that should be eligible for taxonomy-based stickiness.
	 *
	 * @param array $post_types Post types that should be able to use Sticky Tax.
	 */
	$post_types = apply_filters( 'stickytax_post_types', array( 'post' ) );

	add_meta_box(
		'sticky-tax',
		_x( 'Sticky', 'meta box title', 'sticky-tax' ),
		__NAMESPACE__ . '\render_meta_box',
		$post_types,
		'side',
		'default',
		$taxonomies
	);
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\register_meta_boxes', 0 );

/**
 * Render the Sticky meta box on a post edit screen.
 *
 * @param WP_Post $post The current post object.
 */
function render_meta_box( $post ) {

?>

	<p><?php esc_html_e( '"Stick" this post to the top of a term archive.', 'sticky-tax' ); ?></p>

<?php
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
		|| ! isset( $_POST['sticky-tax-nonce'], $_POST['sticky-tax-term-id'] )
		|| ! wp_verify_nonce( $_POST['sticky-tax-nonce'], 'sticky-tax' )
	) {
		return;
	}

	sticky_post_for_term( $post_id, (int) $_POST['sticky-tax-term-id'] );
}
