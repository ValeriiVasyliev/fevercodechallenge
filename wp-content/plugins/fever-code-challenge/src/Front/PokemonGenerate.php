<?php
/**
 * PokemonGenerate class file.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge\Front;

use FeverCodeChallenge\Plugin;

/**
 * Class PokemonGenerate
 */
final class PokemonGenerate {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected Plugin $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize the class.
	 */
	public function init(): void {
		$this->hooks();
	}

	/**
	 * Register class hooks.
	 */
	protected function hooks(): void {
	}
}
