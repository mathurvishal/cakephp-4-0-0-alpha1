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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Bake\Test\TestCase\Command;

use Bake\Test\TestCase\TestCase;
use Bake\Utility\SubsetSchemaCollection;
use Cake\Console\Command;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * ModelAllCommand test class
 */
class ModelAllCommandTest extends TestCase
{
    /**
     * fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Bake.TodoTasks',
        'plugin.Bake.TodoItems',
    ];

    /**
     * @var array
     */
    protected $tables = ['todo_tasks', 'todo_items'];

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

        $connection = ConnectionManager::get('test');
        $subsetCollection = new SubsetSchemaCollection($connection->getSchemaCollection(), $this->tables);
        $connection->setSchemaCollection($subsetCollection);
    }

    /**
     * teardown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $connection = ConnectionManager::get('test');
        $connection->setSchemaCollection($connection->getSchemaCollection()->getInnerCollection());

        TableRegistry::getTableLocator()->clear();
    }

    /**
     * test execute
     *
     * @return void
     */
    public function testExecute()
    {
        foreach ($this->tables as $table) {
            $plural = Inflector::camelize($table);
            $singular = Inflector::singularize($plural);

            $this->generatedFiles[] = APP . "Model/Entity/{$singular}.php";
            $this->generatedFiles[] = ROOT . "tests/Fixture/{$plural}Fixture.php";
        }
        $this->exec('bake model all --connection test --no-table --no-test');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);

        $this->assertFileNotExists(
            APP . 'Model/Table/TodoItemsTable.php',
            'Table should not be created as options should be forwarded'
        );
        $this->assertFileNotExists(
            ROOT . 'tests/TestCase/Model/Table/TodoItemsTableTest.php',
            'Table test should not be created as options should be forwarded'
        );
    }
}
