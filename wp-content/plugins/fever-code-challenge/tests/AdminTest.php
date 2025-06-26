<?php

namespace FeverCodeChallenge\Tests;

use FeverCodeChallenge\Admin;
use FeverCodeChallenge\Plugin;

class AdminTest extends TestCase {

	private Admin $admin;
	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin = $this->createMock( Plugin::class );
		$this->admin  = new Admin( $this->plugin );
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( Admin::class, $this->admin );
	}

	public function testInit(): void {
		$this->admin->init();
		$this->assertTrue( true ); // If we get here without errors, the test passes
	}
}
