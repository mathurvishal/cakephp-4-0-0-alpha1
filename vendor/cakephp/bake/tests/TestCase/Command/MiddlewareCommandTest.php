<?php
declare(strict_types=1);
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.3.6
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Bake\Test\TestCase\Command;

use Bake\Test\TestCase\TestCase;
use Cake\Console\Command;
use Cake\Core\Plugin;

/**
 * MiddlewareCommandTest class
 */
class MiddlewareCommandTest extends TestCase
{
    /**
     * setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->_compareBasePath = Plugin::path('Bake') . 'tests' . DS . 'comparisons' . DS . 'Middleware' . DS;
        $this->setAppNamespace('Bake\Test\App');
        $this->useCommandRunner();
    }

    /**
     * Test the excute method.
     *
     * @return void
     */
    public function testMain()
    {
        $this->generatedFile = APP . 'Middleware/ExampleMiddleware.php';
        $this->exec('bake middleware example');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFileContains('class ExampleMiddleware', $this->generatedFile);
    }

    /**
     * Test main within a plugin.
     *
     * @return void
     */
    public function testMainPlugin()
    {
        $this->_loadTestPlugin('TestBake');
        $path = Plugin::path('TestBake');

        $this->generatedFile = $path . 'src/Middleware/ExampleMiddleware.php';
        $this->exec('bake middleware TestBake.example');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFileContains('class ExampleMiddleware', $this->generatedFile);
    }

    /**
     * Test baking within a plugin.
     *
     * @return void
     */
    public function testBakePlugin()
    {
        $this->_loadTestPlugin('TestBake');
        $path = Plugin::path('TestBake');

        $this->generatedFile = $path . 'src/Middleware/ExampleMiddleware.php';
        $this->exec('bake middleware TestBake.example');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFile));
    }
}
