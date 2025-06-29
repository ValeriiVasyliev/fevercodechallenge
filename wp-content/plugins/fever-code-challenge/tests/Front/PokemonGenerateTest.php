<?php

namespace FeverCodeChallenge\Tests\Front;

use FeverCodeChallenge\Front\PokemonGenerate;
use FeverCodeChallenge\Plugin;
use FeverCodeChallenge\Tests\TestCase;
use Brain\Monkey\Functions;
use org\bovigo\vfs\vfsStream;
use Patchwork;

class PokemonGenerateTest extends TestCase {
	// ... (fields, setUp, unchanged) ...

	protected function setUp(): void {
		parent::setUp();
		$this->vfsRoot = vfsStream::setup(
			'plugin-dir',
			null,
			array(
				'templates'                   => array(
					'generate-pokemon.php' => '',
				),
				'custom-generate-pokemon.php' => '',
			)
		);
		$pluginDir     = $this->vfsRoot->url();

		$this->plugin = $this->createMock( Plugin::class );
		$this->plugin->method( 'plugin_url' )->willReturn( 'http://example.com/plugins/fever' );
		$this->plugin->method( 'plugin_dir' )->willReturn( $pluginDir );

		$this->pokemonGenerate = new PokemonGenerate( $this->plugin );

		Functions\when( 'wp_create_nonce' )->justReturn( 'test_nonce' );
		Functions\when( 'admin_url' )->justReturn( 'http://example.com/wp-admin/admin-ajax.php' );
		Functions\when( 'filemtime' )->justReturn( 123456 );
		$this->blankTemplatePath = $pluginDir . '/custom-generate-pokemon.php';
	}

	public function testMaybeGeneratePokemonUsesDefaultTemplateIfNotFound(): void {
		\Patchwork\replace(
			'exit',
			function () {
				throw new \RuntimeException( 'Intercepted exit' );
			}
		);

		Functions\when( 'get_query_var' )->justReturn( true );
		Functions\when( 'locate_template' )->justReturn( '' );
		Functions\when( 'filemtime' )->justReturn( 123456 );

		Functions\expect( 'wp_register_script' )->once();
		Functions\expect( 'wp_localize_script' )->once();
		Functions\expect( 'wp_enqueue_script' )->once();

		$this->expectOutputString( '' );
		try {
			$this->pokemonGenerate->maybe_generate_pokemon();
			$this->fail( 'Expected exit/die to terminate script' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Intercepted exit', $e->getMessage() );
		}
	}

	public function testMaybeGeneratePokemonUsesCustomThemeTemplate(): void {
		\Patchwork\replace(
			'exit',
			function () {
				throw new \RuntimeException( 'Intercepted exit' );
			}
		);

		Functions\when( 'get_query_var' )->justReturn( true );
		Functions\when( 'locate_template' )->justReturn( $this->blankTemplatePath );

		Functions\expect( 'wp_register_script' )->once();
		Functions\expect( 'wp_localize_script' )->once();
		Functions\expect( 'wp_enqueue_script' )->once();

		$this->expectOutputString( '' );
		try {
			$this->pokemonGenerate->maybe_generate_pokemon();
			$this->fail( 'Expected exit/die to terminate script' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Intercepted exit', $e->getMessage() );
		}
	}

	public function testScriptLocalizationData(): void {
		\Patchwork\replace(
			'exit',
			function () {
				throw new \RuntimeException( 'Intercepted exit' );
			}
		);

		Functions\when( 'get_query_var' )->justReturn( true );
		Functions\when( 'locate_template' )->justReturn( '' );

		$capturedLocalization = array();
		Functions\expect( 'wp_localize_script' )
			->once()
			->with(
				'fever_code_challenge-front-pokemon-generate',
				'feverCodeChallengeFrontPokemonGenerate',
				$this->callback(
					function ( $data ) use ( &$capturedLocalization ) {
						$capturedLocalization = $data;
						return is_array( $data ) && isset( $data['ajax_url'] ) && isset( $data['nonce'] );
					}
				)
			);

		Functions\expect( 'wp_register_script' )->once();
		Functions\expect( 'wp_enqueue_script' )->once();

		$this->expectOutputString( '' );
		try {
			$this->pokemonGenerate->maybe_generate_pokemon();
			$this->fail( 'Expected exit/die to terminate script' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Intercepted exit', $e->getMessage() );
		}

		$this->assertArrayHasKey( 'ajax_url', $capturedLocalization );
		$this->assertArrayHasKey( 'nonce', $capturedLocalization );
		$this->assertEquals( 'http://example.com/wp-admin/admin-ajax.php', $capturedLocalization['ajax_url'] );
		$this->assertEquals( 'test_nonce', $capturedLocalization['nonce'] );
	}
}
