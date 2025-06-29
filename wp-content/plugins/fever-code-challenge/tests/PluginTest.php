<?php

namespace FeverCodeChallenge\Tests;

use FeverCodeChallenge\Plugin;
use FeverCodeChallenge\Interfaces\IAPI;
use Brain\Monkey\Functions;

class PluginTest extends TestCase {
	private Plugin $plugin;
	private IAPI $api;
	private string $plugin_file_path;
	private string $plugin_url = 'http://example.com/wp-content/plugins/fever-code-challenge/';
	private string $plugin_dir = '/path/to/plugin';

	protected function setUp(): void {
		parent::setUp();

		// Mock API interface
		$this->api              = $this->createMock( IAPI::class );
		$this->plugin_file_path = $this->plugin_dir . '/plugin.php';

		// Mock WordPress functions
		Functions\when( 'plugin_dir_url' )->justReturn( $this->plugin_url );
		Functions\when( 'plugin_basename' )->justReturn( 'fever-code-challenge/plugin.php' );
		Functions\when( 'untrailingslashit' )->alias(
			function ( $input ) {
				return rtrim( $input, '/' );
			}
		);
		Functions\when( '__' )->returnArg();

		$this->plugin = new Plugin( $this->api, $this->plugin_file_path );
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( Plugin::class, $this->plugin );
		$this->assertEquals(
			'http://example.com/wp-content/plugins/fever-code-challenge',
			$this->plugin->plugin_url()
		);
		$this->assertEquals( $this->plugin_dir, $this->plugin->plugin_dir() );
	}

	public function testInit(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'init', array( $this->plugin, 'register_pokemon_post_type' ) );

		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with(
				'fever-code-challenge',
				false,
				'fever-code-challenge/../languages/'
			);

		$this->plugin->init();
		$this->addToAssertionCount( 1 );
	}

	public function testRegisterPokemonPostType(): void {
		Functions\expect( 'register_post_type' )
			->once()
			->with(
				'pokemon',
				$this->callback(
					function ( $args ) {
						return isset( $args['labels'] ) &&
						isset( $args['public'] ) &&
						isset( $args['publicly_queryable'] ) &&
						$args['rewrite']['slug'] === 'pokemon' &&
						in_array( 'thumbnail', $args['supports'] ) &&
						in_array( 'custom-fields', $args['supports'] );
					}
				)
			);

		$this->plugin->register_pokemon_post_type();
		$this->addToAssertionCount( 1 );
	}

	public function testLoadPluginTextdomainWithExistingDomain(): void {
		$GLOBALS['l10n']['fever-code-challenge'] = true;

		Functions\expect( 'load_plugin_textdomain' )->never();

		$reflection = new \ReflectionClass( Plugin::class );
		$method     = $reflection->getMethod( 'load_plugin_textdomain' );
		$method->setAccessible( true );
		$method->invoke( $this->plugin );

		unset( $GLOBALS['l10n']['fever-code-challenge'] );
		$this->addToAssertionCount( 1 );
	}

	public function testLoadPluginTextdomainWithoutExistingDomain(): void {
		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with(
				'fever-code-challenge',
				false,
				'fever-code-challenge/../languages/'
			);

		$reflection = new \ReflectionClass( Plugin::class );
		$method     = $reflection->getMethod( 'load_plugin_textdomain' );
		$method->setAccessible( true );
		$method->invoke( $this->plugin );
		$this->addToAssertionCount( 1 );
	}

	public function testGetPath(): void {
		$expected = $this->plugin_dir . '/subfolder/file.php';

		// Test with leading slash
		$this->assertEquals( $expected, $this->plugin->get_path( '/subfolder/file.php' ) );

		// Test without leading slash
		$this->assertEquals( $expected, $this->plugin->get_path( 'subfolder/file.php' ) );

		// Test empty path
		$this->assertEquals( $this->plugin_dir . '/', $this->plugin->get_path() );
	}

	public function testGetApi(): void {
		$this->assertSame( $this->api, $this->plugin->get_api() );
	}

	public function testRegisterHooks(): void {
		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with(
				'fever-code-challenge',
				false,
				'fever-code-challenge/../languages/'
			);

		Functions\expect( 'add_action' )
			->once()
			->with( 'init', array( $this->plugin, 'register_pokemon_post_type' ) );

		$reflection = new \ReflectionClass( Plugin::class );
		$method     = $reflection->getMethod( 'register_hooks' );
		$method->setAccessible( true );
		$method->invoke( $this->plugin );
		$this->addToAssertionCount( 1 );
	}
}
