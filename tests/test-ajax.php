<?php
/**
 * Tests for the back-end handling of sticky meta data.
 *
 * @package Sticky_Tax
 */

use LiquidWeb\StickyTax\Ajax as Ajax;

/**
 * Test suite handling the post meta related to sticky taxonomy posts.
 */
class AjaxTest extends WP_Ajax_UnitTestCase {

	public function test_ajax_success() {
		$_POST['nonce']     = wp_create_nonce( 'sticky-tax' );
		$_POST['action']    = 'stickytax_get_id_from_name';
		$_POST['term_name'] = 'Uncategorized';
		$_POST['term_type'] = 'category';

		try {
			$this->_handleAjax( 'stickytax_get_id_from_name' );
		} catch ( WPAjaxDieContinueException $e ) {
			return;
		}

		// Check that the exception was thrown.
		$response = json_decode( $this->_last_response );

		$this->assertInternalType( 'object', $response );
		$this->assertObjectHasAttribute( 'success', $response );
		$this->assertTrue( $response->success );
		$this->assertObjectHasAttribute( 'data', $response );
	}

	public function test_ajax_error_bad_nonce() {
		$_POST['nonce']     = uniqid();
		$_POST['action']    = 'stickytax_get_id_from_name';
		$_POST['term_name'] = 'Uncategorized';
		$_POST['term_type'] = 'category';

		try {
			$this->_handleAjax( 'stickytax_get_id_from_name' );
		} catch ( WPAjaxDieContinueException $e ) {
			return;
		}

		// Check that the exception was thrown.
		$response = json_decode( $this->_last_response );

		$this->assertInternalType( 'object', $response );
		$this->assertObjectHasAttribute( 'success', $response );
		$this->assertFalse( $response->success );
		$this->assertObjectHasAttribute( 'data', $response );
		$this->assertObjectHasAttribute( 'errcode', $response->data );
		$this->assertEquals( $response->data->errcode, 'BAD_NONCE' );
	}

	public function test_ajax_error_no_term_name() {
		$_POST['nonce']     = wp_create_nonce( 'sticky-tax' );
		$_POST['action']    = 'stickytax_get_id_from_name';
		$_POST['term_name'] = '';
		$_POST['term_type'] = 'category';

		try {
			$this->_handleAjax( 'stickytax_get_id_from_name' );
		} catch ( WPAjaxDieContinueException $e ) {
			return;
		}

		// Check that the exception was thrown.
		$response = json_decode( $this->_last_response );

		$this->assertInternalType( 'object', $response );
		$this->assertObjectHasAttribute( 'success', $response );
		$this->assertFalse( $response->success );
		$this->assertObjectHasAttribute( 'data', $response );
		$this->assertObjectHasAttribute( 'errcode', $response->data );
		$this->assertEquals( $response->data->errcode, 'NO_TERM_NAME' );
	}

	public function test_ajax_error_no_term_type() {
		$_POST['nonce']     = wp_create_nonce( 'sticky-tax' );
		$_POST['action']    = 'stickytax_get_id_from_name';
		$_POST['term_name'] = 'Uncategorized';
		$_POST['term_type'] = '';

		try {
			$this->_handleAjax( 'stickytax_get_id_from_name' );
		} catch ( WPAjaxDieContinueException $e ) {
			return;
		}

		// Check that the exception was thrown.
		$response = json_decode( $this->_last_response );

		$this->assertInternalType( 'object', $response );
		$this->assertObjectHasAttribute( 'success', $response );
		$this->assertFalse( $response->success );
		$this->assertObjectHasAttribute( 'data', $response );
		$this->assertObjectHasAttribute( 'errcode', $response->data );
		$this->assertEquals( $response->data->errcode, 'NO_TERM_TYPE' );
	}

	public function test_ajax_error_no_term_found() {
		$_POST['nonce']     = wp_create_nonce( 'sticky-tax' );
		$_POST['action']    = 'stickytax_get_id_from_name';
		$_POST['term_name'] = uniqid();
		$_POST['term_type'] = 'category';

		try {
			$this->_handleAjax( 'stickytax_get_id_from_name' );
		} catch ( WPAjaxDieContinueException $e ) {
			return;
		}

		// Check that the exception was thrown.
		$response = json_decode( $this->_last_response );

		$this->assertInternalType( 'object', $response );
		$this->assertObjectHasAttribute( 'success', $response );
		$this->assertFalse( $response->success );
		$this->assertObjectHasAttribute( 'data', $response );
		$this->assertObjectHasAttribute( 'errcode', $response->data );
		$this->assertEquals( $response->data->errcode, 'NO_TERM_FOUND' );
	}

}
