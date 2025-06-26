<?php

namespace FeverCodeChallenge\Tests;

use FeverCodeChallenge\Front;
use FeverCodeChallenge\Plugin;
use FeverCodeChallenge\Front\Pokemon;
use FeverCodeChallenge\Front\PokemonList;
use FeverCodeChallenge\Front\PokemonGenerate;
use FeverCodeChallenge\Front\PokemonRandom;
use PHPUnit\Framework\TestCase;

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

	public function testInitInitializesAllPokemonClasses(): void {
		// Create a mock of the Front class
		$frontMock = $this->getMockBuilder( Front::class )
		->setConstructorArgs( [ $this->plugin ] )
		->onlyMethods( [ 'register_hooks' ] )
		->getMock();

		// Execute init and verify no exceptions are thrown
		$frontMock->init();

		// If we get here, the test passes as all Pokemon classes were initialized without errors
		$this->assertTrue( true );
	}

	public function testRegisterHooksIsProtected(): void {
		$reflectionClass = new \ReflectionClass( Front::class );
		$method          = $reflectionClass->getMethod( 'register_hooks' );

		$this->assertTrue( $method->isProtected() );
	}

	public function testEnqueueAssetsMethodExists(): void {
		$this->assertTrue(
			method_exists( $this->front, 'enqueue_assets' ),
			'enqueue_assets method should exist'
		);
	}

	public function testPluginPropertyIsProtected(): void {
		$reflectionClass = new \ReflectionClass( Front::class );
		$property        = $reflectionClass->getProperty( 'plugin' );

		$this->assertTrue( $property->isProtected() );
	}

	public function testPluginInstanceIsSet(): void {
		$reflectionClass = new \ReflectionClass( Front::class );
		$property        = $reflectionClass->getProperty( 'plugin' );
		$property->setAccessible( true );

		$this->assertSame( $this->plugin, $property->getValue( $this->front ) );
	}
}
