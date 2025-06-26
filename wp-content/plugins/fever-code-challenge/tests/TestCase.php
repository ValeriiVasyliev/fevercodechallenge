<?php

namespace FeverCodeChallenge\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setUpWPFunctions();
	}

	protected function setUpWPFunctions(): void {
	}
}
