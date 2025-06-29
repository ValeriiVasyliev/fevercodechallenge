<?php
/**
 * PHPUnit bootstrap file for WordPress plugin testing
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Initialize Brain\Monkey
use Brain\Monkey;
Monkey\setUp();

register_shutdown_function(
	function () {
		// Only call tearDown/close at very end.
		Monkey\tearDown();
		if ( class_exists( '\Mockery' ) ) {
			\Mockery::close();
		}
	}
);

// WP_Error stub (only once!)
if ( ! class_exists( '\WP_Error' ) ) {
	class WP_Error {
		private $code;
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code = $code;
		}
		public function get_error_code() {
			return $this->code;
		}
	}
}

// WP_REST_Request stub
if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		private array $params = array();

		public function __construct( $method = '', $route = '' ) {
			// no-op constructor for stub
		}

		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}

		public function set_param( string $key, $value ): void {
			$this->params[ $key ] = $value;
		}

		// optional: to make set_params(['key' => 'value']) usable
		public function set_params( array $params ): void {
			$this->params = $params;
		}
	}
}


// WP_REST_Server stub
if ( ! class_exists( 'WP_REST_Server' ) ) {
	class WP_REST_Server {
		const READABLE   = 'GET';
		const EDITABLE   = 'POST';
		const DELETABLE  = 'DELETE';
		const CREATABLE  = 'POST';
		const ALLMETHODS = 'GET,POST,PUT,DELETE,PATCH';
	}
}

// WP_REST_Response stub
if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		private $data;
		public function __construct( $data = null ) {
			$this->data = $data;
		}
		public function get_data() {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		public static $test_instance = null;
		public $found_posts          = 0;
		public $max_num_pages        = 0;
		public function __construct( $args = array() ) {
			if ( self::$test_instance ) {
				foreach ( get_object_vars( self::$test_instance ) as $k => $v ) {
					$this->$k = $v;
				}
			}
		}
		public function have_posts() {
			return false; }
		public function the_post() {}
	}
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {

		private $code;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code = $code;
		}

		public function get_error_code() {
			return $this->code;
		}
	}
}

// ✅ Stub WP_Screen if it doesn't exist in test context
if ( ! class_exists( 'WP_Screen' ) ) {
	class_alias( \stdClass::class, 'WP_Screen' );
}

// Add this block:
if ( ! class_exists( 'wpdb' ) ) {
	class wpdb {
		public function prepare( $query, ...$args ) {
			return $query; }
		public function get_col( $query ) {
			return array(); }
	}
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

// Get plugin path.
define( 'FEVER_CODE_CHALLENGE_PLUGIN_PATH', dirname( __DIR__ ) . '/' );

// Plugin file.
define( 'FEVER_CODE_CHALLENGE_PLUGIN_FILE', dirname( __DIR__ ) . '/../plugin.php' );

// Get plugin URL.
define( 'FEVER_CODE_CHALLENGE_PLUGIN_URL', 'https://fevercodechallenge.ddev.site/wp-content/plugins/fever-code-challenge' );
