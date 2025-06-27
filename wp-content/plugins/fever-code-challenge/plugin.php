<?php
/**
 *  Fever Code Challenge - Pokemon API Plugin
 *
 * @package           fever-code-challenge
 * @author            Valerii Vasyliev
 * @license           GPL-2.0-or-later
 * @wordpress-plugin
 *
 * Plugin Name:       Fever Code Challenge - Pokemon API Plugin
 * Description:       Fever Code Challenge - Pokemon API Plugin
 * Version:           1.0.1
 * Requires at least: 6.0
 * Tested up to:      6.3
 * Requires PHP:      8.0
 * Author:            Valerii Vasyliev
 * Author URI:        https://www.codeable.io/developers/valerii-vasyliev/?ref=OaT0y
 * License:           GPL-2.0-or-later
 * Text Domain:       fever-code-challenge
 */

namespace FeverCodeChallenge;

// Load the autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Activation hook.
 *
 * @return void
 */
function fever_code_challenge_activate() {

	// Rewrite rules for the custom pages.
	add_rewrite_rule( '^generate/?$', 'index.php?generate_pokemon=1', 'top' );
	add_rewrite_rule( '^random/?$', 'index.php?random_pokemon=1', 'top' );
	add_rewrite_rule( '^pokemon-list/?$', 'index.php?pokemon_list=1', 'top' );

	// Custom rule for single pokemon posts with slug.
	add_rewrite_rule(
		'^pokemon/([^/]+)/?$',
		'index.php?pokemon=$matches[1]',
		'top'
	);

	// Flush rewrite rules on activation.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'FeverCodeChallenge\fever_code_challenge_activate' );

/**
 * Deactivation hook.
 *
 * @return void
 */
function fever_code_challenge_deactivate() {
	// Flush rewrite rules on deactivation.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'FeverCodeChallenge\fever_code_challenge_deactivate' );

// Get plugin path.
define( 'FEVER_CODE_CHALLENGE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Plugin file.
define( 'FEVER_CODE_CHALLENGE_PLUGIN_FILE', plugin_basename( __FILE__ ) );

// Initialize the plugin.
add_action( 'plugins_loaded', array( new Plugin( new API\PokeAPI(), __FILE__ ), 'init' ) );
