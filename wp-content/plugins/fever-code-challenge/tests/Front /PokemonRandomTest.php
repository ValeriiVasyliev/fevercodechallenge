<?php

namespace FeverCodeChallenge\Tests\Front;

use FeverCodeChallenge\Front\PokemonRandom;
use FeverCodeChallenge\Plugin;
use PHPUnit\Framework\TestCase;

class PokemonRandomTest extends TestCase {

	private PokemonRandom $pokemonRandom;
	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin        = $this->createMock( Plugin::class );
		$this->pokemonRandom = new PokemonRandom( $this->plugin );
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( PokemonRandom::class, $this->pokemonRandom );
	}

	public function testInit(): void {
		$this->pokemonRandom->init();

		// Verify that init registers the necessary hooks
		$this->assertTrue( has_action( 'init' ) !== false );
		$this->assertTrue( has_filter( 'query_vars' ) !== false );
		$this->assertTrue( has_action( 'template_redirect' ) !== false );
	}

	public function testQueryVarsFilter(): void {
		$filter = $this->getQueryVarsFilter();
		$vars   = $filter( [ 'existing_var' ] );

		$this->assertContains( 'random_pokemon', $vars );
		$this->assertContains( 'existing_var', $vars );
	}

	private function getQueryVarsFilter(): callable {
		$filters = $GLOBALS['wp_filter']['query_vars']->callbacks[10] ?? [];
		foreach ( $filters as $filter ) {
			return $filter['function'];
		}
		return function( $vars ) {
			return $vars;
		};
	}
}
