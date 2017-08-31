<?php
/**
 * Plugin Name: Sticky Tax: Test Taxonomies
 * Plugin URI:  https://github.com/liquidweb/sticky-tax
 * Description: Register custom taxonomies for the default "post" post type, for the sake of testing Sticky Tax.
 * Author:      Liquid Web
 * Author URI:  https://www.liquidweb.com
 *
 * @package LiquidWeb\StickyTax
 * @author  Liquid Web
 */

namespace LiquidWeb\StickyTax\TestFiles\CustomTaxonomies;

/**
 * Register custom taxonomies for testing purposes.
 */
function register_taxonomies() {
	register_taxonomy( 'sticky_tax_test_hierarchical', 'post', array(
		'label'        => 'Sticky Tax: Test Hierarchical',
		'hierarchical' => true,
	) );

	register_taxonomy( 'sticky_tax_test_non_hierarchical', 'post', array(
		'label'        => 'Sticky Tax: Test Non-Hierarchical',
		'hierarchical' => false,
	) );
}
add_action( 'init', __NAMESPACE__ . '\register_taxonomies' );
