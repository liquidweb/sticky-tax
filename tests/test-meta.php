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

		wp_dequeue_script( 'sticky-tax-admin' );
		wp_dequeue_style( 'sticky-tax-admin' );
		wp_deregister_script( 'sticky-tax-admin' );
		wp_deregister_style( 'sticky-tax-admin' );
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
		$post_id = $this->factory()->post->create();
		$cat_id  = self::factory()->category->create();

		$this->assertTrue( Meta\sticky_post_for_term( $post_id, $cat_id ) );
		$this->assertFalse( Meta\sticky_post_for_term( $post_id, $cat_id ) );
		$this->assertFalse( Meta\sticky_post_for_term( $post_id, $cat_id ) );

		$this->assertCount( 1, get_post_meta( $post_id, '_sticky_tax' ) );
	}

	public function test_register_meta_boxes_applies_taxonomy_filter() {
		global $wp_meta_boxes;

		$tax = get_taxonomy( 'category' );

		add_filter( 'stickytax_taxonomies', function ( $taxonomies ) use ( $tax ) {
			$taxonomies[] = $tax;

			return $taxonomies;
		} );

		$post = $this->factory->post->create_and_get();
		$post_type = $post->post_type;

		Meta\register_meta_boxes( $post, $post_type );

		$this->assertArrayHasKey(
			$tax->name,
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

		$post = $this->factory->post->create_and_get();
		$post_type = $post->post_type;

		Meta\register_meta_boxes( $post, $post_type );

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

		$post = $this->factory->post->create_and_get();
		$post_type = $post->post_type;

		Meta\register_meta_boxes( $post, $post_type );

		$this->assertArrayNotHasKey(
			'sticky-tax',
			$wp_meta_boxes['post']['side']['default'],
			'If there are no taxonomies to stick to, the meta box should not be registered.'
		);
	}

	public function test_render_meta_box() {
		$this->factory()->category->create();

		$this->assertCount( 2, get_terms( [
			'taxonomy'   => 'category',
			'hide_empty' => false,
		] ), 'There should be two categories: "Uncategorized" (defined by default) and our new cat.' );

		$output = $this->render_meta_box_output();

		$this->assertContains(
			'<div class="term-sticky-list-wrap">',
			$output,
			'A <div> element with class "term-sticky-list-wrap" should be created.'
		);
		$this->assertContains(
			'<input type="hidden" id="sticky-tax-nonce" name="sticky-tax-nonce"',
			$output,
			'The meta box should contain a hidden "sticky-tax-nonce" input.'
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

	public function test_save_post_can_remove_all_terms() {
		$post_id = $this->factory()->post->create();
		$cat_id  = self::factory()->category->create();
		$_POST   = [
			'sticky-tax-nonce'   => wp_create_nonce( 'sticky-tax' ),
			'sticky-tax-term-id' => null, // Field is not populated if there are no selections.
		];

		Meta\sticky_post_for_term( $post_id, $cat_id );

		Meta\save_post( $post_id );

		$this->assertEmpty( get_post_meta( $post_id, '_sticky_tax' ) );
	}

	public function test_save_post_does_nothing_if_there_are_no_changes() {
		$post_id = $this->factory()->post->create();
		$cat_id  = self::factory()->category->create();
		$_POST   = [
			'sticky-tax-nonce'   => wp_create_nonce( 'sticky-tax' ),
			'sticky-tax-term-id' => [ $cat_id ],
		];

		Meta\sticky_post_for_term( $post_id, $cat_id );
		$before = get_post_meta( $post_id, '_sticky_tax' );

		Meta\save_post( $post_id );

		$this->assertSame( $before, get_post_meta( $post_id, '_sticky_tax' ) );
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

		// Previously, this post was sticky in $cat1 and $cat2.
		Meta\sticky_post_for_term( $post_id, $cat1 );
		Meta\sticky_post_for_term( $post_id, $cat2 );

		// After saving the post, only the new cat should be set.
		Meta\save_post( $post_id );

		$this->assertEquals( [ $cat2 ], get_post_meta( $post_id, '_sticky_tax' ) );
	}

	public function test_save_post_clears_caches() {
		$post_id = $this->factory()->post->create();
		$cat1    = self::factory()->category->create();
		$cat2    = self::factory()->category->create();
		$cat3    = self::factory()->category->create();
		$_POST   = [
			'sticky-tax-nonce'   => wp_create_nonce( 'sticky-tax' ),
			'sticky-tax-term-id' => [ $cat2 ],
		];

		// Prime the caches.
		wp_cache_set( 'term_' . $cat1, [ uniqid() ], 'sticky-tax' );
		wp_cache_set( 'term_' . $cat2, [ uniqid() ], 'sticky-tax' );
		wp_cache_set( 'term_' . $cat3, [ uniqid() ], 'sticky-tax' );

		// Previously, this post was sticky in $cat1.
		Meta\sticky_post_for_term( $post_id, $cat1 );

		Meta\save_post( $post_id );

		// Since $cat1 and $cat2 have changed, their caches should be purged.
		$this->assertEmpty( wp_cache_get( 'term_' . $cat1, 'sticky-tax' ) );
		$this->assertEmpty( wp_cache_get( 'term_' . $cat2, 'sticky-tax' ) );

		// $cat3 was not touched, so its cache should persist.
		$this->assertNotEmpty( wp_cache_get( 'term_' . $cat3, 'sticky-tax' ) );
	}

	/**
	 * @dataProvider register_script_hooks_provider()
	 */
	public function test_register_scripts( $hook, $expected ) {
		$this->assertFalse( wp_script_is( 'sticky-tax-admin', 'registered' ) );
		$this->assertFalse( wp_style_is( 'sticky-tax-admin', 'registered' ) );

		Meta\register_scripts( $hook );

		$this->assertEquals( $expected, wp_script_is( 'sticky-tax-admin', 'enqueued' ) );
		$this->assertEquals( $expected, wp_style_is( 'sticky-tax-admin', 'enqueued' ) );
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
		wp_register_script( 'sticky-tax-admin', 'https://example.com', null, '0.0.7', true );
		wp_register_style( 'sticky-tax-admin', 'https://example.com', null, '0.0.7', true );

		Meta\register_scripts( 'post.php' );

		$this->assertEquals( '0.0.7', wp_scripts()->registered['sticky-tax-admin']->ver );
		$this->assertEquals( '0.0.7', wp_styles()->registered['sticky-tax-admin']->ver );
	}

	public function test_register_scripts_will_register_style_if_script_is_missing() {
		wp_register_script( 'sticky-tax-admin', 'https://example.com', null, '0.0.7', true );

		Meta\register_scripts( 'post.php' );

		$this->assertTrue( wp_style_is( 'sticky-tax-admin', 'enqueued' ) );
	}

	/**
	 * Shortcut for rendering the meta box and capturing it's output.
	 *
	 * @param WP_Post $post Optional. The post object. If one isn't defined, one will be created.
	 * @param array   $meta Optional. The $post_meta argument for the render_meta_box() function.
	 *                      Default is an array with an 'args' array, containing "category".
	 */
	protected function render_meta_box_output( $post = null, $meta = null ) {
		if ( ! $post ) {
			$post = $this->factory()->post->create_and_get();
		}

		if ( null === $meta ) {
			$meta = [
				'args' => [ 'category' ],
			];
		}

		ob_start();
		Meta\render_meta_box( $post, $meta );
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}
