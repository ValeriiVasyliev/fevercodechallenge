<?php

namespace FeverCodeChallenge\Tests\Front;

use Brain\Monkey;
use Brain\Monkey\Functions;
use FeverCodeChallenge\Front\PokemonList;
use FeverCodeChallenge\Plugin;
use FeverCodeChallenge\Tests\TestCase;
use org\bovigo\vfs\vfsStream;
use Patchwork;

class PokemonListTest extends TestCase {
	private PokemonList $pokemon_list;
	private Plugin $plugin;
	private $vfsRoot;
	private string $blankTemplatePath;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Set up virtual filesystem with vfsStream for template inclusion
		$this->vfsRoot = vfsStream::setup(
			'plugin-dir',
			null,
			array(
				'templates'               => array(
					'pokemon-list.php' => '', // blank fallback template
				),
				'custom-pokemon-list.php' => '', // for custom template test
			)
		);
		$pluginDir     = $this->vfsRoot->url();

		$this->plugin = $this->createMock( Plugin::class );
		$this->plugin->method( 'plugin_url' )->willReturn( 'http://example.com/plugin' );
		$this->plugin->method( 'plugin_dir' )->willReturn( $pluginDir );

		$this->pokemon_list = new PokemonList( $this->plugin );

		$this->blankTemplatePath = $pluginDir . '/custom-pokemon-list.php';

		// Only stub globally used functions here (not the ones you expect in tests)
		Functions\when( 'rest_url' )->justReturn( 'http://example.com/wp-json/' );
		Functions\when( 'locate_template' )->justReturn( '' );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testConstruction(): void {
		// Only stub get_query_var here if needed (not expected in this test)
		Functions\when( 'get_query_var' )->justReturn( false );
		$this->assertInstanceOf( PokemonList::class, $this->pokemon_list );
	}

	public function testInit(): void {
		Functions\expect( 'add_filter' )
			->once()
			->with( 'query_vars', array( $this->pokemon_list, 'add_query_var' ) );

		Functions\expect( 'add_action' )
			->once()
			->with( 'template_redirect', array( $this->pokemon_list, 'maybe_display_pokemon_list_page' ) );

		$this->pokemon_list->init();

		$this->assertTrue( true ); // To avoid risky test warning
	}

	public function testAddQueryVar(): void {
		$initial_vars = array( 'existing_var' );
		$result       = $this->pokemon_list->add_query_var( $initial_vars );

		$this->assertContains( 'pokemon_list', $result );
		$this->assertCount( count( $initial_vars ) + 1, $result );
	}

	public function testMaybeDisplayPokemonListPageWhenNotPokemonList(): void {
		// Expect get_query_var to be called once and return false
		Functions\expect( 'get_query_var' )
			->once()
			->with( 'pokemon_list' )
			->andReturn( false );

		$this->pokemon_list->maybe_display_pokemon_list_page();
		$this->assertTrue( true );
	}

	public function testMaybeDisplayPokemonListPageWhenIsPokemonListDefaultTemplate(): void {
		\Patchwork\replace(
			'exit',
			function () {
				throw new \RuntimeException( 'Intercepted exit' );
			}
		);

		global $wpdb;
		$wpdb = $this->createMock( \wpdb::class );

		Functions\expect( 'get_query_var' )
			->once()
			->with( 'pokemon_list' )
			->andReturn( true );

		$this->plugin->expects( $this->once() )
			->method( 'plugin_url' )
			->willReturn( 'http://example.com/plugin' );

		$this->plugin->expects( $this->exactly( 2 ) )
			->method( 'plugin_dir' )
			->willReturn( $this->vfsRoot->url() );

		Functions\expect( 'filemtime' )
			->once()
			->andReturn( 123456 );

		Functions\expect( 'wp_register_script' )
			->once()
			->with(
				'fever-pokemon-list',
				'http://example.com/plugin/dist/front-pokemon-list.js',
				array( 'wp-i18n' ),
				123456,
				1
			);

		Functions\when( 'rest_url' )->justReturn( 'http://example.com/wp-json/fever-code-challenge/v1/pokemon' );

		Functions\expect( 'wp_localize_script' )
			->once()
			->with(
				'fever-pokemon-list',
				'feverPokemonList',
				array(
					'rest_url' => 'http://example.com/wp-json/fever-code-challenge/v1/pokemon',
					'per_page' => 6,
				)
			);

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with( 'fever-pokemon-list' );

		// No custom template found, use fallback
		Functions\when( 'locate_template' )->justReturn( '' );

		// Mock wp_cache functions
		Functions\expect( 'wp_cache_get' )
			->once()
			->with( 'fever_unique_pokemon_types', 'fever-code-challenge' )
			->andReturn( false );

		$wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SQL query' );
		$wpdb->expects( $this->once() )
			->method( 'get_col' )
			->willReturn( array( 'fire', 'water', 'grass' ) );

		Functions\expect( 'wp_cache_set' )
			->once()
			->with(
				'fever_unique_pokemon_types',
				array( 'fire', 'water', 'grass' ),
				'fever-code-challenge',
				12 * HOUR_IN_SECONDS
			);

		$this->expectOutputString( '' );
		try {
			$this->pokemon_list->maybe_display_pokemon_list_page();
			$this->fail( 'Expected exit/die to terminate script' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Intercepted exit', $e->getMessage() );
		}
	}

	public function testMaybeDisplayPokemonListPageWhenIsPokemonListCustomTemplate(): void {
		\Patchwork\replace(
			'exit',
			function () {
				throw new \RuntimeException( 'Intercepted exit' );
			}
		);

		global $wpdb;
		$wpdb = $this->createMock( \wpdb::class );

		Functions\expect( 'get_query_var' )
			->once()
			->with( 'pokemon_list' )
			->andReturn( true );

		$this->plugin->method( 'plugin_url' )->willReturn( 'http://example.com/plugins/fever' );
		$this->plugin->method( 'plugin_dir' )->willReturn( '/path/to/plugin' );

		Functions\when( 'filemtime' )->justReturn( 123456 );
		Functions\when( 'rest_url' )->justReturn( 'http://example.com/wp-json/fever-code-challenge/v1/pokemon' );

		Functions\expect( 'wp_register_script' )->once();
		Functions\expect( 'wp_localize_script' )->once();
		Functions\expect( 'wp_enqueue_script' )->once();

		// Return a blank template path for custom template
		Functions\when( 'locate_template' )->justReturn( $this->blankTemplatePath );

		// Mock wp_cache and db for unique types
		Functions\expect( 'wp_cache_get' )
			->once()
			->with( 'fever_unique_pokemon_types', 'fever-code-challenge' )
			->andReturn( false );

		$wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SQL query' );
		$wpdb->expects( $this->once() )
			->method( 'get_col' )
			->willReturn( array( 'fire', 'water', 'grass' ) );

		Functions\expect( 'wp_cache_set' )
			->once()
			->with(
				'fever_unique_pokemon_types',
				array( 'fire', 'water', 'grass' ),
				'fever-code-challenge',
				12 * HOUR_IN_SECONDS
			);

		// No expectations for script registration or cache
		$this->expectOutputString( '' );
		try {
			$this->pokemon_list->maybe_display_pokemon_list_page();
			$this->fail( 'Expected exit/die to terminate script' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Intercepted exit', $e->getMessage() );
		}
	}

	public function testGetUniqueTypeMetaValuesWithCache(): void {
		$cached_types = array( 'fire', 'water', 'grass' );

		Functions\expect( 'wp_cache_get' )
			->once()
			->with( 'fever_unique_pokemon_types', 'fever-code-challenge' )
			->andReturn( $cached_types );

		$reflection = new \ReflectionClass( PokemonList::class );
		$method     = $reflection->getMethod( 'get_unique_type_meta_values' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->pokemon_list );
		$this->assertEquals( $cached_types, $result );
	}
}
