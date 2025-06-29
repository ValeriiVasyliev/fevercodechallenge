<?php
/**
 * Admin class file.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge;

use WP_Query;

/**
 * Class Admin
 *
 * Handles all admin-related functionality.
 */
class Admin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	protected Plugin $plugin;

	/**
	 * Admin constructor.
	 *
	 * @param Plugin $plugin The main plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize admin logic.
	 */
	public function init(): void {
		$this->register_hooks();
	}

	/**
	 * Register admin-specific hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'load-edit.php', array( $this, 'add_form_create_pokemon' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_create_new_pokemon', array( $this, 'handle_create_new_pokemon' ) );
		add_filter( 'manage_pokemon_posts_columns', array( $this, 'fever_add_featured_image_column' ) );
		add_action( 'manage_pokemon_posts_custom_column', array( $this, 'fever_show_featured_image_column' ), 10, 2 );
		add_filter( 'post_thumbnail_html', array( $this, 'fever_add_thumbnail_class' ), 10, 5 );
		add_action( 'admin_head', array( $this, 'fever_add_admin_column_styles' ) );
	}

	/**
	 * Add form to create a new Pokemon.
	 */
	public function add_form_create_pokemon(): void {
		$screen = get_current_screen();
		if ( 'pokemon' === $screen->post_type ) {
			add_action(
				'all_admin_notices',
				function () {
					echo '<div class="notice notice-info is-dismissible">';
					echo '<p>' . esc_html__( 'Click the button below to load a Pokémon from the API.', 'fever-code-challenge' ) . '</p>';
					echo '<p>';
					echo '<a href="#" class="button button-primary create-pokemon-ajax" data-action="create_new_pokemon">';
					echo esc_html__( 'Load Pokémon from API', 'fever-code-challenge' );
					echo '</a>';
					echo '</p>';
					echo '</div>';
				}
			);
		}
	}

	/**
	 * Enqueue scripts in the admin.
	 */
	public function enqueue_scripts(): void {
		wp_register_script(
			'fever_code_challenge_admin',
			$this->plugin->plugin_url() . '/dist/admin.js',
			array( 'wp-i18n' ),
			filemtime( $this->plugin->plugin_dir() . '/dist/admin.js' ),
			true
		);

		wp_localize_script(
			'fever_code_challenge_admin',
			'feverCodeChallengeAdmin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'create_new_pokemon' ),
			)
		);

		wp_enqueue_script( 'fever_code_challenge_admin' );
	}

	/**
	 * Handle AJAX request to create a new Pokemon.
	 *
	 * @return void
	 */
	public function handle_create_new_pokemon(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You do not have permission to create a Pokemon.', 'fever-code-challenge' ),
				)
			);
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'create_new_pokemon' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid nonce.', 'fever-code-challenge' ),
				)
			);
		}

		$pokemons = $this->plugin->get_api()->get_list( 100000, 0 );
		if ( empty( $pokemons ) || ! is_array( $pokemons['results'] ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Failed to retrieve Pokemon list.', 'fever-code-challenge' ),
				)
			);
		}

		$random_index = array_rand( $pokemons['results'] );
		$pokemon_name = $pokemons['results'][ $random_index ]['name'];
		$data         = $this->plugin->get_api()->get_pokemon_data( $pokemon_name );

		if ( empty( $data ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Failed to retrieve Pokemon details.', 'fever-code-challenge' ),
				)
			);
		}

		$query               = new WP_Query(
			array(
				'post_type'      => 'pokemon',
				'title'          => $data['name'],
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);
		$existing_pokemon_id = $query->posts[0] ?? 0;

		$post_data = array(
			'post_title'   => $data['name'],
			'post_content' => $data['description'],
			'post_status'  => 'publish',
			'post_type'    => 'pokemon',
		);

		if ( $existing_pokemon_id ) {
			$post_data['ID'] = $existing_pokemon_id;
			$post_id         = wp_update_post( $post_data );
			$action          = 'update';
		} else {
			$post_id = wp_insert_post( $post_data );
			$action  = 'create';
		}

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						// translators: %s is either "create" or "update".
						esc_html__( 'Failed to %s Pokemon post.', 'fever-code-challenge' ),
						$action
					),
				)
			);
		}

		if ( ! empty( $data['image_url'] ) ) {
			$existing_image_url = get_post_meta( $post_id, '_external_image_url', true );
			if ( $data['image_url'] !== $existing_image_url ) {
				$image_id = media_sideload_image( $data['image_url'], $post_id, null, 'id' );
				if ( is_wp_error( $image_id ) ) {
					wp_send_json_error(
						array( 'message' => esc_html__( 'Failed to download Pokemon image.', 'fever-code-challenge' ) )
					);
				}
				set_post_thumbnail( $post_id, $image_id );
				update_post_meta( $post_id, '_external_image_url', $data['image_url'] );
			}
		}

		$custom_fields = array(
			'weight'          => $data['weight'],
			'primary_type'    => $data['primary_type'],
			'secondary_type'  => $data['secondary_type'],
			'pokedex_numbers' => $data['pokedex_numbers'],
			'oldest_dex'      => $data['oldest_dex'],
			'newest_dex'      => $data['newest_dex'],
		);

		foreach ( $custom_fields as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		$data['featured_image'] = get_the_post_thumbnail_url( $post_id, 'full' );
		$data['post_id']        = $post_id;
		$data['permalink']      = get_permalink( $post_id );

		wp_send_json_success(
			array(
				'message' => esc_html__( 'Pokemon created successfully.', 'fever-code-challenge' ),
				'pokemon' => $data,
			)
		);
	}

	/**
	 * Adds a featured image column to the Pokemon posts list table.
	 *
	 * @param array $columns The existing columns in the posts table.
	 * @return array Modified array of columns with the featured image column added.
	 */
	public function fever_add_featured_image_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( 'title' === $key ) {
				$new_columns['featured_image'] = esc_html__( 'Featured Image', 'fever-code-challenge' );
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
	}

	/**
	 * Displays the featured image in the custom column for each Pokemon post.
	 *
	 * @param string $column The name of the column being displayed.
	 * @param int    $post_id The ID of the current post.
	 */
	public function fever_show_featured_image_column( $column, $post_id ) {
		if ( 'featured_image' === $column ) {
			$thumbnail = get_the_post_thumbnail( $post_id, 'thumbnail' );
			echo $thumbnail ? wp_kses_post( $thumbnail ) : esc_html__( 'No Image', 'fever-code-challenge' );
		}
	}

	/**
	 * Adds a custom CSS class to Pokemon thumbnail images.
	 *
	 * @param string $html The thumbnail HTML.
	 * @param int    $post_id The post ID.
	 * @param int    $post_thumbnail_id The thumbnail attachment ID.
	 * @param string $size The size of the image being displayed.
	 * @param array  $_attr Additional attributes for the image. Unused.
	 * @return string Modified HTML with the additional class.
	 *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
	 */
	public function fever_add_thumbnail_class( $html, $post_id, $post_thumbnail_id, $size, $_attr ) {
		if ( 'thumbnail' === $size ) {
			$html = str_replace( '<img', '<img class="pokemon-thumbnail"', $html );
		}
		return $html;
	}
	// phpcs:enable


	/**
	 * Adds custom CSS styles for the featured image column in the admin interface.
	 */
	public function fever_add_admin_column_styles() {
		echo '<style>
            .column-featured_image {
                width: 200px;
            }
            .pokemon-thumbnail {
                max-width: 100%;
                height: auto;
            }
        </style>';
	}
}
