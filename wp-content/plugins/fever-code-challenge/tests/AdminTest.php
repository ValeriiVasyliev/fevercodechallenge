<?php

namespace FeverCodeChallenge\Tests;

use FeverCodeChallenge\Admin;
use FeverCodeChallenge\Plugin;
use Brain\Monkey\Functions;

class AdminTest extends TestCase {

	private Admin $admin;
	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->plugin = $this->createMock( Plugin::class );
		$this->admin  = new Admin( $this->plugin );

		// ✅ Common WP mocks
		Functions\when( 'admin_url' )->justReturn( 'http://example.com/wp-admin/' );
		Functions\when( 'wp_create_nonce' )->justReturn( 'test_nonce' );
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'get_the_post_thumbnail' )->justReturn( '' );

		Functions\when( 'wp_send_json_error' )->alias(
			function () {
				throw new \Exception( 'wp_send_json_error called' );
			}
		);
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( Admin::class, $this->admin );
	}

	public function testInit(): void {
		Functions\expect( 'add_action' )->times( 5 )->andReturn( true );
		Functions\expect( 'add_filter' )->times( 2 )->andReturn( true );

		$this->admin->init();

		$this->assertTrue( true ); // Avoid risky test
	}

	public function testAddFormCreatePokemonWithNonPokemonScreen(): void {
		$screen            = $this->createMock( \WP_Screen::class );
		$screen->post_type = 'post';

		Functions\when( 'get_current_screen' )->justReturn( $screen );
		Functions\expect( 'add_action' )->never();

		$this->admin->add_form_create_pokemon();

		$this->assertTrue( true ); // Avoid risky test
	}

	public function testAddFormCreatePokemonWithPokemonScreen(): void {
		$screen            = $this->createMock( \WP_Screen::class );
		$screen->post_type = 'pokemon';

		Functions\when( 'get_current_screen' )->justReturn( $screen );

		Functions\expect( 'add_action' )
			->once()
			->with(
				'all_admin_notices',
				$this->callback(
					function ( $callback ) {
						ob_start();
						$callback();
						$output = ob_get_clean();
						return strpos( $output, 'create-pokemon-ajax' ) !== false;
					}
				)
			);

		$this->admin->add_form_create_pokemon();

		$this->assertTrue( true ); // Avoid risky test
	}

	public function testEnqueueScripts(): void {
		$this->plugin->method( 'plugin_url' )->willReturn( 'http://example.com/plugins/fever' );
		$this->plugin->method( 'plugin_dir' )->willReturn( '/path/to/plugin' );
		Functions\when( 'filemtime' )->justReturn( 123456 );

		Functions\expect( 'wp_register_script' )
			->once()
			->with(
				'fever_code_challenge_admin',
				'http://example.com/plugins/fever/dist/admin.js',
				array( 'wp-i18n' ),
				123456,
				true
			);

		Functions\expect( 'wp_localize_script' )->once();
		Functions\expect( 'wp_enqueue_script' )->once();

		$this->admin->enqueue_scripts();

		$this->assertTrue( true ); // Avoid risky test
	}

	public function testHandleCreateNewPokemonWithoutPermission(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$_POST['types'] = array( 'fire', 'water' );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'wp_send_json_error called' );

		$this->admin->handle_create_new_pokemon();

		unset( $_POST['types'] );
	}

	public function testHandleCreateNewPokemonWithInvalidNonce(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_verify_nonce' )->justReturn( false );

		$_POST['types'] = array( 'electric', 'grass' );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'wp_send_json_error called' );

		$this->admin->handle_create_new_pokemon();

		unset( $_POST['types'] );
	}

	public function testFeverAddFeaturedImageColumn(): void {
		$columns = array(
			'cb'    => '<input type="checkbox" />',
			'title' => 'Title',
			'date'  => 'Date',
		);

		$expected = array(
			'cb'             => '<input type="checkbox" />',
			'featured_image' => 'Featured Image',
			'title'          => 'Title',
			'date'           => 'Date',
		);

		$result = $this->admin->fever_add_featured_image_column( $columns );
		$this->assertEquals( $expected, $result );
	}

	public function testFeverShowFeaturedImageColumnWithoutImage(): void {
		Functions\when( 'get_the_post_thumbnail' )->justReturn( '' );

		ob_start();
		$this->admin->fever_show_featured_image_column( 'featured_image', 1 );
		$output = ob_get_clean();

		$this->assertEquals( 'No Image', $output );
	}

	public function testFeverShowFeaturedImageColumnWithImage(): void {
		Functions\when( 'get_the_post_thumbnail' )->justReturn( '<img src="test.jpg" />' );

		ob_start();
		$this->admin->fever_show_featured_image_column( 'featured_image', 1 );
		$output = ob_get_clean();

		$this->assertEquals( '<img src="test.jpg" />', $output );
	}

	public function testFeverAddThumbnailClass(): void {
		$html     = '<img src="test.jpg" />';
		$expected = '<img class="pokemon-thumbnail" src="test.jpg" />';

		$result = $this->admin->fever_add_thumbnail_class( $html, 1, 1, 'thumbnail', array() );
		$this->assertEquals( $expected, $result );

		$result = $this->admin->fever_add_thumbnail_class( $html, 1, 1, 'full', array() );
		$this->assertEquals( $html, $result );
	}

	public function testFeverAddAdminColumnStyles(): void {
		ob_start();
		$this->admin->fever_add_admin_column_styles();
		$output = ob_get_clean();

		$this->assertStringContainsString( '.column-featured_image', $output );
		$this->assertStringContainsString( '.pokemon-thumbnail', $output );
	}
}
