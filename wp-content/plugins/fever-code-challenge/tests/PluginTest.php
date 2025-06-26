<?php

namespace FeverCodeChallenge\Tests;

use FeverCodeChallenge\Plugin;
use FeverCodeChallenge\Admin;
use FeverCodeChallenge\Front;
use FeverCodeChallenge\REST;
use FeverCodeChallenge\Interfaces\IAPI;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class PluginTest extends TestCase
{
    private Plugin $plugin;
    private $api;
    private string $plugin_file_path;
    private string $plugin_url = 'http://example.com/wp-content/plugins/fever-code-challenge';
    private string $plugin_dir = '/path/to/plugin';

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Mock WordPress functions
        Functions\when('plugin_dir_url')->justReturn($this->plugin_url . '/');
        Functions\when('plugin_basename')->returnArg();
        Functions\when('add_action')->justReturn(true);
        Functions\when('load_plugin_textdomain')->justReturn(true);
        Functions\when('register_post_type')->justReturn(true);
        Functions\when('__')->returnArg();

        // Mock API interface
        $this->api = $this->createMock(IAPI::class);
        $this->plugin_file_path = $this->plugin_dir . '/plugin.php';

        // Create plugin instance using a mock that doesn't require dirname()
        $this->plugin = $this->getMockBuilder(Plugin::class)
            ->setConstructorArgs([$this->api, $this->plugin_file_path])
            ->onlyMethods(['getPluginDir'])
            ->getMock();

        $this->plugin->method('getPluginDir')
            ->willReturn($this->plugin_dir);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testConstruction(): void
    {
        $this->assertInstanceOf(Plugin::class, $this->plugin);
    }

    public function testInit(): void
    {
        // Create mocks for the components
        $adminMock = $this->createMock(Admin::class);
        $frontMock = $this->createMock(Front::class);
        $restMock = $this->createMock(REST::class);

        // Create a plugin mock that returns our component mocks
        $pluginMock = $this->getMockBuilder(Plugin::class)
            ->setConstructorArgs([$this->api, $this->plugin_file_path])
            ->onlyMethods(['getPluginDir', 'createComponents'])
            ->getMock();

        $pluginMock->method('getPluginDir')->willReturn($this->plugin_dir);
        $pluginMock->method('createComponents')->willReturn([
            $adminMock,
            $frontMock,
            $restMock
        ]);

        // Set expectations for init calls
        $adminMock->expects($this->once())->method('init');
        $frontMock->expects($this->once())->method('init');
        $restMock->expects($this->once())->method('init');

        // Execute init
        $pluginMock->init();
    }

    public function testPluginUrl(): void
    {
        $this->assertEquals($this->plugin_url, $this->plugin->plugin_url());
    }

    public function testPluginDir(): void
    {
        $this->assertEquals($this->plugin_dir, $this->plugin->plugin_dir());
    }

    public function testGetPath(): void
    {
        $subpath = 'subfolder/file.php';
        $expected = $this->plugin_dir . '/' . $subpath;

        $this->assertEquals($expected, $this->plugin->get_path($subpath));
        $this->assertEquals($expected, $this->plugin->get_path('/' . $subpath));
        $this->assertEquals($this->plugin_dir . '/', $this->plugin->get_path());
    }

    public function testGetApi(): void
    {
        $this->assertSame($this->api, $this->plugin->get_api());
    }

    public function testRegisterPokemonPostType(): void
    {
        Functions\expect('register_post_type')
            ->once()
            ->with('pokemon', $this->callback(function($args) {
                return isset($args['labels']) &&
                    isset($args['public']) &&
                    isset($args['publicly_queryable']);
            }));

        $this->plugin->register_pokemon_post_type();
    }

    public function testRegisterHooks(): void
    {
        Functions\expect('add_action')
            ->once()
            ->with('init', [$this->plugin, 'register_pokemon_post_type']);

        $reflection = new \ReflectionClass(get_class($this->plugin));
        $method = $reflection->getMethod('register_hooks');
        $method->setAccessible(true);
        $method->invoke($this->plugin);
    }
}