<?php
/**
 * Tests for the main plugin functionality.
 *
 * @package Sticky_Tax
 */

use LiquidWeb\StickyTax as Core;

/**
 * Test suite for the main plugin file.
 */
class CoreTest extends WP_UnitTestCase {

	public function test_defines_url_constant() {
		$this->assertTrue( defined( 'STICKY_TAX_URL' ) );
	}

	public function test_defines_vers_constant() {
		$this->assertTrue( defined( 'STICKY_TAX_VERS' ) );
	}

	public function test_loads_textdomain() {
		$called = false;

		/*
		 * Since we don't necessarily have .mo files to load, watch for the plugin_locale filter,
		 * which is called at the top of load_plugin_textdomain().
		 */
		add_filter( 'plugin_locale', function ( $locale, $domain ) use ( &$called ) {
			if ( 'sticky-tax' === $domain ) {
				$called = true;
			}

			return $locale;
		}, 10, 2 );

		Core\load_textdomain();

		$this->assertTrue( $called, 'The plugin_locale filter was not called.' );
	}
}
