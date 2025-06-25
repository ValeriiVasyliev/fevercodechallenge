<?php
/**
 * Front class file.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge;

/**
 * Class Front
 *
 * Handles all public-facing functionality of the plugin.
 */
final class Front {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	protected Plugin $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Main plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize front-end logic.
	 */
	public function init(): void {
		$this->register_hooks();
	}

	/**
	 * Register front-end specific hooks.
	 */
	protected function register_hooks(): void {
	}

	/**
	 * Example: Enqueue front-end assets.
	 */
	public function enqueue_assets(): void {
	}
}
