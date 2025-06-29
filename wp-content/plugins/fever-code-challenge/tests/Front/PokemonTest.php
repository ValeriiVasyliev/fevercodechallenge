<?php

namespace FeverCodeChallenge\Tests\Front;

use FeverCodeChallenge\Front\Pokemon;
use FeverCodeChallenge\Plugin;
use FeverCodeChallenge\Tests\TestCase;
use Brain\Monkey\Functions;

class PokemonTest extends TestCase {
	private Pokemon $pokemon;
	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->plugin  = $this->createMock( Plugin::class );
		$this->pokemon = new Pokemon( $this->plugin );

		// Mock WordPress functions
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'in_the_loop' )->justReturn( true );
		Functions\when( 'is_main_query' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 1 );
		Functions\when( 'is_protected_meta' )->justReturn( false );
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'rest_url' )->justReturn( 'http://example.com/wp-json/' );
		// filemtime is used in enqueue_scripts, needs to be stubbed
		Functions\when( 'filemtime' )->justReturn( 123456 );
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( Pokemon::class, $this->pokemon );
	}

	public function testInit(): void {
		Functions\expect( 'add_filter' )
			->once()
			->with( 'the_content', array( $this->pokemon, 'extend_pokemon_content' ) );

		Functions\expect( 'add_action' )
			->once()
			->with( 'wp_enqueue_scripts', array( $this->pokemon, 'enqueue_scripts' ) );

		$this->pokemon->init();

		// Add assertion to avoid risky test
		$this->assertTrue( true );
	}

	public function testExtendPokemonContentWhenNotSingular(): void {
		Functions\when( 'is_singular' )->justReturn( false );

		$content = 'Original content';
		$result  = $this->pokemon->extend_pokemon_content( $content );

		$this->assertEquals( $content, $result );
	}

	public function testExtendPokemonContentWithValidData(): void {
		$customFields = array(
			'weight'          => array( '100' ),
			'primary_type'    => array( 'electric' ),
			'newest_dex'      => array( '25' ),
			'pokedex_numbers' => array(
				array(
					array(
						'entry_number' => 25,
						'pokedex_name' => 'national',
					),
				),
			),
		);

		Functions\when( 'get_post_custom' )->justReturn( $customFields );
		Functions\when( 'get_post_meta' )->justReturn(
			array(
				array(
					'entry_number' => 25,
					'pokedex_name' => 'national',
				),
			)
		);

		$content = 'Original content';
		$result  = $this->pokemon->extend_pokemon_content( $content );

		// The plugin renders "Weight" as "Weight" not "Weight: 100" (the HTML is: <strong>Weight:</strong> 100)
		$this->assertStringContainsString( '<strong>Weight:</strong> 100', $result );
		$this->assertStringContainsString( '<strong>Primary Type:</strong> electric', $result );
		$this->assertStringContainsString( 'Original content', $result );
		$this->assertStringContainsString( 'pokemon-custom-fields', $result );
		// "Get Oldest Dex Entry" button only appears if newest_dex and pokedex_map is present
		$this->assertStringContainsString( 'Get Oldest Dex Entry', $result );
	}

	public function testEnqueueScriptsWhenNotSingular(): void {
		Functions\when( 'is_singular' )->justReturn( false );

		Functions\expect( 'wp_register_script' )->never();
		Functions\expect( 'wp_localize_script' )->never();
		Functions\expect( 'wp_enqueue_script' )->never();

		$this->pokemon->enqueue_scripts();

		// Add assertion to avoid risky test
		$this->assertTrue( true );
	}

	public function testEnqueueScriptsOnPokemonSingle(): void {
		$this->plugin->method( 'plugin_url' )->willReturn( 'http://example.com/plugins/fever' );
		$this->plugin->method( 'plugin_dir' )->willReturn( '/path/to/plugin' );

		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'is_main_query' )->justReturn( true );
		Functions\when( 'filemtime' )->justReturn( 123456 );
		Functions\when( 'rest_url' )->justReturn( 'http://example.com/wp-json/fever-code-challenge/v1/pokemon' );

		Functions\expect( 'wp_register_script' )
			->once()
			->with(
				'fever_code_challenge-front-pokemon',
				'http://example.com/plugins/fever/dist/front-pokemon.js',
				array( 'wp-i18n' ),
				123456,
				true
			);

		Functions\expect( 'wp_localize_script' )
			->once()
			->with(
				'fever_code_challenge-front-pokemon',
				'feverCodeChallengeFrontPokemon',
				array( 'rest_url' => 'http://example.com/wp-json/fever-code-challenge/v1/pokemon' )
			);

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with( 'fever_code_challenge-front-pokemon' );

		$this->pokemon->enqueue_scripts();

		$this->assertTrue( true );
	}

	public function testProtectedFields(): void {
		Functions\when( 'is_protected_meta' )->justReturn( true );

		$customFields = array(
			'_protected_field' => array( 'secret' ),
		);

		Functions\when( 'get_post_custom' )->justReturn( $customFields );
		Functions\when( 'get_post_meta' )->justReturn( array() );

		$content = 'Original content';
		$result  = $this->pokemon->extend_pokemon_content( $content );

		$this->assertStringNotContainsString( '_protected_field', $result );
		$this->assertStringNotContainsString( 'secret', $result );
	}

	public function testEmptyPokedexNumbers(): void {
		Functions\when( 'get_post_custom' )->justReturn( array() );
		Functions\when( 'get_post_meta' )->justReturn( null );

		$content = 'Original content';
		$result  = $this->pokemon->extend_pokemon_content( $content );

		$this->assertStringContainsString( 'Original content', $result );
		$this->assertStringContainsString( 'pokemon-custom-fields', $result );
		$this->assertStringNotContainsString( 'Get Oldest Dex Entry', $result );
	}

	public function testInvalidPokedexNumbers(): void {
		Functions\when( 'get_post_custom' )->justReturn( array() );
		Functions\when( 'get_post_meta' )->justReturn( 'invalid data' );

		$content = 'Original content';
		$result  = $this->pokemon->extend_pokemon_content( $content );

		$this->assertStringContainsString( 'Original content', $result );
		$this->assertStringContainsString( 'pokemon-custom-fields', $result );
		$this->assertStringNotContainsString( 'Get Oldest Dex Entry', $result );
	}
}
