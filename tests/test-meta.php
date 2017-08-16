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

	/**
	 * @after
	 */
	public function reset_meta_boxes() {
		global $wp_meta_boxes;

		$wp_meta_boxes['post']['side']['default'] = [];

		wp_dequeue_script( 'select2' );
		wp_dequeue_style( 'select2' );
		wp_deregister_script( 'select2' );
		wp_deregister_style( 'select2' );
	}

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

	public function test_register_meta_boxes_applies_taxonomy_filter() {
		global $wp_meta_boxes;

		$tax = uniqid();

		add_filter( 'stickytax_taxonomies', function ( $taxonomies ) use ( $tax ) {
			$taxonomies[] = $tax;

			return $taxonomies;
		} );

		Meta\register_meta_boxes();

		$this->assertContains(
			$tax,
			$wp_meta_boxes['post']['side']['default']['sticky-tax']['args'],
			'The stickytax_taxonomies filter should be applied when registering meta boxes.'
		);
	}

	public function test_register_meta_boxes_applies_post_type_filter() {
		global $wp_meta_boxes;

		$cpt = uniqid();

		add_filter( 'stickytax_post_types', function ( $post_types ) use ( $cpt ) {
			$post_types[] = $cpt;

			return $post_types;
		} );

		Meta\register_meta_boxes();

		$this->assertArrayHasKey(
			'sticky-tax',
			$wp_meta_boxes[ $cpt ]['side']['default'],
			'The stickytax_post_types filter should be applied when registering meta boxes.'
		);
	}

	public function test_register_meta_boxes_returns_early_if_no_eligible_taxonomies_were_found() {
		global $wp_meta_boxes;

		add_filter( 'stickytax_taxonomies', function ( $taxonomies ) {
			return [];
		} );

		Meta\register_meta_boxes();

		$this->assertArrayNotHasKey(
			'sticky-tax',
			$wp_meta_boxes['post']['side']['default'],
			'If there are no taxonomies to stick to, the meta box should not be registered.'
		);
	}

	public function test_save_post() {
		$post_id = $this->factory()->post->create();
		$cat_id  = self::factory()->category->create();
		$_POST   = [
			'sticky-tax-nonce'   => wp_create_nonce( 'sticky-tax' ),
			'sticky-tax-term-id' => [ $cat_id ],
		];

		Meta\save_post( $post_id );

		$this->assertEquals( [ $cat_id ], get_post_meta( $post_id, '_sticky_tax' ) );
	}

	public function test_save_post_verifies_nonce() {
		$post_id = $this->factory()->post->create();
		$cat_id  = self::factory()->category->create();
		$_POST   = [
			'sticky-tax-nonce'   => uniqid(),
			'sticky-tax-term-id' => [ $cat_id ],
		];

		Meta\save_post( $post_id );

		$this->assertEmpty( get_post_meta( $post_id, '_sticky_tax' ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_save_post_does_not_execute_on_autosave() {
		$post_id = $this->factory()->post->create();
		$cat_id  = self::factory()->category->create();
		$_POST   = [
			'sticky-tax-nonce'   => wp_create_nonce( 'sticky-tax' ),
			'sticky-tax-term-id' => $cat_id,
		];

		define( 'DOING_AUTOSAVE', true );

		Meta\save_post( $post_id );

		$this->assertEmpty( get_post_meta( $post_id, '_sticky_tax' ) );
	}

	public function test_save_post_clears_previous_values() {
		$post_id = $this->factory()->post->create();
		$old_cat = self::factory()->category->create();
		$cat_id  = self::factory()->category->create();
		$_POST   = [
			'sticky-tax-nonce'   => wp_create_nonce( 'sticky-tax' ),
			'sticky-tax-term-id' => [ $cat_id ],
		];

		// Previously, this post was sticky in $old_cat.
		Meta\sticky_post_for_term( $post_id, $old_cat );

		// After saving the post, only the new cat should be set.
		Meta\save_post( $post_id );

		$this->assertEquals( [ $cat_id ], get_post_meta( $post_id, '_sticky_tax' ) );
	}

	public function test_save_post_handles_diffs() {
		$post_id = $this->factory()->post->create();
		$cat1    = self::factory()->category->create();
		$cat2    = self::factory()->category->create();
		$cat3    = self::factory()->category->create();
		$_POST   = [
			'sticky-tax-nonce'   => wp_create_nonce( 'sticky-tax' ),
			'sticky-tax-term-id' => [ $cat2 ],
		];

		// Previously, this post was sticky in $old_cat.
		Meta\sticky_post_for_term( $post_id, $cat1 );
		Meta\sticky_post_for_term( $post_id, $cat2 );

		// After saving the post, only the new cat should be set.
		Meta\save_post( $post_id );

		$this->assertEquals( [ $cat2 ], get_post_meta( $post_id, '_sticky_tax' ) );
	}

	/**
	 * @dataProvider register_script_hooks_provider()
	 */
	public function test_register_scripts( $hook, $expected ) {
		$this->assertFalse( wp_script_is( 'select2', 'registered' ) );
		$this->assertFalse( wp_style_is( 'select2', 'registered' ) );

		Meta\register_scripts( $hook );

		$this->assertEquals( $expected, wp_script_is( 'select2', 'enqueued' ) );
		$this->assertEquals( $expected, wp_style_is( 'select2', 'enqueued' ) );
	}

	public function register_script_hooks_provider() {
		return [
			'post edit screen' => [ 'post.php', true ],
			'new post screen'  => [ 'post-new.php', true ],
			'admin index page' => [ 'index.php', false ],
			'post list table'  => [ 'edit.php', false ],
		];
	}

	public function test_register_scripts_checks_for_existing_scripts() {
		wp_register_script( 'select2', 'https://example.com', null, '0.0.7', true );
		wp_register_style( 'select2', 'https://example.com', null, '0.0.7', true );

		Meta\register_scripts( 'post.php' );

		$this->assertEquals( '0.0.7', wp_scripts()->registered['select2']->ver );
		$this->assertEquals( '0.0.7', wp_styles()->registered['select2']->ver );
	}

	public function test_register_scripts_will_register_style_if_script_is_missing() {
		wp_register_script( 'select2', 'https://example.com', null, '0.0.7', true );

		Meta\register_scripts( 'post.php' );

		$this->assertTrue( wp_style_is( 'select2', 'enqueued' ) );
		$this->assertEquals( '0.0.7', wp_scripts()->registered['select2']->ver );
		$this->assertEquals( '0.0.7', wp_styles()->registered['select2']->ver );
	}
}
