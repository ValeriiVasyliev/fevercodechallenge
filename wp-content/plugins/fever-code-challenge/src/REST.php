<?php
/**
 * REST class file.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Query;

/**
 * Class REST
 */
class REST {

	/**
	 * REST namespace.
	 */
	public const REST_NAMESPACE = 'fever-code-challenge/v1';

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	protected Plugin $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin The plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize class hooks.
	 */
	public function init(): void {
		$this->register_hooks();
	}

	/**
	 * Register hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/pokemon',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_pokemon_list' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit'        => array(
						'description'       => __( 'Number of Pokémon to return', 'fever-code-challenge' ),
						'type'              => 'integer',
						'required'          => false,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && intval( $param ) >= 1;
						},
					),
					'order'        => array(
						'description' => __( 'Order of the results: title, rand, or date', 'fever-code-challenge' ),
						'type'        => 'string',
						'required'    => false,
						'enum'        => array( 'title', 'rand', 'date' ), // enforce allowed values
					),
					'pokemon_type' => array(
						'description' => __( 'Filter by Pokémon type', 'fever-code-challenge' ),
						'type'        => 'string',
						'required'    => false,
					),
					'page'         => array(
						'description'       => __( 'Page number for pagination', 'fever-code-challenge' ),
						'type'              => 'integer',
						'required'          => false,
						'default'           => 1,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && intval( $param ) >= 1;
						},
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/pokemon/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_pokemon_data' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $param, $request, $key ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);
	}

	/**
	 * Get a list of Pokémon with optional filtering, ordering, and pagination.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_pokemon_list( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// Read and sanitize parameters
		$limit        = intval( $request->get_param( 'limit' ) );
		$page         = max( 1, intval( $request->get_param( 'page' ) ) );
		$order_param  = $request->get_param( 'order' );
		$pokemon_type = sanitize_text_field( $request->get_param( 'pokemon_type' ) );

		// Validate order
		$valid_orders = array( 'title', 'rand', 'date' );
		$order_param  = in_array( $order_param, $valid_orders, true ) ? $order_param : 'title';

		// Base query args
		$args = array(
			'post_type'      => 'pokemon',
			'post_status'    => 'publish',
			'posts_per_page' => $limit > 0 ? $limit : -1, // -1 means no limit
		);

		// Apply pagination only if limit is positive
		if ( $limit > 0 ) {
			$args['paged'] = $page;
		}

		// Ordering
		switch ( $order_param ) {
			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			case 'rand':
				$args['orderby'] = 'rand';
				break;
			case 'date':
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
		}

		// Filter by primary/secondary meta key
		if ( ! empty( $pokemon_type ) ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => 'primary_type',
					'value'   => $pokemon_type,
					'compare' => '=',
				),
				array(
					'key'     => 'secondary_type',
					'value'   => $pokemon_type,
					'compare' => '=',
				),
			);
		}

		// Query posts
		$query = new WP_Query( $args );

		$pokemon_list = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$newest_dex = intval( get_post_meta( get_the_ID(), 'newest_dex', true ) );
				if ( empty( $newest_dex ) ) {
					continue;
				}

				$pokemon_list[] = array(
					'id'             => $newest_dex,
					'name'           => get_the_title(),
					'link'           => rest_url( self::REST_NAMESPACE . '/pokemon/' . $newest_dex ),
					'content'        => wp_kses_post( get_the_content() ),
					'featured_image' => get_the_post_thumbnail_url( get_the_ID(), 'full' ) ?: '',
					'permalink'      => get_permalink(),
				);
			}
			wp_reset_postdata();
		}

		// Response structure
		$response = array(
			'data' => $pokemon_list,
		);

		// Add pagination metadata only if paginated
		if ( $limit > 0 ) {
			$response['total'] = (int) $query->found_posts;
			$response['pages'] = (int) $query->max_num_pages;
		}

		return rest_ensure_response( $response );
	}



	/**
	 * Get Pokémon data by ID.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_pokemon_data( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// Get the Pokémon ID from the request.
		$id = $request->get_param( 'id' );

		// Get the post with post type 'pokemon' and newest_dex meta key matching the ID.
		$args = array(
			'post_type'      => 'pokemon',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional use for specific lookup.
				array(
					'key'     => 'newest_dex',
					'value'   => $id,
					'compare' => '=',
				),
			),
		);

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return new WP_Error( 'no_pokemon', __( 'Pokémon not found.', 'fever-code-challenge' ), array( 'status' => 404 ) );
		}

		$query->the_post();

		$pokemon_data = array(
			'id'             => get_post_meta( get_the_ID(), 'newest_dex', true ),
			'name'           => get_the_title(),
			'content'        => wp_kses_post( get_the_content() ),
			'featured_image' => get_the_post_thumbnail_url( get_the_ID(), 'full' ) ? get_the_post_thumbnail_url( get_the_ID(), 'full' ) : '',
			'weight'         => get_post_meta( get_the_ID(), 'weight', true ),
			'primary_type'   => get_post_meta( get_the_ID(), 'primary_type', true ),
			'secondary_type' => get_post_meta( get_the_ID(), 'secondary_type', true ),
			'oldest_dex'     => get_post_meta( get_the_ID(), 'oldest_dex', true ),
		);

		wp_reset_postdata(); // Reset post data after the query.

		return rest_ensure_response( $pokemon_data );
	}
}
