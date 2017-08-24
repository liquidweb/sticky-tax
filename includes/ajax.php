<?php
/**
 * Handle the Ajax portion.
 *
 * @package LiquidWeb\StickyTax
 * @author  Liquid Web
 */

namespace LiquidWeb\StickyTax\Ajax;

/**
 * Handle the Ajax call for getting the term ID from a tag.
 *
 * @return array
 */
function get_id_from_name() {

	// Only run this on the admin side.
	if ( ! is_admin() ) {
		wp_die( esc_attr__( 'This can only be run on admin.', 'sticky-tax' ) );
	}

	// Bail out if running an autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Bail out if running a cron, unless we've skipped that.
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return;
	}

	// Verify our nonce.
	if ( false === check_ajax_referer( 'sticky-tax', 'nonce', false ) ) {

		// Build our return.
		$return = array(
			'errcode' => 'BAD_NONCE',
			'message' => __( 'The security nonce did not validate.', 'sticky-tax' ),
		);

		// And handle my JSON return.
		wp_send_json_error( $return );
	}

	// Bail without passing a term name.
	if ( empty( $_POST['term_name'] ) ) {

		// Build our return.
		$return = array(
			'errcode' => 'NO_TERM_NAME',
			'message' => __( 'No term name was provided.', 'sticky-tax' ),
		);

		// And handle my JSON return.
		wp_send_json_error( $return );
	}

	// Bail without passing a term type.
	if ( empty( $_POST['term_type'] ) ) {

		// Build our return.
		$return = array(
			'errcode' => 'NO_TERM_TYPE',
			'message' => __( 'No term type was provided.', 'sticky-tax' ),
		);

		// And handle my JSON return.
		wp_send_json_error( $return );
	}

	// Set my variables.
	$name   = preg_replace( '/\PL/u', '', sanitize_text_field( $_POST['term_name'] ) );
	$type   = trim( sanitize_text_field( $_POST['term_type'] ) );

	// Get my term data.
	$term   = get_term_by( 'name', $name, $type );

	// Check to see if our term failed.
	if ( empty( $term ) || is_wp_error( $term ) ) {

		// Build our return.
		$return = array(
			'errcode' => 'NO_TERM_FOUND',
			'message' => __( 'The term could not be found.', 'sticky-tax' ),
		);

		// And handle my JSON return.
		wp_send_json_error( $return );
	}

	// Check that a term ID was included.
	if ( ! empty( $term->term_id ) ) {

		// Build our return.
		$return = array(
			'errcode' => null,
			'term_id' => $term->term_id,
		);

		// And handle my JSON return.
		wp_send_json_success( $return );
	}

	// Build our return.
	$return = array(
		'errcode' => 'UNKNOWN',
		'message' => __( 'An unknown error has occured.', 'sticky-tax' ),
	);

	// And handle my JSON return.
	wp_send_json_error( $return );
}
add_action( 'wp_ajax_stickytax_get_id_from_name', __NAMESPACE__ . '\get_id_from_name' );
