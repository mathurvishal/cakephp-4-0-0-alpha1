<?php
declare(strict_types=1);
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
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

use Bake\Command\TestCommand;
use Bake\Test\App\Controller\PostsController;
use Bake\Test\App\Model\Table\ArticlesTable;
use Bake\Test\App\Model\Table\CategoryThreadsTable;
use Bake\Test\TestCase\TestCase;
use Cake\Console\Command;
use Cake\Core\Plugin;
use Cake\Http\Response;
use Cake\Http\ServerRequest as Request;
use Cake\ORM\TableRegistry;

/**
 * TestCommandTest class
 *
 */
class TestCommandTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var string
     */
    public $fixtures = [
        'plugin.Bake.BakeArticles',
        'plugin.Bake.BakeArticlesBakeTags',
        'plugin.Bake.BakeComments',
        'plugin.Bake.BakeTags',
        'core.Authors',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->setAppNamespace('Bake\Test\App');
        $this->_compareBasePath = Plugin::path('Bake') . 'tests' . DS . 'comparisons' . DS . 'Test' . DS;
        $this->useCommandRunner();
    }

    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::getTableLocator()->clear();
    }

    /**
     * Test that with no args execute() outputs the types you can generate
     * tests for.
     *
     * @return void
     */
    public function testExecuteNoArgsPrintsTypeOptions()
    {
        $this->exec('bake test');

        $this->assertOutputContains('You must provide a class type');
        $this->assertOutputContains('1. Entity');
        $this->assertOutputContains('2. Table');
        $this->assertOutputContains('3. Controller');
        $this->assertExitCode(Command::CODE_SUCCESS);
    }

    /**
     * Test that with no args execute() outputs the types you can generate
     * tests for.
     *
     * @return void
     */
    public function testExecuteOneArgPrintsClassOptions()
    {
        $this->exec('bake test entity');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('You must provide a class to bake');
    }

    /**
     * test execute with type and class name defined
     *
     * @return void
     */
    public function testExecuteWithTwoArgs()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Model/Table/TestTaskTagTableTest.php',
        ];
        $this->exec('bake test Table TestTaskTag');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertFileContains(
            'class TestTaskTagTableTest extends TestCase',
            $this->generatedFiles[0]
        );
    }

    /**
     * test execute with plugin syntax
     *
     * @return void
     */
    public function testExecuteWithPluginName()
    {
        $this->_loadTestPlugin('TestBake');

        $this->generatedFiles = [
            ROOT . 'Plugin/TestBake/tests/TestCase/Model/Table/BakeArticlesTableTest.php',
        ];
        $this->exec('bake test table TestBake.BakeArticles');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertFileContains(
            'class BakeArticlesTableTest extends TestCase',
            $this->generatedFiles[0]
        );
        $this->assertFileContains(
            'namespace TestBake\Test\TestCase\Model\Table;',
            $this->generatedFiles[0]
        );
    }

    /**
     * test execute with type and class name defined
     *
     * @return void
     */
    public function testExecuteWithAll()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Model/Table/ArticlesTableTest.php',
            ROOT . 'tests/TestCase/Model/Table/AuthorsTableTest.php',
            ROOT . 'tests/TestCase/Model/Table/BakeArticlesTableTest.php',
            ROOT . 'tests/TestCase/Model/Table/CategoryThreadsTableTest.php',
            ROOT . 'tests/TestCase/Model/Table/TemplateTaskCommentsTableTest.php',
        ];
        $this->exec('bake test table --all');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
    }

    /**
     * Test generating class options for table.
     *
     * @return void
     */
    public function testOutputClassOptionsForTable()
    {
        $this->exec('bake test table');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('You must provide a class to bake a test for. Some possible options are:');
        $this->assertOutputContains('1. ArticlesTable');
        $this->assertOutputContains('2. AuthorsTable');
        $this->assertOutputContains('3. BakeArticlesTable');
        $this->assertOutputContains('4. CategoryThreadsTable');
        $this->assertOutputContains('5. TemplateTaskCommentsTable');
        $this->assertOutputContains('Re-run your command as `cake bake Table <classname>`');
    }

    /**
     * Test generating class options for table.
     *
     * @return void
     */
    public function testOutputClassOptionsForTablePlugin()
    {
        $this->loadPlugins(['BakeTest' => ['path' => ROOT . 'Plugin' . DS . 'BakeTest' . DS]]);
        $this->exec('bake test table --plugin BakeTest');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('You must provide a class to bake a test for. Some possible options are:');
        $this->assertOutputContains('1. AuthorsTable');
        $this->assertOutputContains('2. BakeArticlesTable');
        $this->assertOutputContains('3. BakeTestCommentsTable');
        $this->assertOutputContains('4. CommentsTable');
    }

    /**
     * Test that method introspection pulls all relevant non parent class
     * methods into the test case.
     *
     * @return void
     */
    public function testMethodIntrospection()
    {
        $command = new TestCommand();
        $result = $command->getTestableMethods('Bake\Test\App\Model\Table\ArticlesTable');
        $expected = ['initialize', 'findpublished', 'dosomething', 'dosomethingelse'];
        $this->assertEquals($expected, array_map('strtolower', $result));
    }

    /**
     * test that the generation of fixtures works correctly.
     *
     * @return void
     */
    public function testFixtureArrayGenerationFromModel()
    {
        $command = new TestCommand();
        $subject = new ArticlesTable();
        $result = $command->generateFixtureList($subject);
        $expected = [
            'app.Articles',
            'app.Authors',
            'app.Tags',
            'app.ArticlesTags',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that the generation of fixtures works correctly.
     *
     * @return void
     */
    public function testFixtureArrayGenerationIgnoreSelfAssociation()
    {
        TableRegistry::getTableLocator()->clear();
        $subject = new CategoryThreadsTable();
        $command = new TestCommand();
        $result = $command->generateFixtureList($subject);
        $expected = [
            'app.CategoryThreads',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that the generation of fixtures works correctly.
     *
     * @return void
     */
    public function testFixtureGenerationFromController()
    {
        $subject = new PostsController(new Request(), new Response());
        $command = new TestCommand();
        $result = $command->generateFixtureList($subject);
        $expected = [
            'app.Posts',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Dataprovider for class name generation.
     *
     * @return array
     */
    public static function realClassProvider()
    {
        return [
            ['Entity', 'Article', 'App\Model\Entity\Article'],
            ['Entity', 'ArticleEntity', 'App\Model\Entity\ArticleEntity'],
            ['Table', 'Posts', 'App\Model\Table\PostsTable'],
            ['Table', 'PostsTable', 'App\Model\Table\PostsTable'],
            ['Controller', 'Posts', 'App\Controller\PostsController'],
            ['Controller', 'PostsController', 'App\Controller\PostsController'],
            ['Behavior', 'Timestamp', 'App\Model\Behavior\TimestampBehavior'],
            ['Behavior', 'TimestampBehavior', 'App\Model\Behavior\TimestampBehavior'],
            ['Helper', 'Form', 'App\View\Helper\FormHelper'],
            ['Helper', 'FormHelper', 'App\View\Helper\FormHelper'],
            ['Component', 'Auth', 'App\Controller\Component\AuthComponent'],
            ['Component', 'AuthComponent', 'App\Controller\Component\AuthComponent'],
            ['Shell', 'Example', 'App\Shell\ExampleShell'],
            ['Shell', 'ExampleShell', 'App\Shell\ExampleShell'],
            ['Task', 'Example', 'App\Shell\Task\ExampleTask'],
            ['Task', 'ExampleTask', 'App\Shell\Task\ExampleTask'],
            ['Cell', 'Example', 'App\View\Cell\ExampleCell'],
            ['Cell', 'ExampleCell', 'App\View\Cell\ExampleCell'],
        ];
    }

    /**
     * test that resolving class names works
     *
     * @dataProvider realClassProvider
     * @return void
     */
    public function testGetRealClassname($type, $name, $expected)
    {
        $this->setAppNamespace('App');

        $command = new TestCommand();
        $result = $command->getRealClassname($type, $name);
        $this->assertEquals($expected, $result);
    }

    /**
     * test resolving class names with plugins
     *
     * @return void
     */
    public function testGetRealClassnamePlugin()
    {
        $this->_loadTestPlugin('TestBake');
        $command = new TestCommand();
        $command->plugin = 'TestBake';

        $result = $command->getRealClassname('Helper', 'Asset');
        $expected = 'TestBake\View\Helper\AssetHelper';
        $this->assertEquals($expected, $result);
    }

    /**
     * test resolving class names with prefix
     *
     * @return void
     */
    public function testGetRealClassnamePrefix()
    {
        $command = new TestCommand();
        $result = $command->getRealClassname('Controller', 'Posts', 'Api/Public');

        $expected = 'Bake\Test\App\Controller\Api\Public\PostsController';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test baking a test for a concrete model with fixtures arg
     *
     * @return void
     */
    public function testBakeFixturesParam()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Model/Table/AuthorsTableTest.php',
        ];
        $this->exec('bake test table Authors --fixtures app.Posts,app.Comments,app.Users');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * Test baking a test for a concrete model with no-fixtures arg
     *
     * @return void
     */
    public function testBakeNoFixtureParam()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Model/Table/AuthorsTableTest.php',
        ];
        $this->exec('bake test table Authors --no-fixture');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * Test baking a test for a cell.
     *
     * @return void
     */
    public function testBakeCellTest()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/View/Cell/ArticlesCellTest.php',
        ];
        $this->exec('bake test cell Articles');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * Test baking a test for a command.
     *
     * @return void
     */
    public function testBakeCommandTest()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Command/OtherExampleCommandTest.php',
        ];
        $this->exec('bake test command OtherExample');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * Test baking a test for a concrete model.
     *
     * @return void
     */
    public function testBakeModelTest()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Model/Table/ArticlesTableTest.php',
        ];
        $this->exec('bake test table Articles');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * test baking controller test files
     *
     * @return void
     */
    public function testBakeControllerTest()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Controller/PostsControllerTest.php',
        ];
        $this->exec('bake test controller PostsController');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * test baking controller test files
     *
     * @return void
     */
    public function testBakePrefixControllerTest()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Controller/Admin/PostsControllerTest.php',
        ];
        $this->exec('bake test controller Admin\PostsController');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * test baking controller test files with prefix CLI option
     *
     * @return void
     */
    public function testBakePrefixControllerTestWithCliOption()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Controller/Admin/PostsControllerTest.php',
        ];
        $this->exec('bake test controller --prefix Admin PostsController');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * test baking component test files,
     *
     * @return void
     */
    public function testBakeComponentTest()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Controller/Component/AppleComponentTest.php',
        ];
        $this->exec('bake test component Apple');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * test baking behavior test files,
     *
     * @return void
     */
    public function testBakeBehaviorTest()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Model/Behavior/ExampleBehaviorTest.php',
        ];
        $this->exec('bake test behavior Example');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * test baking helper test files,
     *
     * @return void
     */
    public function testBakeHelperTest()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/View/Helper/ExampleHelperTest.php',
        ];
        $this->exec('bake test helper Example');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * Test baking a test for a shell.
     *
     * @return void
     */
    public function testBakeShellTest()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Shell/ArticlesShellTest.php',
        ];
        $this->exec('bake test shell Articles');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * Test baking a test for a shell task.
     *
     * @return void
     */
    public function testBakeShellTaskTest()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Shell/Task/ArticlesTaskTest.php',
        ];
        $this->exec('bake test task Articles');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * Test baking a test for a shell helper.
     *
     * @return void
     */
    public function testBakeShellHelperTest()
    {
        $this->generatedFiles = [
            ROOT . 'tests/TestCase/Shell/Helper/ExampleHelperTest.php',
        ];
        $this->exec('bake test shell_helper Example');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertSameAsFile(__FUNCTION__ . '.php', file_get_contents($this->generatedFiles[0]));
    }

    /**
     * Test baking an unknown class type.
     *
     * @return void
     */
    public function testBakeUnknownClass()
    {
        $this->exec('bake test Foo Example');

        $this->assertExitCode(Command::CODE_ERROR);
    }

    /**
     * test Constructor generation ensure that constructClasses is called for controllers
     *
     * @return void
     */
    public function testGenerateConstructor()
    {
        $command = new TestCommand();
        $result = $command->generateConstructor('Controller', 'PostsController');
        $expected = ['', '', ''];
        $this->assertEquals($expected, $result);

        $result = $command->generateConstructor('Table', 'App\Model\\Table\PostsTable');
        $expected = [
            "\$config = TableRegistry::getTableLocator()->exists('Posts') ? [] : ['className' => PostsTable::class];",
            "TableRegistry::getTableLocator()->get('Posts', \$config);",
            '',
        ];
        $this->assertEquals($expected, $result);

        $result = $command->generateConstructor('Helper', 'FormHelper');
        $expected = ["\$view = new View();", "new FormHelper(\$view);", ''];
        $this->assertEquals($expected, $result);

        $result = $command->generateConstructor('Entity', 'TestBake\Model\Entity\Article');
        $expected = ["", "new Article();", ''];
        $this->assertEquals($expected, $result);

        $result = $command->generateConstructor('ShellHelper', 'TestBake\Shell\Helper\ExampleHelper');
        $expected = [
            "\$this->stub = new ConsoleOutput();\n        \$this->io = new ConsoleIo(\$this->stub);",
            "new ExampleHelper(\$this->io);",
            '',
        ];
        $this->assertEquals($expected, $result);

        $result = $command->generateConstructor('Form', 'TestBake\Form\ExampleForm');
        $expected = [
            '',
            "new ExampleForm();",
            '',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test generateUses()
     *
     * @return void
     */
    public function testGenerateUses()
    {
        $command = new TestCommand();
        $result = $command->generateUses('Table', 'App\Model\Table\PostsTable');
        $expected = [
            'Cake\ORM\TableRegistry',
            'App\Model\Table\PostsTable',
        ];
        $this->assertEquals($expected, $result);

        $result = $command->generateUses('Controller', 'App\Controller\PostsController');
        $expected = [
            'App\Controller\PostsController',
        ];
        $this->assertEquals($expected, $result);

        $result = $command->generateUses('Helper', 'App\View\Helper\FormHelper');
        $expected = [
            'Cake\View\View',
            'App\View\Helper\FormHelper',
        ];
        $this->assertEquals($expected, $result);

        $result = $command->generateUses('Component', 'App\Controller\Component\AuthComponent');
        $expected = [
            'Cake\Controller\ComponentRegistry',
            'App\Controller\Component\AuthComponent',
        ];
        $this->assertEquals($expected, $result);

        $result = $command->generateUses('ShellHelper', 'App\Shell\Helper\ExampleHelper');
        $expected = [
            'Cake\TestSuite\Stub\ConsoleOutput',
            'Cake\Console\ConsoleIo',
            'App\Shell\Helper\ExampleHelper',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that mock class generation works for the appropriate classes
     *
     * @return void
     */
    public function testMockClassGeneration()
    {
        $command = new TestCommand();
        $result = $command->hasMockClass('Controller');
        $this->assertTrue($result);
    }

    /**
     * Provider for test case file names.
     *
     * @return array
     */
    public static function caseFileNameProvider()
    {
        return [
            ['Table', 'App\Model\Table\PostsTable', 'TestCase/Model/Table/PostsTableTest.php'],
            ['Entity', 'App\Model\Entity\Article', 'TestCase/Model/Entity/ArticleTest.php'],
            ['Helper', 'App\View\Helper\FormHelper', 'TestCase/View/Helper/FormHelperTest.php'],
            ['Controller', 'App\Controller\PostsController', 'TestCase/Controller/PostsControllerTest.php'],
            ['Controller', 'App\Controller\Admin\PostsController', 'TestCase/Controller/Admin/PostsControllerTest.php'],
            ['Behavior', 'App\Model\Behavior\TreeBehavior', 'TestCase/Model/Behavior/TreeBehaviorTest.php'],
            [
                'Component',
                'App\Controller\Component\AuthComponent',
                'TestCase/Controller/Component/AuthComponentTest.php',
            ],
            ['entity', 'App\Model\Entity\Article', 'TestCase/Model/Entity/ArticleTest.php'],
            ['table', 'App\Model\Table\PostsTable', 'TestCase/Model/Table/PostsTableTest.php'],
            ['helper', 'App\View\Helper\FormHelper', 'TestCase/View/Helper/FormHelperTest.php'],
            ['controller', 'App\Controller\PostsController', 'TestCase/Controller/PostsControllerTest.php'],
            ['behavior', 'App\Model\Behavior\TreeBehavior', 'TestCase/Model/Behavior/TreeBehaviorTest.php'],
            [
                'component',
                'App\Controller\Component\AuthComponent',
                'TestCase/Controller/Component/AuthComponentTest.php',
            ],
            ['Shell', 'App\Shell\ExampleShell', 'TestCase/Shell/ExampleShellTest.php'],
            ['shell', 'App\Shell\ExampleShell', 'TestCase/Shell/ExampleShellTest.php'],
        ];
    }

    /**
     * Test filename generation for each type + plugins
     *
     * @dataProvider caseFileNameProvider
     * @return void
     */
    public function testTestCaseFileName($type, $class, $expected)
    {
        $this->setAppNamespace('App');
        $command = new TestCommand();
        $result = $command->testCaseFileName($type, $class);

        $this->assertPathEquals(ROOT . DS . 'tests' . DS . $expected, $result);
    }

    /**
     * Test filename generation for plugins.
     *
     * @return void
     */
    public function testTestCaseFileNamePlugin()
    {
        $this->loadPlugins([
            'TestTest' => [
                'path' => APP . 'Plugin' . DS . 'TestTest' . DS,
            ],
        ]);
        $this->generatedFiles = [
            APP . 'Plugin/TestTest/tests/TestCase/Model/Entity/ArticleTest.php',
        ];
        $this->exec('bake test entity --plugin TestTest Article');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
    }

    /**
     * Data provider for mapType() tests.
     *
     * @return array
     */
    public static function mapTypeProvider()
    {
        return [
            ['Controller', 'Controller'],
            ['Component', 'Controller\Component'],
            ['Table', 'Model\Table'],
            ['Entity', 'Model\Entity'],
            ['Behavior', 'Model\Behavior'],
            ['Helper', 'View\Helper'],
            ['ShellHelper', 'Shell\Helper'],
        ];
    }

    /**
     * Test that mapType returns the correct package names.
     *
     * @dataProvider mapTypeProvider
     * @return void
     */
    public function testMapType($original, $expected)
    {
        $command = new TestCommand();
        $this->assertEquals($expected, $command->mapType($original));
    }
}
