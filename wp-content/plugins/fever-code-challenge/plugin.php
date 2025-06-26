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

// Plugin debug mode.
define( 'FEVER_CODE_CHALLENGE_DEBUG', true );

// Get plugin path.
define( 'FEVER_CODE_CHALLENGE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Plugin file.
define( 'FEVER_CODE_CHALLENGE_PLUGIN_FILE', plugin_basename( __FILE__ ) );

// Initialize the plugin.
add_action( 'plugins_loaded', array( new Plugin( new API\PokeAPI(), __FILE__ ), 'init' ) );
