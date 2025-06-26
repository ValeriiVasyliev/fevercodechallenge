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
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
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
					'limit' => array(
						'description'       => __( 'Number of Pokémon to return', 'fever-code-challenge' ),
						'type'              => 'integer',
						'required'          => false,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && intval( $param ) >= 1;
						},
					),
					'order' => array(
						'description' => __( 'Order of the results: title, rand, or date', 'fever-code-challenge' ),
						'type'        => 'string',
						'required'    => false,
						'enum'        => array( 'title', 'rand', 'date' ), // enforce allowed values
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
	 * Get a list of Pokémon.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_pokemon_list( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// Handle query params.
		$limit = intval( $request->get_param( 'limit' ) );
		$limit = $limit > 0 ? $limit : -1; // Default: all posts.

		$order_param  = $request->get_param( 'order' );
		$valid_orders = array( 'title', 'rand', 'date' );
		$order_param  = in_array( $order_param, $valid_orders, true ) ? $order_param : 'title';

		$args = array(
			'post_type'      => 'pokemon',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
		);

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

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return new WP_Error( 'no_pokemon', __( 'No Pokémon found.', 'fever-code-challenge' ), array( 'status' => 404 ) );
		}

		$pokemon_list = array();

		while ( $query->have_posts() ) {
			$query->the_post();

			$newest_dex = get_post_meta( get_the_ID(), 'newest_dex', true );
			$newest_dex = intval( $newest_dex );

			if ( empty( $newest_dex ) ) {
				continue;
			}

			$link = rest_url( self::REST_NAMESPACE . '/pokemon/' . $newest_dex );

			$pokemon_list[] = array(
				'id'             => $newest_dex,
				'name'           => get_the_title(),
				'link'           => $link,
				'content'        => wp_kses_post( get_the_content() ),
				'featured_image' => get_the_post_thumbnail_url( get_the_ID(), 'full' ) ?: '',
				'permalink'      => get_permalink(),
			);
		}

		wp_reset_postdata();

		return rest_ensure_response( $pokemon_list );
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
