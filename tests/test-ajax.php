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
		$response = $this->make_ajax_request();

		$this->assertTrue( $response->success );
		$this->assertObjectHasAttribute( 'data', $response );
	}

	public function test_ajax_error_bad_nonce() {
		$response = $this->make_ajax_request( [
			'nonce' => uniqid(),
		] );

		$this->assertFalse( $response->success );
		$this->assertEquals( $response->data->errcode, 'BAD_NONCE' );
	}

	public function test_ajax_error_no_term_name() {
		$response = $this->make_ajax_request( [
			'term_name' => '',
		] );

		$this->assertFalse( $response->success );
		$this->assertEquals( $response->data->errcode, 'NO_TERM_NAME' );
	}

	public function test_ajax_error_no_term_type() {
		$response = $this->make_ajax_request( [
			'term_type' => '',
		] );

		$this->assertFalse( $response->success );
		$this->assertEquals( $response->data->errcode, 'NO_TERM_TYPE' );
	}

	public function test_ajax_error_no_term_found() {
		$response = $this->make_ajax_request( [
			'term_name' => uniqid(),
		] );

		$this->assertFalse( $response->success );
		$this->assertEquals( $response->data->errcode, 'NO_TERM_FOUND' );
	}

	/**
	 * Send a request to the stickytax_get_id_from_name ajax handler.
	 *
	 * @param array $args Optional. Arguments to specify in the $_POST superglobal. Default is empty.
	 * @return object The JSON-decoded ajax response.
	 */
	protected function make_ajax_request( $args = array() ) {
		$_POST = array_merge( [
			'nonce'     => wp_create_nonce( 'sticky-tax' ),
			'action'    => 'stickytax_get_id_from_name',
			'term_name' => 'Uncategorized',
			'term_type' => 'category',
		], $args );

		/*
		 * The WP Core testing suite will throw WPDieExceptions whenever Ajax responses are
		 * returned, but we don't want these to prevent us from returning the last response.
		 */
		try {
			$this->_handleAjax( 'stickytax_get_id_from_name' );

		} catch ( WPDieException $e ) { // @codingStandardsIgnoreLine.
			// Do nothing, proceed.
		}

		return json_decode( $this->_last_response );
	}
}
