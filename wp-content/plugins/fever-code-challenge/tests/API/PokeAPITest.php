<?php

namespace FeverCodeChallenge\Tests\API;

use FeverCodeChallenge\API\PokeAPI;
use FeverCodeChallenge\Plugin;
use PHPUnit\Framework\TestCase;

class PokeAPITest extends TestCase {

	private PokeAPI $pokeAPI;
	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin  = $this->createMock( Plugin::class );
		$this->pokeAPI = new PokeAPI( $this->plugin );
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( PokeAPI::class, $this->pokeAPI );
	}

	public function testImplementsIAPIInterface(): void {
		$this->assertInstanceOf( \FeverCodeChallenge\Interfaces\IAPI::class, $this->pokeAPI );
	}
}
