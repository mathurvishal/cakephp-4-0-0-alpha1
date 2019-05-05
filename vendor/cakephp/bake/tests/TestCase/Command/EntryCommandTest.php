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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Bake\Test\TestCase\Command;

use Bake\Test\TestCase\TestCase;
use Cake\Console\Command;

/**
 * EntryCommand Test
 */
class EntryCommandTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->setAppNamespace('Bake\Test\App');
        $this->useCommandRunner();
    }

    /**
     * teardown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->removePlugins(['BakeTest']);
    }

    /**
     * Test execute() generating a full stack
     *
     * @return void
     */
    public function testExecuteHelp()
    {
        $this->exec('bake --help');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Bake generates code');
        $this->assertOutputContains('Subcommands:');
        $this->assertOutputContains('controller');
        $this->assertOutputContains('Generate controller files.');
        $this->assertOutputContains('shell_helper');
        $this->assertOutputContains('Generate shell helper files.');
    }

    /**
     * Test execute() calling an app task
     *
     * @return void
     */
    public function testExecuteAppTask()
    {
        $this->exec('bake app_policy');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('App Policy Generated');
    }

    /**
     * Test calling an app task --help
     *
     * @return void
     */
    public function testExecuteAppTaskHelp()
    {
        $this->exec('bake app_policy --help');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('bake app_policy');
        $this->assertOutputContains('Options');
    }

    /**
     * Test execute() calling a plugin task
     *
     * @return void
     */
    public function testExecutePluginTask()
    {
        $this->_loadTestPlugin('BakeTest');

        $this->exec('bake zerg --verbose');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Zerg generated');
        $this->assertOutputContains('Loud noises');
    }

    /**
     * Test execute() error on a missing task
     *
     * @return void
     */
    public function testExecuteMissingTask()
    {
        $this->exec('bake nope');

        $this->assertExitCode(Command::CODE_ERROR);
        $this->assertErrorContains('Could not find');
    }
}
