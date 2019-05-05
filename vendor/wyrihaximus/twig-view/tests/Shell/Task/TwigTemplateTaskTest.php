<?php
declare(strict_types=1);
/**
 * This file is part of TwigView.
 *
 ** (c) 2015 Cees-Jan Kiewiet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WyriHaximus\CakePHP\Tests\TwigView\Shell\Task;

use Bake\Shell\Task\BakeTemplateTask;
use Bake\Shell\Task\ModelTask;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\StringCompareTrait;
use WyriHaximus\CakePHP\Tests\TwigView\TestCase;
use WyriHaximus\TwigView\Shell\Task\TwigTemplateTask;

/**
 * TwigTemplateTaskTest class.
 */
class TwigTemplateTaskTest extends TestCase
{
    use StringCompareTrait;

    /**
     * Fixtures.
     *
     * @var array
     */
    public $fixtures = [
        'core.Authors',
    ];

    /**
     * setUp method.
     *
     * Ensure that the default template is used
     *
     */
    public function setUp()
    {
        parent::setUp();
        $this->_compareBasePath = PLUGIN_REPO_ROOT . 'tests' . DS . 'comparisons' . DIRECTORY_SEPARATOR;

        Configure::write('App.namespace', 'WyriHaximus\TwigView\Test\App');
        $this->setupTask(['in', 'err', 'error', 'createFile', '_stop']);
    }

    /**
     * tearDown method.
     *
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
        unset($this->Task);
    }

    /**
     * test Bake method.
     *
     */
    public function testBakeView()
    {
        $this->markTestSkipped();

        $this->Task->modelName = __NAMESPACE__ . '\\TemplateTask\\AuthorsTable';
        $this->Task->controllerName = 'Authors';
        $this->Task->controllerClass = __NAMESPACE__ . '\\TemplateTask\\AuthorsController';

        $this->Task
            ->expects($this->at(0))
            ->method('createFile')
            ->with($this->_normalizePath(APP . 'Template/Authors/view.twig'));

        $result = $this->Task->bake('view', true);
        $this->assertSameAsFile(__FUNCTION__ . '.twig', $result);
    }

    /**
     * test baking an edit file.
     *
     */
    public function testBakeEdit()
    {
        $this->markTestSkipped();

        $this->Task->modelName = __NAMESPACE__ . '\\TemplateTask\\AuthorsTable';
        $this->Task->controllerName = 'Authors';
        $this->Task->controllerClass = __NAMESPACE__ . '\\TemplateTask\\AuthorsController';

        $this->Task->expects($this->at(0))->method('createFile')
            ->with(
                $this->_normalizePath(APP . 'Template/Authors/edit.twig')
            );
        $result = $this->Task->bake('edit', true);
        $this->assertSameAsFile(__FUNCTION__ . '.twig', $result);
    }

    /**
     * test baking an index.
     *
     */
    public function testBakeIndex()
    {
        $this->markTestSkipped();

        $this->Task->modelName = __NAMESPACE__ . '\\TemplateTask\\AuthorsTable';
        $this->Task->controllerName = 'Authors';
        $this->Task->controllerClass = __NAMESPACE__ . '\\TemplateTask\\AuthorsController';

        $this->Task->expects($this->at(0))->method('createFile')
            ->with(
                $this->_normalizePath(APP . 'Template/Authors/index.twig')
            );
        $result = $this->Task->bake('index', true);
        $this->assertSameAsFile(__FUNCTION__ . '.twig', $result);
    }

    /**
     * test bake template with index limit overwrite.
     *
     */
    public function testBakeIndexWithIndexLimit()
    {
        $this->markTestSkipped();

        $this->Task->modelName = __NAMESPACE__ . '\\TemplateTask\\AuthorsTable';
        $this->Task->controllerName = 'Authors';
        $this->Task->controllerClass = __NAMESPACE__ . '\\TemplateTask\\AuthorsController';
        $this->Task->params['index-columns'] = 1;
        $this->Task->expects($this->at(0))->method('createFile')
            ->with(
                $this->_normalizePath(APP . 'Template/Authors/index.twig')
            );
        $result = $this->Task->bake('index', true);
        $this->assertSameAsFile(__FUNCTION__ . '.twig', $result);
    }

    /**
     * test baking an index with output file.
     *
     */
    public function testBakeIndexWithOutputFile()
    {
        $this->markTestSkipped();

        $this->Task->modelName = __NAMESPACE__ . '\\TemplateTask\\AuthorsTable';
        $this->Task->controllerName = 'Authors';
        $this->Task->controllerClass = __NAMESPACE__ . '\\TemplateTask\\AuthorsController';

        $this->Task->expects($this->at(0))->method('createFile')
            ->with(
                $this->_normalizePath(APP . 'Template/Authors/listing.twig')
            );
        $this->Task->bake('index', true, 'listing');
    }

    /**
     * Generate the mock objects used in tests.
     *
     * @param $methods
     */
    protected function setupTask($methods)
    {
        $this->markTestSkipped();

        $io = $this->getMockBuilder(ConsoleIo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->Task = $this->getMockBuilder(TwigTemplateTask::class)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
        $this->Task->connection = 'test';

        $this->Task->BakeTemplate = new BakeTemplateTask($io);
        $this->Task->BakeTemplate->params['theme'] = 'TwigView';
        $this->Task->Model = $this->getMockBuilder(ModelTask::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
