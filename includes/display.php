<?php
/**
 * Handle query manipulations to display posts in taxonomy term archives.
 *
 * @package LiquidWeb\StickyTax
 * @author  Liquid Web
 */

namespace LiquidWeb\StickyTax\Display;

use WP_Query;

/**
 * Retrieve a list of all post IDs that are sticky for the given term ID.
 *
 * @param int $term_id The ID for which terms are stickied.
 * @return array An array of post IDs.
 */
function get_sticky_posts_for_term( $term_id ) {
	$cache_key = 'term_' . (int) $term_id;
	$found     = false;
	$post_ids  = wp_cache_get( $cache_key, 'sticky-tax', false, $found );

	if ( false === $found ) {
		$query = new WP_Query( [
			'update_term_meta_cache' => false,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'meta_query' => [
				[
					'key'   => '_sticky_tax',
					'value' => $term_id,
				],
			],
		] );
		$post_ids = $query->posts;

		wp_cache_set( $cache_key, $post_ids, 'sticky-tax' );
	}

	/**
	 * Modify the post IDs that are sticky for the given term ID.
	 *
	 * @param array $post_ids Array of sticky post IDs.
	 * @param int   $term_id  The current term ID.
	 */
	$post_ids = apply_filters( 'stickytax_get_sticky_posts_for_term', $post_ids, $term_id );

	return $post_ids;
}

/**
 * Inject a custom ORDER BY clause into MySQL queries, moving the sticky posts to the front of the
 * list when building taxonomy archive pages.
 *
 * @global $wpdb
 *
 * @param string   $orderby The MySQL "ORDER BY" statement.
 * @param WP_Query $query   The WP_Query object being modified. This query will be checked to
 *                          ensure the logic is only applied where appropriate.
 */
function inject_orderby_clause( $orderby, $query ) {
	global $wpdb;

	if ( ! $query->is_category() && ! $query->is_tag() && ! $query->is_tax ) {
		return $orderby;
	}

	// Locate sticky posts for the given taxonomy term ID.
	$sticky = get_sticky_posts_for_term( get_queried_object_id() );

	if ( empty( $sticky ) ) {
		return $orderby;
	}

	// Prepend an explicit list of post IDs that should be at the beginning of the results.
	$sticky  = array_map( 'absint', $sticky );

	return sprintf(
		"field({$wpdb->posts}.ID, %s) DESC, " . $orderby,
		implode( ', ', $sticky )
	);
}
add_filter( 'posts_orderby', __NAMESPACE__ . '\inject_orderby_clause', 10, 2 );

/**
 * When the post is acting as sticky, add .sticky-tax to it for easy styling.
 *
 * @param array $classes An array of post classes.
 * @param array $class   An array of additional classes added to the post.
 * @param int   $post_id The post ID.
 * @return array The filtered array of post classes.
 */
function append_sticky_class( $classes, $class, $post_id ) {
	if ( ! is_category() && ! is_tag() && ! is_tax() ) {
		return $classes;
	}

	if ( in_array( $post_id, get_sticky_posts_for_term( get_queried_object_id() ), true ) ) {
		$classes[] = 'sticky';
		$classes[] = 'sticky-tax';
	}

	return $classes;
}
add_filter( 'post_class', __NAMESPACE__ . '\append_sticky_class', 10, 3 );
