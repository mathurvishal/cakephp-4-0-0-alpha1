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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace DebugKit\Test\TestCase;

use Cake\TestSuite\TestCase;
use DebugKit\TestApp\Panel\SimplePanel;

/**
 * DebugPanel TestCase
 */
class DebugPanelTest extends TestCase
{
    /**
     * @var SimplePanel
     */
    protected $panel;

    public function setUp()
    {
        parent::setUp();
        $this->panel = new SimplePanel();
    }

    public function testTitle()
    {
        $this->assertEquals('Simple', $this->panel->title());
    }

    public function testElementName()
    {
        $this->assertEquals('DebugKit.simple_panel', $this->panel->elementName());

        $this->panel->plugin = 'Derpy';
        $this->assertEquals('Derpy.simple_panel', $this->panel->elementName());

        $this->panel->plugin = false;
        $this->assertEquals('simple_panel', $this->panel->elementName());
    }
}
