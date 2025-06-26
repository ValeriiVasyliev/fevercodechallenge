<?php

namespace FeverCodeChallenge\Tests;

use FeverCodeChallenge\Plugin;
use FeverCodeChallenge\REST;
use PHPUnit\Framework\TestCase;

class RESTTest extends TestCase {

	private REST $rest;
	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin = $this->createMock( Plugin::class );
		$this->rest   = new REST( $this->plugin );
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( REST::class, $this->rest );
	}

	public function testNamespaceConstant(): void {
		$this->assertEquals( 'fever-code-challenge/v1', REST::REST_NAMESPACE );
	}

	public function testInit(): void {
		// Verify that init adds the rest_api_init hook
		$this->rest->init();
		$this->assertTrue(
			has_action( 'rest_api_init', [ $this->rest, 'register_rest_routes' ] ) !== false
		);
	}
}
