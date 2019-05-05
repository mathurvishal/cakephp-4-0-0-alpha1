<?php
declare(strict_types=1);
/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Filesystem\Folder;
use Cake\TestSuite\TestCase;

/**
 * ExtractTaskTest class
 *
 * @property \Cake\Shell\Task\ExtractTask|MockObject $Task
 * @property \Cake\Console\ConsoleIo|MockObject $io
 * @property string $path
 */
class ExtractTaskTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')
            ->disableOriginalConstructor()
            ->getMock();
        $progress = $this->getMockBuilder('Cake\Shell\Helper\ProgressHelper')
            ->setConstructorArgs([$this->io])
            ->getMock();
        $this->io->method('helper')
            ->will($this->returnValue($progress));

        $this->Task = $this->getMockBuilder('Cake\Shell\Task\ExtractTask')
            ->setMethods(['in', 'out', 'err', '_stop'])
            ->setConstructorArgs([$this->io])
            ->getMock();
        $this->path = TMP . 'tests/extract_task_test';
        new Folder($this->path . DS . 'locale', true);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Task);

        $Folder = new Folder($this->path);
        $Folder->delete();
        $this->clearPlugins();
    }

    /**
     * testExecute method
     *
     * @return void
     */
    public function testExecute()
    {
        $this->Task->params['paths'] = TEST_APP . 'templates' . DS . 'Pages';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';

        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));
        $this->Task->expects($this->never())->method('_stop');

        $this->Task->main();
        $this->assertFileExists($this->path . DS . 'default.pot');
        $result = file_get_contents($this->path . DS . 'default.pot');

        $this->assertFileNotExists($this->path . DS . 'cake.pot');

        // extract.ctp
        $pattern = '/\#: [\/\\\\]extract\.php:\d+\n';
        $pattern .= '\#: [\/\\\\]extract\.php:\d+\n';
        $pattern .= 'msgid "You have %d new message."\nmsgid_plural "You have %d new messages."/';
        $this->assertRegExp($pattern, $result);

        $pattern = '/msgid "You have %d new message."\nmsgstr ""/';
        $this->assertNotRegExp($pattern, $result, 'No duplicate msgid');

        $pattern = '/\#: [\/\\\\]extract\.php:\d+\n';
        $pattern .= 'msgid "You deleted %d message."\nmsgid_plural "You deleted %d messages."/';
        $this->assertRegExp($pattern, $result);

        $pattern = '/\#: [\/\\\\]extract\.php:\d+\nmsgid "';
        $pattern .= 'Hot features!';
        $pattern .= '\\\n - No Configuration: Set-up the database and let the magic begin';
        $pattern .= '\\\n - Extremely Simple: Just look at the name...It\'s Cake';
        $pattern .= '\\\n - Active, Friendly Community: Join us #cakephp on IRC. We\'d love to help you get started';
        $pattern .= '"\nmsgstr ""/';
        $this->assertRegExp($pattern, $result);

        $this->assertContains('msgid "double \\"quoted\\""', $result, 'Strings with quotes not handled correctly');
        $this->assertContains("msgid \"single 'quoted'\"", $result, 'Strings with quotes not handled correctly');

        $pattern = '/\#: [\/\\\\]extract\.php:\d+\n';
        $pattern .= 'msgctxt "mail"\n';
        $pattern .= 'msgid "letter"/';
        $this->assertRegExp($pattern, $result);

        $pattern = '/\#: [\/\\\\]extract\.php:\d+\n';
        $pattern .= 'msgctxt "alphabet"\n';
        $pattern .= 'msgid "letter"/';
        $this->assertRegExp($pattern, $result);

        // extract.php - reading the domain.pot
        $result = file_get_contents($this->path . DS . 'domain.pot');

        $pattern = '/msgid "You have %d new message."\nmsgid_plural "You have %d new messages."/';
        $this->assertNotRegExp($pattern, $result);
        $pattern = '/msgid "You deleted %d message."\nmsgid_plural "You deleted %d messages."/';
        $this->assertNotRegExp($pattern, $result);

        $pattern = '/msgid "You have %d new message \(domain\)."\nmsgid_plural "You have %d new messages \(domain\)."/';
        $this->assertRegExp($pattern, $result);
        $pattern = '/msgid "You deleted %d message \(domain\)."\nmsgid_plural "You deleted %d messages \(domain\)."/';
        $this->assertRegExp($pattern, $result);
    }

    /**
     * testExecute with merging on method
     *
     * @return void
     */
    public function testExecuteMerge()
    {
        $this->Task->params['paths'] = TEST_APP . 'templates' . DS . 'Pages';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'yes';

        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));
        $this->Task->expects($this->never())->method('_stop');

        $this->Task->main();
        $this->assertFileExists($this->path . DS . 'default.pot');
        $this->assertFileNotExists($this->path . DS . 'cake.pot');
        $this->assertFileNotExists($this->path . DS . 'domain.pot');
    }

    /**
     * test exclusions
     *
     * @return void
     */
    public function testExtractWithExclude()
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] = TEST_APP . 'templates';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['exclude'] = 'Pages,Layout';
        $this->Task->params['extract-core'] = 'no';

        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));

        $this->Task->main();
        $this->assertFileExists($this->path . DS . 'default.pot');
        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/\#: .*extract\.php:\d+\n/';
        $this->assertNotRegExp($pattern, $result);

        $pattern = '/\#: .*default\.php:\d+\n/';
        $this->assertNotRegExp($pattern, $result);
    }

    /**
     * testExtractWithoutLocations method
     *
     * @return void
     */
    public function testExtractWithoutLocations()
    {
        $this->Task->params['paths'] = TEST_APP . 'templates';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['exclude'] = 'Pages,Layout';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['no-location'] = true;

        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));

        $this->Task->main();
        $this->assertFileExists($this->path . DS . 'default.pot');

        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/\n\#: .*\n/';
        $this->assertNotRegExp($pattern, $result);
    }

    /**
     * test extract can read more than one path.
     *
     * @return void
     */
    public function testExtractMultiplePaths()
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] =
            TEST_APP . 'templates/Pages,' .
            TEST_APP . 'templates/Posts';

        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->never())->method('_stop');
        $this->Task->main();

        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/msgid "Add User"/';
        $this->assertRegExp($pattern, $result);
    }

    /**
     * Tests that it is possible to exclude plugin paths by enabling the param option for the ExtractTask
     *
     * @return void
     */
    public function testExtractExcludePlugins()
    {
        static::setAppNamespace();
        $this->Task = $this->getMockBuilder('Cake\Shell\Task\ExtractTask')
            ->setMethods(['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'])
            ->setConstructorArgs([$this->io])
            ->getMock();
        $this->Task->expects($this->exactly(1))
            ->method('_isExtractingApp')
            ->will($this->returnValue(true));

        $this->Task->params['paths'] = TEST_APP . 'TestApp/';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['exclude-plugins'] = true;

        $this->Task->main();
        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertNotRegExp('#TestPlugin#', $result);
    }

    /**
     * Test that is possible to extract messages from a single plugin
     *
     * @return void
     */
    public function testExtractPlugin()
    {
        static::setAppNamespace();
        $this->loadPlugins(['TestPlugin']);

        $this->Task = $this->getMockBuilder('Cake\Shell\Task\ExtractTask')
            ->setMethods(['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'])
            ->setConstructorArgs([$this->io])
            ->getMock();

        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['plugin'] = 'TestPlugin';

        $this->Task->main();
        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertNotRegExp('#Pages#', $result);
        $this->assertRegExp('/translate\.php:\d+/', $result);
        $this->assertContains('This is a translatable string', $result);
    }

    /**
     * Test that is possible to extract messages from a vendored plugin.
     *
     * @return void
     */
    public function testExtractVendoredPlugin()
    {
        static::setAppNamespace();
        $this->loadPlugins(['Company/TestPluginThree']);

        $this->Task = $this->getMockBuilder('Cake\Shell\Task\ExtractTask')
            ->setMethods(['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'])
            ->setConstructorArgs([$this->io])
            ->getMock();

        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['plugin'] = 'Company/TestPluginThree';

        $this->Task->main();
        $result = file_get_contents($this->path . DS . 'test_plugin_three.pot');
        $this->assertNotRegExp('#Pages#', $result);
        $this->assertRegExp('/default\.php:\d+/', $result);
        $this->assertContains('A vendor message', $result);
    }

    /**
     *  Test that the extract shell overwrites existing files with the overwrite parameter
     *
     * @return void
     */
    public function testExtractOverwrite()
    {
        static::setAppNamespace();
        $this->Task->interactive = false;

        $this->Task->params['paths'] = TEST_APP . 'TestApp/';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['overwrite'] = true;

        file_put_contents($this->path . DS . 'default.pot', 'will be overwritten');
        $this->assertFileExists($this->path . DS . 'default.pot');
        $original = file_get_contents($this->path . DS . 'default.pot');

        $this->Task->main();
        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertNotEquals($original, $result);
    }

    /**
     *  Test that the extract shell scans the core libs
     *
     * @return void
     */
    public function testExtractCore()
    {
        static::setAppNamespace();
        $this->Task->interactive = false;

        $this->Task->params['paths'] = TEST_APP . 'TestApp/';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'yes';

        $this->Task->main();
        $this->assertFileExists($this->path . DS . 'cake.pot');
        $result = file_get_contents($this->path . DS . 'cake.pot');

        $pattern = '/#: Console\/Templates\//';
        $this->assertNotRegExp($pattern, $result);

        $pattern = '/#: Test\//';
        $this->assertNotRegExp($pattern, $result);
    }

    /**
     * Test when marker-error option is set
     * When marker-error is unset, it's already test
     * with other functions like testExecute that not detects error because err never called
     */
    public function testMarkerErrorSets()
    {
        $this->Task->method('err')
            ->will($this->returnCallback([$this, 'echoTest']));

        $this->Task->params['paths'] = TEST_APP . 'templates' . DS . 'Pages';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';
        $this->Task->params['marker-error'] = true;

        $this->expectOutputRegex('/.*Invalid marker content in .*extract\.php.*/');
        $this->Task->main();
    }

    /**
     * A useful function to mock/replace err or out function that allows to use expectOutput
     * @param string $val
     * @param int $nbLines
     */
    public function echoTest($val = '', $nbLines = 1)
    {
        echo $val . str_repeat(PHP_EOL, $nbLines);
    }

    /**
     * test relative-paths option
     *
     * @return void
     */
    public function testExtractWithRelativePaths()
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] = TEST_APP . 'templates';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['relative-paths'] = true;

        $this->Task->method('in')
            ->will($this->returnValue('y'));

        $this->Task->main();
        $this->assertFileExists($this->path . DS . 'default.pot');
        $result = file_get_contents($this->path . DS . 'default.pot');

        $expected = '#: ./tests/test_app/templates/Pages/extract.php:';
        $this->assertContains($expected, $result);
    }
}
