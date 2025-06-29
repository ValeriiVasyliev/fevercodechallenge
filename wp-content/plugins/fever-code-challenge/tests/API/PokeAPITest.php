<?php

namespace FeverCodeChallenge\Tests\API;

use FeverCodeChallenge\API\PokeAPI;
use FeverCodeChallenge\Tests\TestCase;
use Brain\Monkey\Functions;
use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

class PokeAPITest extends TestCase {

	private PokeAPI $api;

	protected function setUp(): void {
		parent::setUp();

		// Set up WordPress function mocks before initializing the API
		Functions\when( 'add_query_arg' )->alias(
			function ( $args, $url ) {
				if ( is_string( $args ) ) {
					parse_str( $args, $parsed_args );
					$args = $parsed_args;
				}
				return $url . '?' . http_build_query( $args );
			}
		);

		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			function ( $response ) {
				return $response['body'] ?? '';
			}
		);
		Functions\when( 'is_wp_error' )->alias(
			function ( $thing ) {
				return $thing instanceof \WP_Error;
			}
		);
		Functions\when( 'wp_remote_get' )->justReturn(
			array(
				'body'     => '{}',
				'response' => array( 'code' => 200 ),
			)
		);

		$this->api = new PokeAPI();
	}

	public function testGetListWithDefaultParameters(): void {
		$mockResponse = array(
			'results' => array(
				array(
					'name' => 'bulbasaur',
					'url'  => 'https://pokeapi.co/api/v2/pokemon/1/',
				),
				array(
					'name' => 'ivysaur',
					'url'  => 'https://pokeapi.co/api/v2/pokemon/2/',
				),
			),
		);

		Functions\when( 'wp_remote_get' )->justReturn(
			array(
				'body'     => json_encode( $mockResponse ),
				'response' => array( 'code' => 200 ),
			)
		);

		$result = $this->api->get_list();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertCount( 2, $result['results'] );
	}

	public function testGetPokemonData(): void {
		$mockDetails = array(
			'name'    => 'pikachu',
			'weight'  => 60,
			'sprites' => array(
				'other' => array(
					'official-artwork' => array(
						'front_default' => 'https://example.com/pikachu.png',
					),
				),
			),
			'types'   => array(
				array( 'type' => array( 'name' => 'electric' ) ),
			),
		);

		$mockSpecies = array(
			'flavor_text_entries' => array(
				array(
					'flavor_text' => 'Test description',
					'language'    => array( 'name' => 'en' ),
				),
			),
			'pokedex_numbers'     => array(
				array(
					'entry_number' => 25,
					'pokedex'      => array( 'name' => 'national' ),
				),
			),
		);

		$callCount = 0;
		Functions\when( 'wp_remote_get' )->alias(
			function () use ( &$callCount, $mockDetails, $mockSpecies ) {
				$callCount++;
				return array(
					'body'     => json_encode( $callCount === 1 ? $mockDetails : $mockSpecies ),
					'response' => array( 'code' => 200 ),
				);
			}
		);

		$result = $this->api->get_pokemon_data( 'pikachu' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'pikachu', $result['name'] );
		$this->assertEquals( 60, $result['weight'] );
		$this->assertEquals( 'electric', $result['primary_type'] );
		$this->assertEquals( 'Test description', $result['description'] );
		$this->assertEquals( 25, $result['oldest_dex'] );
	}

	public function testSanitizeData(): void {
		$unsanitizedData = array(
			'name'     => '<script>alert("xss")</script>',
			'types'    => array(
				array( 'name' => '<b>fire</b>' ),
				array( 'name' => '"water"' ),
			),
			'number'   => 25,
			'isActive' => true,
		);

		$sanitizedData = $this->api->sanitize_data( $unsanitizedData );

		$this->assertEquals( '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $sanitizedData['name'] );
		$this->assertEquals( '&lt;b&gt;fire&lt;/b&gt;', $sanitizedData['types'][0]['name'] );
		$this->assertEquals( '&quot;water&quot;', $sanitizedData['types'][1]['name'] );
		$this->assertEquals( 25, $sanitizedData['number'] );
		$this->assertTrue( $sanitizedData['isActive'] );
	}

	public function testGetListWithCaching(): void {
		$cachedData = array(
			'results' => array(
				array(
					'name' => 'cached_pokemon',
					'url'  => 'https://pokeapi.co/api/v2/pokemon/1/',
				),
			),
		);

		Functions\when( 'get_transient' )->justReturn( $cachedData );

		$result = $this->api->get_list();

		$this->assertIsArray( $result );
		$this->assertEquals( $cachedData, $result );
	}

	public function testGetListWithFailedRequest(): void {
		Functions\when( 'wp_remote_get' )->justReturn( new \WP_Error( 'error', 'Failed request' ) );
		Functions\when( 'is_wp_error' )->justReturn( true );

		$result = $this->api->get_list();

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function testGetPokemonDataWithInvalidResponse(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'wp_remote_get' )->justReturn(
			array(
				'body'     => 'invalid json',
				'response' => array( 'code' => 200 ),
			)
		);

		$result = $this->api->get_pokemon_data( 'invalid-pokemon' );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}
}
