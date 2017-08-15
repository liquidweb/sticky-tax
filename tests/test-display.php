<?php
/**
 * Tests for the front-end display of sticky posts.
 *
 * @package Sticky_Tax
 */

use LiquidWeb\StickyTax\Display as Display;
use LiquidWeb\StickyTax\Meta as Meta;

/**
 * Test suite for the display of sticky posts on the front-end of the site.
 */
class DisplayTest extends WP_UnitTestCase {

	public function test_get_sticky_posts_for_term() {
		$cat_id    = self::factory()->category->create();
		$post_ids  = $this->create_posts_and_assign_to_category( 3, $cat_id );
		$sticky_id = $post_ids[1];

		Meta\sticky_post_for_term( $sticky_id, $cat_id );

		$this->assertEquals( [ $sticky_id ], Display\get_sticky_posts_for_term( $cat_id ) );
	}

	public function test_get_sticky_posts_for_term_filters_results() {
		$cat_id = self::factory()->category->create();
		$random = mt_rand( 1, 9999 );

		add_filter( 'stickytax_get_sticky_posts_for_term', function ( $post_ids, $term_id ) use ( $random, $cat_id ) {
			$this->assertEmpty( $post_ids );
			$this->assertEquals( $cat_id, $term_id );

			return [ $random ];
		}, 10, 2 );

		$this->assertEquals( [ $random ], Display\get_sticky_posts_for_term( $cat_id ) );
	}

	public function test_sticky_post_moved_to_front() {
		$cat_id    = self::factory()->category->create();
		$post_ids  = $this->create_posts_and_assign_to_category( 3, $cat_id );
		$sticky_id = $post_ids[1];

		Meta\sticky_post_for_term( $sticky_id, $cat_id );

		$wp_query = $this->get_wp_query_for_url( get_term_link( $cat_id, 'category' ) );

		$this->assertTrue( is_category( $cat_id ) );
		$this->assertCount( 3, $wp_query->posts );
		$this->assertEquals(
			$sticky_id,
			$wp_query->posts[0]->ID,
			sprintf( 'The sticked post (ID #%d) should be pushed to the front of the results.', $sticky_id )
		);
	}

	public function test_sticky_post_id_is_not_duplicated() {
		$cat_id    = self::factory()->category->create();
		$post_ids  = $this->create_posts_and_assign_to_category( 3, $cat_id );
		$wp_query  = $this->get_wp_query_for_url( get_term_link( $cat_id, 'category' ) );
		$sticky_id = $post_ids[1];

		$this->assertEquals(
			1,
			array_count_values( wp_list_pluck( $wp_query->posts, 'ID' ) )[ $sticky_id ],
			'Sticky posts should not appear multiple times.'
		);
	}

	public function test_can_make_multiple_posts_sticky() {
		$cat_id    = self::factory()->category->create();
		$post_ids  = $this->create_posts_and_assign_to_category( 10, $cat_id );
		$sticky_id = [ $post_ids[2], $post_ids[3] ];

		// Make each of these posts sticky.
		foreach ( $sticky_id as $id ) {
			Meta\sticky_post_for_term( $id, $cat_id );
		}

		// Visit the page and investigate the query.
		$wp_query = $this->get_wp_query_for_url( get_term_link( $cat_id, 'category' ) );
		$counter  = 0;

		/*
		 * Start from the end of $sticky_id, as those posts should be considered newer; the order
		 * should be newest sticky => oldest sticky, then newest post => oldest post.
		 */
		while ( ! empty( $sticky_id ) ) {
			$sticky = array_pop( $sticky_id );

			$this->assertEquals(
				$sticky,
				$wp_query->posts[ $counter ]->ID,
				sprintf( 'Post #%d was expected to be in position %d of $wp_query->posts', $sticky, $counter )
			);

			$counter++;
		}
	}

	/**
	 * Ensure the pre_get_posts query filter is only applied on taxonomy archive pages.
	 *
	 * @dataProvider wp_query_positive_state_provider()
	 */
	public function test_inject_orderby_clause_positives() {
		$query   = new WP_Query( [
			'suppress_filters' => true,
		] );
		$orderby = uniqid(); // Just need something random.

		// Apply the properties to the WP_Query object.
		foreach ( func_get_args() as $prop ) {
			$query->$prop = true;
		}

		// Ensure we're not returning the orderby statement early due to lack of terms.
		add_filter( 'stickytax_get_sticky_posts_for_term', function () {
			return [ 1, 2, 3 ];
		} );

		$this->assertNotEquals( $orderby, Display\inject_orderby_clause( $orderby, $query ) );
	}

	public function wp_query_positive_state_provider() {
		return [
			'category archive page'           => [ 'is_category' ],
			'paginated category archive page' => [ 'is_category', 'is_paged' ],
			'tag archive page'                => [ 'is_tag' ],
			'custom taxonomy archive page'    => [ 'is_tax' ],
		];
	}

	/**
	 * @dataProvider wp_query_negative_state_provider()
	 */
	public function test_inject_orderby_clause_negatives() {
		$query   = new WP_Query( [
			'suppress_filters' => true,
		] );
		$orderby = uniqid(); // Just need something random.

		// Apply the properties to the WP_Query object.
		foreach ( func_get_args() as $prop ) {
			$query->$prop = true;
		}

		$this->assertEquals( $orderby, Display\inject_orderby_clause( $orderby, $query ) );
	}

	public function wp_query_negative_state_provider() {
		return [
			'homepage'   => [ 'is_category' ],
			'front page' => [ 'is_category', 'is_paged' ],
			'search'     => [ 'is_search' ],
		];
	}

	/**
	 * Helper function to avoid declaring global PHP variables in the middle of a test method.
	 *
	 * @global $wp_query
	 *
	 * @param string $url The WordPress URL to navigate to, via $this->go_to().
	 * @return WP_Query The $wp_query global for the resulting page.
	 */
	protected function get_wp_query_for_url( $url ) {
		$this->go_to( $url );

		global $wp_query;

		return $wp_query;
	}

	/**
	 * Helper to create multiple posts and assign them to a given category.
	 *
	 * @param int $count  The number of posts to create.
	 * @param int $cat_id The category ID the posts should be associated with.
	 * @return array An array of created post IDs.
	 */
	protected function create_posts_and_assign_to_category( $count, $cat_id ) {
		$post_ids  = self::factory()->post->create_many( $count );

		foreach ( $post_ids as $post_id ) {
			wp_set_object_terms( $post_id, $cat_id, 'category' );
		}

		return $post_ids;
	}
}
