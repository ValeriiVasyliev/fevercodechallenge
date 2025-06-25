<?php
/**
 * Main Plugin class.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge;

use FeverCodeChallenge\Interfaces\IAPI;
use WP_Error;

/**
 * Class Plugin
 *
 * Responsible for bootstrapping the plugin.
 */
final class Plugin {

	/**
	 * Base URL to the plugin directory.
	 *
	 * @var string
	 */
	protected string $plugin_url;

	/**
	 * Absolute path to the plugin directory.
	 *
	 * @var string
	 */
	protected string $plugin_dir;

	/**
	 * API instance implementing IAPI interface.
	 *
	 * @var IAPI
	 */
	private IAPI $api;

	/**
	 * Plugin constructor.
	 *
	 * @param IAPI   $api               Injected API instance.
	 * @param string $plugin_file_path  Path to main plugin file (__FILE__).
	 */
	public function __construct( IAPI $api, string $plugin_file_path ) {
		$this->api        = $api;
		$this->plugin_url = untrailingslashit( plugin_dir_url( $plugin_file_path ) );
		$this->plugin_dir = dirname( $plugin_file_path );
	}

	/**
	 * Initialize the plugin.
	 *
	 * Hooks are added and main modules are initialized.
	 */
	public function init(): void {
		$this->register_hooks();

		// Initialize core functionality
		if ( is_admin() ) {
			( new Admin( $this ) )->init();
		} else {
			( new Front( $this ) )->init();
		}
	}

	/**
	 * Register WordPress-specific hooks.
	 */
	protected function register_hooks(): void {
		$this->load_plugin_textdomain();

		// register custom post type for Pokemon
		add_action( 'init', array( $this, 'register_pokemon_post_type' ) );
	}

	/**
	 * Load plugin text domain for localization.
	 */
	protected function load_plugin_textdomain(): void {
		$domain = 'fever-code-challenge';

		if ( isset( $GLOBALS['l10n'][ $domain ] ) ) {
			return;
		}

		load_plugin_textdomain(
			$domain,
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/../languages/'
		);
	}

	/**
	 * Get the plugin's base URL.
	 *
	 * @return string
	 */
	public function plugin_url(): string {
		return $this->plugin_url;
	}

	/**
	 * Get the plugin's base directory path.
	 *
	 * @return string
	 */
	public function plugin_dir(): string {
		return $this->plugin_dir;
	}

	/**
	 * Get the full path to a file inside the plugin directory.
	 *
	 * @param string $path Optional subpath.
	 * @return string
	 */
	public function get_path( string $path = '' ): string {
		return $this->plugin_dir . '/' . ltrim( $path, '/' );
	}

	/**
	 * Get the injected API instance.
	 *
	 * @return IAPI
	 */
	public function get_api(): IAPI {
		return $this->api;
	}

	/**
	 * Register custom post type for Pokemon.
	 *
	 * @return void
	 */
	public function register_pokemon_post_type(): void {

		$labels = [
			'name'               => __( 'Pokemons', 'fever-code-challenge' ),
			'singular_name'      => __( 'Pokemon', 'fever-code-challenge' ),
			'add_new'            => __( 'Add New', 'fever-code-challenge' ),
			'add_new_item'       => __( 'Add New Pokemon', 'fever-code-challenge' ),
			'edit_item'          => __( 'Edit Pokemon', 'fever-code-challenge' ),
			'new_item'           => __( 'New Pokemon', 'fever-code-challenge' ),
			'view_item'          => __( 'View Pokemon', 'fever-code-challenge' ),
			'search_items'       => __( 'Search Pokemons', 'fever-code-challenge' ),
			'not_found'          => __( 'No Pokemons found', 'fever-code-challenge' ),
			'not_found_in_trash' => __( 'No Pokemons found in Trash', 'fever-code-challenge' ),
		];

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'pokemon' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 5,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		);

		register_post_type( 'pokemon', $args );
	}
}
