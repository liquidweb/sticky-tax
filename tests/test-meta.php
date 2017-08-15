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
class MetaTest extends WP_UnitTestCase {

	public function test_can_make_post_sticky_for_term() {
		$post_id = $this->factory()->post->create();
		$cat_obj = self::factory()->category->create_and_get();

		Meta\sticky_post_for_term( $post_id, $cat_obj );

		$this->assertEquals( $cat_obj->term_id, get_post_meta( $post_id, '_sticky_tax', true ) );
	}

	public function test_can_make_post_sticky_for_term_id() {
		$post_id = $this->factory()->post->create();
		$cat_id  = self::factory()->category->create();

		Meta\sticky_post_for_term( $post_id, $cat_id );

		$this->assertEquals( $cat_id, get_post_meta( $post_id, '_sticky_tax', true ) );
	}

	public function test_sticky_post_for_term_handles_deleted_terms() {
		$post_id = $this->factory()->post->create();
		$cat_id  = self::factory()->category->create();

		// Delete the term we just created.
		wp_delete_term( $cat_id, 'category' );

		$this->assertFalse( Meta\sticky_post_for_term( $post_id, $cat_id ) );
		$this->assertNotContains( $cat_id, get_post_meta( $post_id, '_sticky_tax' ) );
	}

	public function test_handles_multiple_calls_for_same_post_and_term() {
		$this->markTestSkipped( 'Logic not yet implemented' );

		$post_id = $this->factory()->post->create();
		$cat_id  = self::factory()->category->create();

		$this->assertTrue( Meta\sticky_post_for_term( $post_id, $cat_id ) );
		$this->assertFalse( Meta\sticky_post_for_term( $post_id, $cat_id ) );
		$this->assertFalse( Meta\sticky_post_for_term( $post_id, $cat_id ) );

		$this->assertCount( 1, get_post_meta( $post_id, '_sticky_tax' ) );
	}
}
