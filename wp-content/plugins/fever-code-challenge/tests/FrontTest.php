<?php

namespace FeverCodeChallenge\Tests;

use FeverCodeChallenge\Front;
use FeverCodeChallenge\Plugin;
use FeverCodeChallenge\Front\Pokemon;
use FeverCodeChallenge\Front\PokemonList;
use FeverCodeChallenge\Front\PokemonGenerate;
use FeverCodeChallenge\Front\PokemonRandom;
use Brain\Monkey\Functions;

class FrontTest extends TestCase {

	private Front $front;
	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->plugin = $this->createMock( Plugin::class );
		$this->front  = new Front( $this->plugin );
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( Front::class, $this->front );
	}

	public function testInitRegistersHook(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'wp_enqueue_scripts', array( $this->front, 'enqueue_assets' ) );

		$this->front->init();

		$this->assertTrue( true );
	}

	public function testEnqueueAssets(): void {
		$this->plugin->method( 'plugin_url' )->willReturn( 'http://example.com/plugins/fever' );
		$this->plugin->method( 'plugin_dir' )->willReturn( '/path/to/plugin' );

		// Mock file_exists to return true and filemtime to return a consistent value
		Functions\when( 'file_exists' )->alias(
			function ( $path ) {
				return true;
			}
		);
		Functions\when( 'filemtime' )->justReturn( '123456' );

		// Expect wp_enqueue_style to be called with correct arguments
		Functions\expect( 'wp_enqueue_style' )
			->once()
			->with(
				'fever-code-challenge-style',
				'http://example.com/plugins/fever/assets/css/style.css',
				array(),
				'123456'
			);

		$this->front->enqueue_assets();

		// Assertion to avoid risky test
		$this->assertTrue( true );
	}

	public function testRegisterHooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'wp_enqueue_scripts', array( $this->front, 'enqueue_assets' ) );

		$reflection = new \ReflectionClass( Front::class );
		$method     = $reflection->getMethod( 'register_hooks' );
		$method->setAccessible( true );
		$method->invoke( $this->front );

		// Assertion to avoid risky test
		$this->assertTrue( true );
	}
}
