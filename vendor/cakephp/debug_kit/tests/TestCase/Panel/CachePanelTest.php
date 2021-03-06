<?php
declare(strict_types=1);
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 **/
namespace DebugKit\Test\TestCase\Panel;

use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use DebugKit\Panel\CachePanel;

/**
 * Class CachePanelTest
 */
class CachePanelTest extends TestCase
{
    /**
     * @var CachePanel
     */
    protected $panel;

    /**
     * set up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->panel = new CachePanel();
        Cache::setConfig('debug_kit_test', ['className' => 'Null']);
    }

    /**
     * Teardown method.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Cache::drop('debug_kit_test');
    }

    /**
     * test initialize
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->panel->initialize();

        $result = $this->panel->data();
        $this->assertArrayHasKey('debug_kit_test', $result['metrics']);
        $this->assertArrayHasKey('_cake_model_', $result['metrics']);
    }

    /**
     * test initialize incomplete data
     *
     * @return void
     */
    public function testInitializeIncompleteData()
    {
        Cache::setConfig('incomplete', ['duration' => '+2 seconds']);
        $this->panel->initialize();

        $result = $this->panel->data();
        $this->assertArrayHasKey('incomplete', $result['metrics']);
    }

    /**
     * Ensure that subrequests don't double proxy the cache engine.
     *
     * @return void
     */
    public function testInitializeTwiceNoDoubleProxy()
    {
        $this->panel->initialize();
        $result = Cache::engine('debug_kit_test');
        $this->assertInstanceOf('DebugKit\Cache\Engine\DebugEngine', $result);

        $this->panel->initialize();
        $result2 = Cache::engine('debug_kit_test');
        $this->assertSame($result2, $result);
    }
}
