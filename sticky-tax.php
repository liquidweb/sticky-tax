<?php
/**
 * Plugin Name: Sticky Tax
 * Plugin URI:  https://github.com/liquidweb/sticky-tax
 * Description: Make posts sticky within the context of a single category, tag, or custom taxonomy term.
 * Author:      Liquid Web
 * Author URI:  https://www.liquidweb.com
 * Text Domain: sticky-tax
 * Domain Path: /languages
 * Version:     1.0.0
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package LiquidWeb\StickyTax
 * @author  Liquid Web
 */

namespace LiquidWeb\StickyTax;

require __DIR__ . '/includes/display.php';
require __DIR__ . '/includes/meta.php';

define( 'STICKY_TAX_URL', plugins_url( '/', __FILE__ ) );

define( 'STICKY_TAX_VERS', '1.0.0' );

/**
 * Load the plugin text domain.
 *
 * This isn't explicitly required for WordPress 4.6+, but it's still a good practice.
 *
 * @link https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain
 */
function load_textdomain() {
	load_plugin_textdomain( 'sticky-tax', false, basename( __DIR__ ) . '/languages/' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\load_textdomain' );
