<?php
declare(strict_types=1);
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         0.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Bake\Test\TestCase\Command;

use Bake\Command\TemplateCommand;
use Bake\Test\TestCase\TestCase;
use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\ORM\TableRegistry;
use Cake\View\Exception\MissingTemplateException;

/**
 * TemplateCommand test
 */
class TemplateCommandTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'core.Articles',
        'core.Tags',
        'core.ArticlesTags',
        'core.Posts',
        'core.Comments',
        'core.TestPluginComments',
        'plugin.Bake.BakeTemplateAuthors',
        'plugin.Bake.BakeTemplateRoles',
        'plugin.Bake.BakeTemplateProfiles',
        'plugin.Bake.CategoryThreads',
    ];

    /**
     * setUp method
     *
     * Ensure that the default template is used
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->_compareBasePath = Plugin::path('Bake') . 'tests' . DS . 'comparisons' . DS . 'Template' . DS;

        $this->setAppNamespace('Bake\Test\App');
        $this->useCommandRunner();

        TableRegistry::getTableLocator()->get('TemplateTaskComments', [
            'className' => 'Bake\Test\App\Model\Table\TemplateTaskCommentsTable',
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::getTableLocator()->clear();
    }

    /**
     * Test the controller() method.
     *
     * @return void
     */
    public function testController()
    {
        $command = new TemplateCommand();
        $args = new Arguments([], [], []);
        $command->controller($args, 'Comments');
        $this->assertEquals('Comments', $command->controllerName);
        $this->assertEquals(
            'Bake\Test\App\Controller\CommentsController',
            $command->controllerClass
        );
    }

    /**
     * Test the controller() method.
     *
     * @param $name
     * @dataProvider nameVariations
     * @return void
     */
    public function testControllerVariations($name)
    {
        $command = new TemplateCommand();
        $args = new Arguments([], [], []);
        $command->controller($args, $name);
        $this->assertEquals('TemplateTaskComments', $command->controllerName);
    }

    /**
     * Test controller method with plugins.
     *
     * @return void
     */
    public function testControllerPlugin()
    {
        $command = new TemplateCommand();
        $command->plugin = 'BakeTest';
        $args = new Arguments([], [], []);
        $command->controller($args, 'Tests');

        $this->assertEquals('Tests', $command->controllerName);
        $this->assertEquals(
            'BakeTest\Controller\TestsController',
            $command->controllerClass
        );
    }

    /**
     * Test controller method with prefixes.
     *
     * @return void
     */
    public function testControllerPrefix()
    {
        $command = new TemplateCommand();

        $args = new Arguments([], ['prefix' => 'Admin'], []);
        $command->controller($args, 'Posts');
        $this->assertEquals('Posts', $command->controllerName);
        $this->assertEquals(
            'Bake\Test\App\Controller\Admin\PostsController',
            $command->controllerClass
        );

        $command->plugin = 'BakeTest';
        $command->controller($args, 'Comments');
        $this->assertEquals('Comments', $command->controllerName);
        $this->assertEquals(
            'BakeTest\Controller\Admin\CommentsController',
            $command->controllerClass
        );
    }

    /**
     * Test controller method with nested prefixes.
     *
     * @return void
     */
    public function testControllerPrefixNested()
    {
        $command = new TemplateCommand();
        $args = new Arguments([], ['prefix' => 'Admin/Management'], []);

        $command->controller($args, 'Posts');
        $this->assertEquals('Posts', $command->controllerName);
        $this->assertEquals(
            'Bake\Test\App\Controller\Admin\Management\PostsController',
            $command->controllerClass
        );
    }

    /**
     * test controller with a non-conventional controller name
     *
     * @return void
     */
    public function testControllerWithOverride()
    {
        $command = new TemplateCommand();
        $args = new Arguments([], [], []);

        $command->controller($args, 'Comments', 'Posts');
        $this->assertEquals('Posts', $command->controllerName);
        $this->assertEquals(
            'Bake\Test\App\Controller\PostsController',
            $command->controllerClass
        );
    }

    /**
     * Test the model() method.
     *
     * @return void
     */
    public function testModel()
    {
        $command = new TemplateCommand();
        $command->model('Articles');
        $this->assertEquals('Articles', $command->modelName);

        $command->model('NotThere');
        $this->assertEquals('NotThere', $command->modelName);
    }

    /**
     * Test model() method with plugins.
     *
     * @return void
     */
    public function testModelPlugin()
    {
        $command = new TemplateCommand();
        $command->plugin = 'BakeTest';
        $command->model('BakeTestComments');
        $this->assertEquals(
            'BakeTest.BakeTestComments',
            $command->modelName
        );
    }

    /**
     * Test getPath()
     *
     * @return void
     */
    public function testGetPath()
    {
        $command = new TemplateCommand();
        $command->controllerName = 'Posts';
        $args = new Arguments([], [], []);

        $result = $command->getPath($args);
        $this->assertPathEquals(APP . '../templates/Posts/', $result);

        $args = new Arguments([], ['prefix' => 'admin'], []);
        $result = $command->getPath($args);
        $this->assertPathEquals(APP . '../templates/Admin/Posts/', $result);

        $args = new Arguments([], ['prefix' => 'admin/management'], []);
        $result = $command->getPath($args);
        $this->assertPathEquals(APP . '../templates/Admin/Management/Posts/', $result);

        $args = new Arguments([], ['prefix' => 'Admin/management'], []);
        $result = $command->getPath($args);
        $this->assertPathEquals(APP . '../templates/Admin/Management/Posts/', $result);
    }

    /**
     * Test getPath with plugins.
     *
     * @return void
     */
    public function testGetPathPlugin()
    {
        $pluginPath = APP . 'Plugin/TestTemplate/';
        $this->loadPlugins(['TestTemplate' => ['path' => $pluginPath]]);

        $command = new TemplateCommand();
        $command->controllerName = 'Posts';
        $command->plugin = 'TestTemplate';

        // Use this->plugin as plugin could be in the name arg
        $args = new Arguments([], [], []);
        $result = $command->getPath($args);
        $this->assertPathEquals($pluginPath . 'src/../templates/Posts/', $result);

        // Use this->plugin as plugin could be in the name arg
        $args = new Arguments([], ['prefix' => 'admin'], []);
        $result = $command->getPath($args);
        $this->assertPathEquals($pluginPath . 'src/../templates/Admin/Posts/', $result);

        $this->removePlugins(['TestTemplate']);
    }

    /**
     * Test getContent and parsing of Templates.
     *
     * @return void
     */
    public function testGetContent()
    {
        $namespace = Configure::read('App.namespace');
        $vars = [
            'modelClass' => 'TestTemplateModel',
            'entityClass' => $namespace . '\Model\Entity\TestTemplateModel',
            'schema' => TableRegistry::getTableLocator()->get('TemplateTaskComments')->getSchema(),
            'primaryKey' => ['id'],
            'displayField' => 'name',
            'singularVar' => 'testTemplateModel',
            'pluralVar' => 'testTemplateModels',
            'singularHumanName' => 'Test Template Model',
            'pluralHumanName' => 'Test Template Models',
            'fields' => ['id', 'name', 'body'],
            'associations' => [],
            'keyFields' => [],
            'namespace' => $namespace,
        ];
        $command = new TemplateCommand();
        $args = new Arguments([], [], []);
        $io = $this->createMock(ConsoleIo::class);
        $result = $command->getContent($args, $io, 'view', $vars);
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * Test getContent with associations
     *
     * @return void
     */
    public function testGetContentAssociations()
    {
        $namespace = Configure::read('App.namespace');
        $vars = [
            'modelClass' => 'TemplateTaskComments',
            'entityClass' => $namespace . '\Model\Entity\TemplateTaskComment',
            'schema' => TableRegistry::getTableLocator()->get('TemplateTaskComments')->getSchema(),
            'primaryKey' => ['id'],
            'displayField' => 'name',
            'singularVar' => 'templateTaskComment',
            'pluralVar' => 'templateTaskComments',
            'singularHumanName' => 'Template Task Comment',
            'pluralHumanName' => 'Template Task Comments',
            'fields' => ['id', 'name', 'body'],
            'associations' => [
                'belongsTo' => [
                    'Authors' => [
                        'property' => 'author',
                        'variable' => 'author',
                        'primaryKey' => ['id'],
                        'displayField' => 'name',
                        'foreignKey' => 'author_id',
                        'alias' => 'Authors',
                        'controller' => 'TemplateTaskAuthors',
                        'fields' => ['name'],
                    ],
                ],
            ],
            'keyFields' => [],
            'namespace' => $namespace,
        ];

        $command = new TemplateCommand();
        $args = new Arguments([], [], []);
        $io = $this->createMock(ConsoleIo::class);
        $result = $command->getContent($args, $io, 'view', $vars);
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * Test getContent with no pk
     *
     * @return void
     */
    public function testGetContentWithNoPrimaryKey()
    {
        $namespace = Configure::read('App.namespace');
        $vars = [
            'modelClass' => 'TestTemplateModel',
            'entityClass' => $namespace . '\Model\Entity\TestTemplateModel',
            'schema' => TableRegistry::getTableLocator()->get('TemplateTaskComments')->getSchema(),
            'primaryKey' => [],
            'displayField' => 'name',
            'singularVar' => 'testTemplateModel',
            'pluralVar' => 'testTemplateModels',
            'singularHumanName' => 'Test Template Model',
            'pluralHumanName' => 'Test Template Models',
            'fields' => ['id', 'name', 'body'],
            'associations' => [],
            'keyFields' => [],
            'namespace' => $namespace,
        ];

        $this->expectException(StopException::class);
        $io = $this->createMock(ConsoleIo::class);
        $io->expects($this->once())
            ->method('error')
            ->with('Cannot generate views for models with no primary key');

        $command = new TemplateCommand();
        $args = new Arguments([], [], []);
        $command->getContent($args, $io, 'view', $vars);
    }

    /**
     * test getContent() using a routing prefix action.
     *
     * @return void
     */
    public function testGetContentWithRoutingPrefix()
    {
        $namespace = Configure::read('App.namespace');
        $vars = [
            'modelClass' => 'TestTemplateModel',
            'entityClass' => $namespace . '\Model\Entity\TestTemplateModel',
            'schema' => TableRegistry::getTableLocator()->get('TemplateTaskComments')->getSchema(),
            'primaryKey' => ['id'],
            'displayField' => 'name',
            'singularVar' => 'testTemplateModel',
            'pluralVar' => 'testTemplateModels',
            'singularHumanName' => 'Test Template Model',
            'pluralHumanName' => 'Test Template Models',
            'fields' => ['id', 'name', 'body'],
            'keyFields' => [],
            'associations' => [],
            'namespace' => $namespace,
        ];
        $command = new TemplateCommand();
        $args = new Arguments([], ['prefix' => 'Admin'], []);
        $io = $this->createMock(ConsoleIo::class);

        $result = $command->getContent($args, $io, 'view', $vars);
        $this->assertSameAsFile(__FUNCTION__ . '-view.php', $result);

        $result = $command->getContent($args, $io, 'add', $vars);
        $this->assertSameAsFile(__FUNCTION__ . '-add.php', $result);
    }

    /**
     * test Bake method
     *
     * @return void
     */
    public function testBakeView()
    {
        $this->generatedFile = ROOT . 'templates/Authors/view.php';
        $this->exec('bake template authors view');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFileExists($this->generatedFile);

        $result = file_get_contents($this->generatedFile);
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * test baking an edit file
     *
     * @return void
     */
    public function testBakeEdit()
    {
        $this->generatedFile = ROOT . 'templates/Authors/edit.php';
        $this->exec('bake template authors edit');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFileExists($this->generatedFile);

        $result = file_get_contents($this->generatedFile);
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * test baking an index
     *
     * @return void
     */
    public function testBakeIndex()
    {
        $this->generatedFile = ROOT . 'templates/TemplateTaskComments/index.php';
        $this->exec('bake template template_task_comments index');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFileExists($this->generatedFile);

        $result = file_get_contents($this->generatedFile);
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * test bake template with index limit overwrite
     *
     * @return void
     */
    public function testBakeIndexWithIndexLimit()
    {
        $this->generatedFile = ROOT . 'templates/TemplateTaskComments/index.php';
        $this->exec('bake template template_task_comments --index-columns 3 index');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFileExists($this->generatedFile);

        $result = file_get_contents($this->generatedFile);
        $this->assertSameAsFile(__FUNCTION__ . '.php', $result);
    }

    /**
     * test Bake with plugins
     *
     * @return void
     */
    public function testBakeIndexPlugin()
    {
        $this->_loadTestPlugin('BakeTest');
        $path = Plugin::templatePath('BakeTest');

        // Setup association to ensure properties don't have dots
        $model = TableRegistry::getTableLocator()->get('BakeTest.Comments');
        $model->belongsTo('Articles');

        $this->generatedFile = $path . 'Comments/index.php';
        $this->exec('bake template BakeTest.comments index');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFileExists($this->generatedFile);
        $this->assertFileContains('$comment->article->id', $this->generatedFile);
    }

    /**
     * Ensure that models in a tree don't include form fields for lft/rght
     *
     * @return void
     */
    public function testBakeTreeNoLftOrRght()
    {
        $this->generatedFiles = [
            APP . '../templates/CategoryThreads/add.php',
            APP . '../templates/CategoryThreads/index.php',
        ];
        $this->exec('bake template CategoryThreads index');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $this->exec('bake template CategoryThreads add');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $this->assertFilesExist($this->generatedFiles);
        $this->assertFileNotContains('rght', $this->generatedFiles[0]);
        $this->assertFileNotContains('lft', $this->generatedFiles[0]);

        $this->assertFileNotContains('rght', $this->generatedFiles[1]);
        $this->assertFileNotContains('lft', $this->generatedFiles[1]);
    }

    /**
     * Ensure that models associated with themselves do not have action
     * links generated.
     *
     * @return void
     */
    public function testBakeSelfAssociationsNoNavLinks()
    {
        $this->generatedFiles = [
            APP . '../templates/CategoryThreads/index.php',
        ];
        $this->exec('bake template CategoryThreads index');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertFileNotContains('New Parent Category', $this->generatedFiles[0]);
        $this->assertFileNotContains('List Parent Category', $this->generatedFiles[0]);
    }

    /**
     * Ensure that models associated with themselves do not have action
     * links generated.
     *
     * @return void
     */
    public function testBakeSelfAssociationsRelatedAssociations()
    {
        $this->generatedFile = ROOT . 'templates/CategoryThreads/view.php';
        $this->exec('bake template category_threads view');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFileExists($this->generatedFile);

        $this->assertFileContains('Related Category Threads', $this->generatedFile);
        $this->assertFileContains('Parent Category Threads', $this->generatedFile);
    }

    /**
     * test that baking a view with no template doesn't make a file.
     *
     * @return void
     */
    public function testBakeWithNoTemplate()
    {
        $this->expectException(MissingTemplateException::class);
        $this->expectExceptionMessage('No bake template found for "Template/delete"');
        $this->exec('bake template template_task_comments delete');
    }

    /**
     * Test execute no args.
     *
     * @return void
     */
    public function testMainNoArgs()
    {
        $this->exec('bake template');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Possible tables to bake view templates for based on your current database:');
        $this->assertOutputContains('- Comments');
        $this->assertOutputContains('- Articles');
    }

    /**
     * test `cake bake view $controller view`
     *
     * @return void
     */
    public function testMainWithActionParam()
    {
        $this->generatedFile = ROOT . 'templates/TemplateTaskComments/view.php';
        $this->exec('bake template TemplateTaskComments view');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFileExists($this->generatedFile);
        $this->assertFileNotExists(
            ROOT . 'templates/TemplateTaskComments/edit.php',
            'no extra files'
        );
        $this->assertFileNotExists(
            ROOT . 'templates/TemplateTaskComments/add.php',
            'no extra files'
        );
    }

    /**
     * test `cake bake view $controller`
     * Ensure that views are only baked for actions that exist in the controller.
     *
     * @return void
     */
    public function testMainWithExistingController()
    {
        $this->generatedFiles = [
            ROOT . 'templates/TemplateTaskComments/index.php',
            ROOT . 'templates/TemplateTaskComments/add.php',
        ];
        $this->exec('bake template TemplateTaskComments');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertFileNotExists(
            ROOT . 'templates/TemplateTaskComments/edit.php',
            'no extra files'
        );
        $this->assertFileNotExists(
            ROOT . 'templates/TemplateTaskComments/view.php',
            'no extra files'
        );
    }

    /**
     * test that plugin.name works.
     *
     * @return void
     */
    public function testMainWithPluginName()
    {
        $this->_loadTestPlugin('TestBake');
        $path = Plugin::templatePath('TestBake');

        $this->generatedFile = $path . 'Comments/index.php';
        $this->exec('bake template --connection test TestBake.Comments index');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFileExists($this->generatedFile);
        $this->assertFileNotExists(
            $path . 'Comments/view.php',
            'No other templates made'
        );
    }

    /**
     * static dataprovider for test cases
     *
     * @return void
     */
    public static function nameVariations()
    {
        return [['TemplateTaskComments'], ['template_task_comments']];
    }

    /**
     * test `cake bake view $table --controller Blog`
     *
     * @return void
     */
    public function testMainWithControllerFlag()
    {
        $this->generatedFiles = [
            ROOT . 'templates/Blog/index.php',
            ROOT . 'templates/Blog/view.php',
            ROOT . 'templates/Blog/add.php',
            ROOT . 'templates/Blog/edit.php',
        ];
        $this->exec('bake template --controller Blog Posts');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertOutputNotContains('No bake template found');
        $this->assertErrorEmpty();
    }

    /**
     * test `cake bake view $controller --prefix Admin`
     *
     * @return void
     */
    public function testMainWithControllerAndAdminFlag()
    {
        $this->generatedFiles = [
            ROOT . 'templates/Admin/Posts/index.php',
            ROOT . 'templates/Admin/Posts/add.php',
        ];
        $this->exec('bake template --prefix Admin Posts');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
    }

    /**
     * test `cake bake view posts index list`
     *
     * @return void
     */
    public function testMainWithAlternateTemplates()
    {
        $this->generatedFile = ROOT . 'templates/TemplateTaskComments/list.php';
        $this->exec('bake template TemplateTaskComments index list');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFileExists($this->generatedFile);
        $this->assertFileContains('Template Task Comments', $this->generatedFile);
    }
}
