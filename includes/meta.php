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
 * @param WP_Post $post     The current post object.
 * @param array   $meta_box The settings for the meta box, including an 'args' key.
 */
function render_meta_box( $post, $meta_box ) {
	$selected = array_map( 'intval', get_post_meta( $post->ID, '_sticky_tax' ) );
	$options  = array();
	$size     = 0;

	foreach ( $meta_box['args'] as $taxonomy ) {
		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'fields'     => 'id=>name',
			'hide_empty' => false,
		] );

		if ( ! empty( $terms ) ) {
			$tax                    = get_taxonomy( $taxonomy );
			$options[ $tax->label ] = $terms;
			$size += count( $terms ) + 1;
		}
	}

	// Limit the <select> size.
	$size = 10 > $size ? $size : 10;
?>

	<label for="sticky-tax-term-id" class="screen-reader-text"><?php esc_html_e( 'Sticky terms', 'sticky-tax' ); ?></label>
	<p>
		<select id="sticky-tax-term-id" name="sticky-tax-term-id[]" class="regular-text" size="<?php echo esc_attr( $size ); ?>" multiple>
			<?php foreach ( $options as $group => $opts ) : ?>
				<optgroup label="<?php echo esc_attr( $group ); ?>">
					<?php foreach ( $opts as $id => $label ) : ?>

						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( in_array( $id, $selected, true ), true ); ?>><?php echo esc_html( $label ); ?></option>

					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	</p>

	<p class="description"><?php esc_html_e( '"Stick" this post to the top of the archive pages for the selected term(s)', 'sticky-tax' ); ?></p>

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
 * Register the Select2 scripts used by the meta box.
 *
 * As Select2 is a popular library, we'll first check to see if another plugin has registered it
 * and, if so, use that instance instead.
 *
 * @link https://select2.github.io/
 *
 * @param string $hook The current page being loaded.
 */
function register_scripts( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	// Current version of Select2.
	$version = '4.0.3';

	// Register the script if it hasn't already been.
	if ( ! wp_script_is( 'select2', 'registered' ) ) {
		wp_register_script(
			'select2',
			STICKY_TAX_URL . 'lib/select2/js/select2.min.js',
			array( 'jquery' ),
			$version,
			true
		);
	}

	// Register the stylesheet, if necessary.
	if ( ! wp_style_is( 'select2', 'registered' ) ) {
		wp_register_style(
			'select2',
			STICKY_TAX_URL . 'lib/select2/css/select2.min.css',
			null,
			$version
		);
	}

	// Add the inline script necessary to get Select2 working for the meta box.
	wp_add_inline_script( 'select2', "jQuery(document.getElementById('sticky-tax-term-id')).select2();" );

	// Add small style overrides.
	wp_add_inline_style( 'select2', '#sticky-tax .select2 li, #select2-sticky-tax-term-id-results li { margin-bottom: inherit; }' );

	wp_enqueue_script( 'select2' );
	wp_enqueue_style( 'select2' );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_scripts' );
