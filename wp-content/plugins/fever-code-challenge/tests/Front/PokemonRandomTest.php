<?php

namespace FeverCodeChallenge\Tests\Front;

use FeverCodeChallenge\Tests\TestCase;
use FeverCodeChallenge\Front\PokemonRandom;
use FeverCodeChallenge\Plugin;
use Brain\Monkey\Functions;
use org\bovigo\vfs\vfsStream;
use Patchwork;

class PokemonRandomTest extends TestCase {

	private PokemonRandom $pokemon_random;
	private Plugin $plugin;
	private $vfsRoot;
	private string $blankTemplatePath;

	protected function setUp(): void {
		parent::setUp();

		// Setup vfsStream for all templates, including custom
		$this->vfsRoot = vfsStream::setup(
			'plugin-dir',
			null,
			array(
				'templates'                 => array(
					'random-pokemon.php' => '', // fallback template
				),
				'custom-random-pokemon.php' => '', // custom template for test
			)
		);
		$pluginDir     = $this->vfsRoot->url();

		$this->plugin = $this->createMock( Plugin::class );
		$this->plugin->method( 'plugin_url' )->willReturn( 'http://example.com/plugins/fever' );
		$this->plugin->method( 'plugin_dir' )->willReturn( $pluginDir );

		$this->pokemon_random = new PokemonRandom( $this->plugin );

		$this->blankTemplatePath = $pluginDir . '/custom-random-pokemon.php';
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( PokemonRandom::class, $this->pokemon_random );
	}

	public function testInit(): void {
		Functions\expect( 'add_filter' )
			->once()
			->with( 'query_vars', array( $this->pokemon_random, 'add_query_var' ) );
		Functions\expect( 'add_action' )
			->once()
			->with( 'template_redirect', array( $this->pokemon_random, 'maybe_redirect_random_pokemon' ) );

		$this->pokemon_random->init();

		$this->assertTrue( true ); // To avoid risky test
	}

	public function testAddQueryVar(): void {
		$initial_vars = array( 'existing_var' );
		$result       = $this->pokemon_random->add_query_var( $initial_vars );

		$this->assertIsArray( $result );
		$this->assertContains( 'random_pokemon', $result );
		$this->assertContains( 'existing_var', $result );
		$this->assertCount( count( $initial_vars ) + 1, $result );
	}

	public function testMaybeRedirectRandomPokemonWhenQueryVarNotSet(): void {
		Functions\when( 'get_query_var' )->justReturn( false );

		Functions\expect( 'wp_register_script' )->never();
		Functions\expect( 'wp_localize_script' )->never();
		Functions\expect( 'wp_enqueue_script' )->never();
		Functions\expect( 'locate_template' )->never();

		$this->expectOutputString( '' );
		$this->pokemon_random->maybe_redirect_random_pokemon();
	}

	public function testMaybeRedirectRandomPokemonWhenQueryVarSetUsesDefaultTemplate(): void {
		\Patchwork\replace(
			'exit',
			function () {
				throw new \RuntimeException( 'Intercepted exit' );
			}
		);

		Functions\when( 'get_query_var' )->justReturn( true );

		$this->plugin->method( 'plugin_url' )->willReturn( 'http://example.com/plugins/fever' );
		$this->plugin->method( 'plugin_dir' )->willReturn( $this->vfsRoot->url() );

		Functions\when( 'filemtime' )->justReturn( 123456 );
		Functions\when( 'rest_url' )->justReturn( 'http://example.com/wp-json/fever-code-challenge/v1/pokemon' );

		Functions\expect( 'wp_register_script' )
			->once()
			->with(
				'fever_code_challenge-front-pokemon-random',
				'http://example.com/plugins/fever/dist/front-pokemon-random.js',
				array( 'wp-i18n' ),
				123456,
				true
			);

		Functions\expect( 'wp_localize_script' )
			->once()
			->with(
				'fever_code_challenge-front-pokemon-random',
				'feverCodeChallengeFrontPokemonRandom',
				array( 'rest_url' => 'http://example.com/wp-json/fever-code-challenge/v1/pokemon' )
			);
		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with( 'fever_code_challenge-front-pokemon-random' );

		// Default: no custom template found, use fallback
		Functions\expect( 'locate_template' )
			->once()
			->with( 'fever-code-challenge/random-pokemon.php' )
			->andReturn( '' );

		$this->expectOutputString( '' );
		try {
			$this->pokemon_random->maybe_redirect_random_pokemon();
			$this->fail( 'Expected exit/die to terminate script' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Intercepted exit', $e->getMessage() );
		}
	}

	public function testMaybeRedirectRandomPokemonWithCustomTemplate(): void {
		\Patchwork\replace(
			'exit',
			function () {
				throw new \RuntimeException( 'Intercepted exit' );
			}
		);

		Functions\when( 'get_query_var' )->justReturn( true );

		$this->plugin->method( 'plugin_url' )->willReturn( 'http://example.com/plugins/fever' );
		$this->plugin->method( 'plugin_dir' )->willReturn( $this->vfsRoot->url() );

		Functions\when( 'filemtime' )->justReturn( 123456 );
		Functions\when( 'rest_url' )->justReturn( 'http://example.com/wp-json/fever-code-challenge/v1/pokemon' );

		Functions\expect( 'wp_register_script' )
			->once()
			->with(
				'fever_code_challenge-front-pokemon-random',
				'http://example.com/plugins/fever/dist/front-pokemon-random.js',
				array( 'wp-i18n' ),
				123456,
				true
			);

		Functions\expect( 'wp_localize_script' )
			->once()
			->with(
				'fever_code_challenge-front-pokemon-random',
				'feverCodeChallengeFrontPokemonRandom',
				array( 'rest_url' => 'http://example.com/wp-json/fever-code-challenge/v1/pokemon' )
			);
		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with( 'fever_code_challenge-front-pokemon-random' );

		Functions\expect( 'locate_template' )
			->once()
			->with( 'fever-code-challenge/random-pokemon.php' )
			->andReturn( $this->blankTemplatePath );

		// No expectations for wp_register_script/wp_localize_script/wp_enqueue_script
		// because the code should exit before they're called

		$this->expectOutputString( '' );
		try {
			$this->pokemon_random->maybe_redirect_random_pokemon();
			$this->fail( 'Expected exit/die to terminate script' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Intercepted exit', $e->getMessage() );
		}
	}
}
