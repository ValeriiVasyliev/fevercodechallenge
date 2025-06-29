<?php

namespace FeverCodeChallenge\Tests;

use FeverCodeChallenge\REST;
use FeverCodeChallenge\Plugin;
use Brain\Monkey\Functions;
use WP_REST_Request;
use WP_Query;
use WP_Error;
use WP_REST_Response;

class RESTTest extends TestCase {

	private REST $rest;
	private Plugin $plugin;
	private WP_Query $query;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin = $this->createMock( Plugin::class );
		$this->rest   = new REST( $this->plugin );

		// General WP mocks
		Functions\when( 'register_rest_route' )->justReturn( null );
		Functions\when( 'rest_ensure_response' )->alias(
			function ( $arg ) {
				// If already a WP_REST_Response, return as-is
				if ( $arg instanceof WP_REST_Response ) {
					return $arg;
				}
				return new WP_REST_Response( $arg );
			}
		);
		Functions\when( 'wp_reset_postdata' )->justReturn( null );
		Functions\when( 'rest_url' )->justReturn( 'http://example.com/wp-json/' );
		Functions\when( 'get_permalink' )->justReturn( 'http://example.com/pokemon/1' );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( '__' )->returnArg();
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( REST::class, $this->rest );
	}

	public function testNamespaceConstant(): void {
		$this->assertEquals( 'fever-code-challenge/v1', REST::REST_NAMESPACE );
	}

	public function testInit(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'rest_api_init', array( $this->rest, 'register_rest_routes' ) );
		$this->rest->init();
		$this->assertTrue( true );
	}

	public function testRegisterRestRoutes(): void {
		$callCount = 0;
		Functions\when( 'register_rest_route' )->alias(
			function () use ( &$callCount ) {
				$callCount++;
			}
		);
		$this->rest->register_rest_routes();
		$this->assertEquals( 2, $callCount, 'register_rest_route should be called twice' );
	}

	public function testGetPokemonListWithNoResults(): void {
		$request = $this->createMock( WP_REST_Request::class );
		$request->method( 'get_param' )->willReturnMap(
			array(
				array( 'limit', 10 ),
				array( 'page', 1 ),
				array( 'order', 'title' ),
				array( 'pokemon_type', '' ),
			)
		);

		$this->query                = $this->createMock( WP_Query::class );
		$this->query->found_posts   = 0;
		$this->query->max_num_pages = 0;
		$this->query->method( 'have_posts' )->willReturn( false );

		$response = $this->rest->get_pokemon_list( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertEmpty( $data['data'] );
	}

	public function testGetPokemonListWithResults(): void {
		$request = $this->getMockBuilder( WP_REST_Request::class )
			->disableOriginalConstructor()
			->getMock();
		$request->method( 'get_param' )->willReturnMap(
			array(
				array( 'limit', 10 ),
				array( 'page', 1 ),
				array( 'order', 'title' ),
				array( 'pokemon_type', '' ),
			)
		);

		Functions\when( 'get_the_ID' )->justReturn( 1 );
		Functions\when( 'get_the_title' )->justReturn( 'Pikachu' );
		Functions\when( 'get_the_content' )->justReturn( 'Test content' );
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'http://example.com/image.jpg' );

		Functions\when( 'get_post_meta' )->alias(
			function ( $post_id, $key ) {
				return match ( $key ) {
					'newest_dex' => 25,  // int, critical
					'weight' => '6.0',
					'primary_type' => 'electric',
					'secondary_type' => '',
					'oldest_dex' => '25',
					'pokedex_numbers' => array(
						array(
							'entry_number' => 25,
							'pokedex_name' => 'national',
						),
					),
					default => '',
				};
			}
		);

		// Mock the WP_Query object returned by new WP_Query()
		$query_mock = $this->createMock( WP_Query::class );

		$query_mock->found_posts   = 1;
		$query_mock->max_num_pages = 1;

		$query_mock->expects( $this->exactly( 3 ) )
			->method( 'have_posts' )
			->willReturnOnConsecutiveCalls( true, true, false );

		$query_mock->expects( $this->once() )
			->method( 'the_post' )
			->willReturn( true );

		WP_Query::$test_instance = $query_mock;

		$response = $this->rest->get_pokemon_list( $request, $query_mock );

		// Clear the test instance after
		WP_Query::$test_instance = null;

		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertNotEmpty( $data['data'] );  // Now passes because newest_dex is int
		$this->assertEquals( 1, $data['total'] );
		$this->assertEquals( 1, $data['pages'] );
	}


	public function testGetPokemonDataNotFound(): void {
		$request = $this->createMock( WP_REST_Request::class );
		$request->method( 'get_param' )->willReturn( 999 );

		$this->query = $this->createMock( WP_Query::class );
		$this->query->method( 'have_posts' )->willReturn( false );

		$response = $this->rest->get_pokemon_data( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertTrue( method_exists( $response, 'get_error_code' ) );
		$this->assertEquals( 'no_pokemon', $response->get_error_code() );
	}

	public function testGetPokemonDataSuccess(): void {
		$request = $this->createMock( WP_REST_Request::class );
		$request->method( 'get_param' )->willReturn( 25 );

		// Mock the WP_Query object returned by new WP_Query()
		$query_mock                = $this->createMock( WP_Query::class );
		$query_mock->found_posts   = 1;
		$query_mock->max_num_pages = 1;
		$query_mock->expects( $this->once() )
			->method( 'have_posts' )
			->willReturnOnConsecutiveCalls( true, false );
		$query_mock->expects( $this->once() )
			->method( 'the_post' )
			->willReturn( true );

		WP_Query::$test_instance = $query_mock;

		// Override WP_Query constructor to return this mock
		Functions\when( 'WP_Query' )->alias( fn( $args ) => $query_mock );

		// Mock template functions your method calls inside the loop
		Functions\when( 'get_the_ID' )->justReturn( 1 );
		Functions\when( 'get_the_title' )->justReturn( 'Pikachu' );
		Functions\when( 'get_the_content' )->justReturn( 'Test content' );
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'http://example.com/image.jpg' );

		Functions\when( 'get_post_meta' )->alias(
			function ( $post_id, $key, $single = true ) {
				return match ( $key ) {
					'newest_dex' => 25,
					'weight' => '6.0',
					'primary_type' => 'electric',
					'secondary_type' => '',
					'oldest_dex' => '25',
					'pokedex_numbers' => array(
						array(
							'entry_number' => 25,
							'pokedex_name' => 'national',
						),
					),
					default => '',
				};
			}
		);

		// Call the method under test
		$response = $this->rest->get_pokemon_data( $request, $query_mock );

		// Clear the test instance after
		WP_Query::$test_instance = null;

		// Check response type and data
		$this->assertInstanceOf( WP_REST_Response::class, $response );

		$data = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertEquals( 25, $data['id'] );
		$this->assertEquals( 'Pikachu', $data['name'] );
		$this->assertEquals( 'electric', $data['primary_type'] );
	}
}
