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
		add_action( 'load-edit.php', [ $this, 'add_form_create_pokemon' ] );

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Handle AJAX request to create a new Pokemon.
		add_action( 'wp_ajax_create_new_pokemon', [ $this, 'handle_create_new_pokemon' ] );

		// Add image column to the Pokemon list table.
		add_filter( 'manage_pokemon_posts_columns', [ $this, 'fever_add_featured_image_column' ] );

		// Output featured image in the custom column.
		add_action( 'manage_pokemon_posts_custom_column', [ $this, 'fever_show_featured_image_column' ], 10, 2 );

		// Add class to the thumbnail image.
		add_filter( 'post_thumbnail_html', [ $this, 'fever_add_thumbnail_class' ], 10, 5 );

		// Add custom styles for the admin column.
		add_action( 'admin_head', [ $this, 'fever_add_admin_column_styles' ] );
	}

	/**
	 * Add form to create a new Pokemon.
	 */
	public function add_form_create_pokemon(): void {
		$screen = get_current_screen();

		// Check if we are on the edit.php screen for the 'pokemon' post type.
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
		if ( FEVER_CODE_CHALLENGE_DEBUG ) {
			wp_register_script(
				'fever_code_challenge-admin',
				$this->plugin->plugin_url() . '/assets/js/admin.js',
				[ 'wp-i18n' ],
				filemtime( $this->plugin->plugin_dir() . '/assets/js/admin.js' ),
				true
			);
		} else {
			wp_register_script(
				'fever_code_challenge_admin',
				$this->plugin->plugin_url() . '/dist/admin.js',
				[ 'wp-i18n' ],
				filemtime( $this->plugin->plugin_dir() . '/dist/admin.js' ),
				true
			);
		}

		// Localize the script with nonce and action.
		wp_localize_script(
			'fever_code_challenge-admin',
			'feverCodeChallengeAdmin',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'create_new_pokemon' ),
			]
		);

		wp_enqueue_script( 'fever_code_challenge-admin' );
	}

	/**
	 * Handle AJAX request to create a new Pokemon.
	 *
	 * @return void
	 */
	public function handle_create_new_pokemon(): void {
		// Check if the user has permission to create a Pokemon.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'You do not have permission to create a Pokemon.', 'fever-code-challenge' ),
				]
			);
		}

		// Check nonce for security.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'create_new_pokemon' ) ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Invalid nonce.', 'fever-code-challenge' ),
				]
			);
		}

		// Get list of Pokemon.
		$pokemons = $this->plugin->get_api()->get_list( 100000, 0, true );
		if ( empty( $pokemons ) || ! is_array( $pokemons['results'] ) ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Failed to retrieve Pokemon list.', 'fever-code-challenge' ),
				]
			);
		}

		// Pick random.
		$random_index = array_rand( $pokemons['results'] );
		$pokemon_name = $pokemons['results'][ $random_index ]['name'];

		// Get structured data from API.
		$data = $this->plugin->get_api()->get_pokemon_data( $pokemon_name, true );
		if ( empty( $data ) ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Failed to retrieve Pokemon details.', 'fever-code-challenge' ),
				]
			);
		}

		// Try to find existing Pokémon post by title.
		$query = new WP_Query(
			[
				'post_type'      => 'pokemon',
				'title'          => $data['name'],
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			]
		);

		$existing_pokemon_id = $query->posts[0] ?? 0;

		$post_data = [
			'post_title'   => $data['name'],
			'post_content' => $data['description'],
			'post_status'  => 'publish',
			'post_type'    => 'pokemon',
		];

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
				[
					'message' => sprintf(
						// translators: %s is either "create" or "update".
						esc_html__( 'Failed to %s Pokemon post.', 'fever-code-challenge' ),
						$action
					),
				]
			);
		}

		// Handle featured image (only update if external URL changed).
		if ( ! empty( $data['image_url'] ) ) {
			$existing_image_url = get_post_meta( $post_id, '_external_image_url', true );
			if ( $data['image_url'] !== $existing_image_url ) {
				$image_id = media_sideload_image( $data['image_url'], $post_id, null, 'id' );
				if ( is_wp_error( $image_id ) ) {
					wp_send_json_error(
						[ 'message' => esc_html__( 'Failed to download Pokemon image.', 'fever-code-challenge' ) ]
					);
				}
				set_post_thumbnail( $post_id, $image_id );
				update_post_meta( $post_id, '_external_image_url', $data['image_url'] );
			}
		}

		// Update custom fields.
		$custom_fields = [
			'weight'          => $data['weight'],
			'primary_type'    => $data['primary_type'],
			'secondary_type'  => $data['secondary_type'],
			'pokedex_numbers' => $data['pokedex_numbers'],
			'oldest_dex'      => $data['oldest_dex'],
			'newest_dex'      => $data['newest_dex'],
		];

		foreach ( $custom_fields as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		// Prepare data for response.
		$data['featured_image'] = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
		$data['post_id']        = $post_id;
		$data['permalink']      = get_permalink( $post_id );

		// Send final success response.
		wp_send_json_success(
			[
				'message' => esc_html__( 'Pokemon created successfully.', 'fever-code-challenge' ),
				'pokemon' => $data,
			]
		);
	}

	/**
	 * Adds a featured image column to the Pokemon posts list table.
	 *
	 * This function adds a new column for featured images after the title column
	 * in the WordPress admin Pokemon list view.
	 *
	 * @param array $columns The existing columns in the posts table
	 * @return array Modified array of columns with the featured image column added
	 */
	public function fever_add_featured_image_column( $columns ) {
		$new_columns = [];
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
	 * This function is called for each row in the Pokemon posts table when displaying
	 * the 'featured_image' column. It shows either the post's thumbnail image
	 * or a "No Image" message if no featured image is set.
	 *
	 * @param string $column The name of the column being displayed
	 * @param int    $post_id The ID of the current post
	 */
	public function fever_show_featured_image_column( $column, $post_id ) {
		if ( 'featured_image' === $column ) {
			$thumbnail = get_the_post_thumbnail( $post_id, 'thumbnail' );
			if ( $thumbnail ) {
				echo wp_kses_post( $thumbnail );
			} else {
				echo esc_html__( 'No Image', 'fever-code-challenge' );
			}
		}
	}

	/**
	 * Adds a custom CSS class to Pokemon thumbnail images.
	 *
	 * This function modifies the HTML output of post thumbnails, adding the
	 * 'pokemon-thumbnail' class to images when they are displayed at thumbnail size.
	 *
	 * @param string $html The thumbnail HTML
	 * @param int    $post_id The post ID
	 * @param int    $post_thumbnail_id The thumbnail attachment ID
	 * @param string $size The size of the image being displayed
	 * @param array  $attr Additional attributes for the image
	 * @return string Modified HTML with the additional class
	 */
	public function fever_add_thumbnail_class( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		if ( 'thumbnail' === $size ) {
			$html = str_replace( '<img', '<img class="pokemon-thumbnail"', $html );
		}
		return $html;
	}

	/**
	 * Adds custom CSS styles for the featured image column in the admin interface.
	 *
	 * This function outputs inline CSS to control the width of the featured image column
	 * and ensure proper sizing of Pokemon thumbnail images within the admin interface.
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
