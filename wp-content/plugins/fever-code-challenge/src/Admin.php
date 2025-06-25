<?php
/**
 * Admin class file.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge;

/**
 * Class Admin
 *
 * Handles all admin-related functionality.
 */
final class Admin {

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
	}

	/**
	 * Example method: Add admin menu page.
	 */
	public function add_admin_menu(): void {
		// add_menu_page( ... );
	}

	/**
	 * Example method: Enqueue admin styles/scripts.
	 */
	public function enqueue_assets(): void {
	}
}
