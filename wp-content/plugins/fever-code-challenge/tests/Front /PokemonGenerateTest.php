<?php

namespace FeverCodeChallenge\Tests\Front;

use FeverCodeChallenge\Front\PokemonGenerate;
use FeverCodeChallenge\Plugin;
use PHPUnit\Framework\TestCase;

class PokemonGenerateTest extends TestCase {

	private PokemonGenerate $pokemonGenerate;
	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin          = $this->createMock( Plugin::class );
		$this->pokemonGenerate = new PokemonGenerate( $this->plugin );
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( PokemonGenerate::class, $this->pokemonGenerate );
	}

	public function testInit(): void {
		$this->pokemonGenerate->init();

		// Verify that init registers the necessary hooks
		$this->assertTrue( has_action( 'init' ) !== false );
		$this->assertTrue( has_filter( 'query_vars' ) !== false );
		$this->assertTrue( has_action( 'template_redirect' ) !== false );
	}

	public function testQueryVarsFilter(): void {
		$filter = $this->getQueryVarsFilter();
		$vars   = $filter( [ 'existing_var' ] );

		$this->assertContains( 'generate_pokemon', $vars );
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
