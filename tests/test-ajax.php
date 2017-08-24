<?php
/**
 * Tests for the back-end handling of sticky meta data.
 *
 * @package Sticky_Tax
 */

use LiquidWeb\StickyTax\Meta as Meta;

/**
 * Test suite handling the post meta related to sticky taxonomy posts.
 */
class AjaxTest extends WP_Ajax_UnitTestCase {

	public function test_ajax_return() {

		$cat_obj = $this->factory()->category->create_and_get();

		global $_POST;

		$_POST[ 'nonce' ] = wp_create_nonce( 'sticky-tax' );
		$_POST[ 'action' ] = 'stickytax_get_id_from_name';
		$_POST[ 'term_name' ] = $cat_obj->name;
		$_POST[ 'term_type' ] = 'category';

		try {
			$this->_handleAjax( 'stickytax_get_id_from_name' );
		} catch ( WPAjaxDieStopException $e ) {}

        // Check that the exception was thrown.
        $this->assertTrue( isset( $e ) );

        // The output should be a 1 for success.
        $this->assertEquals( '1', $e->getMessage() );

        $this->assertEquals( 'yes', get_option( 'some_option' ) );
    }

}
