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
 * @since         1.1.4
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Bake\Test\TestCase\Command;

use Bake\Test\TestCase\TestCase;
use Cake\Console\Command;
use Cake\Core\Plugin;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

/**
 * ModelCommand Association detection test
 */
class ModelCommandAssociationDetectionTest extends TestCase
{
    /**
     * fixtures
     *
     * Don't sort this list alphabetically - otherwise there are table constraints
     * which fail when using postgres
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Bake.Categories',
        'plugin.Bake.CategoriesProducts',
        'plugin.Bake.OldProducts',
        'plugin.Bake.Products',
        'plugin.Bake.ProductVersions',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->_compareBasePath = Plugin::path('Bake') . 'tests' . DS . 'comparisons' . DS . 'Model' . DS;
        $this->setAppNamespace('Bake\Test\App');
        $this->useCommandRunner();

        TableRegistry::getTableLocator()->clear();
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
     * Compare bake table result with static comparison file
     *
     * @return void
     */
    protected function _compareBakeTableResult($name, $comparisonFile)
    {
        $this->generatedFiles = [
            APP . "Model/Table/{$name}Table.php",
        ];
        $this->exec("bake model --no-entity --no-fixture --no-test --connection test {$name}");

        $this->assertExitCode(Command::CODE_SUCCESS);
        $contents = file_get_contents($this->generatedFiles[0]);
        $this->assertSameAsFile($comparisonFile . '.php', $contents);
    }

    /**
     * test checking if associations where built correctly for categories.
     *
     * @return void
     */
    public function testBakeAssociationDetectionCategoriesTable()
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf($driver instanceof Mysql, 'Incompatible with mysql');
        $this->_compareBakeTableResult('Categories', __FUNCTION__);
    }

    /**
     * test checking if associations where built correctly for categories.
     *
     * @return void
     */
    public function testBakeAssociationDetectionCategoriesTableSigned()
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf($driver instanceof Sqlite, 'Incompatible with sqlite');
        $this->skipIf($driver instanceof Postgres, 'Incompatible with postgres');
        $this->skipIf($driver instanceof Sqlserver, 'Incompatible with sqlserver');
        $this->_compareBakeTableResult('Categories', __FUNCTION__);
    }

    /**
     * test checking if associations where built correctly for categories_products.
     *
     * @return void
     */
    public function testBakeAssociationDetectionCategoriesProductsTable()
    {
        $this->_compareBakeTableResult('CategoriesProducts', __FUNCTION__);
    }

    /**
     * test checking if associations where built correctly for old_products.
     *
     * @return void
     */
    public function testBakeAssociationDetectionOldProductsTable()
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf($driver instanceof Mysql, 'Incompatible with mysql');
        $this->_compareBakeTableResult('OldProducts', __FUNCTION__);
    }

    /**
     * test checking if associations where built correctly for old_products.
     *
     * @return void
     */
    public function testBakeAssociationDetectionOldProductsTableSigned()
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf($driver instanceof Sqlite, 'Incompatible with sqlite');
        $this->skipIf($driver instanceof Postgres, 'Incompatible with postgres');
        $this->skipIf($driver instanceof Sqlserver, 'Incompatible with sqlserver');
        $this->_compareBakeTableResult('OldProducts', __FUNCTION__);
    }

    /**
     * test checking if associations where built correctly for product_versions.
     *
     * @return void
     */
    public function testBakeAssociationDetectionProductVersionsTable()
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf($driver instanceof Mysql, 'Incompatible with mysql');
        $this->_compareBakeTableResult('ProductVersions', __FUNCTION__);
    }

    /**
     * test checking if associations where built correctly for product_versions.
     *
     * @return void
     */
    public function testBakeAssociationDetectionProductVersionsTableSigned()
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf($driver instanceof Sqlite, 'Incompatible with sqlite');
        $this->skipIf($driver instanceof Postgres, 'Incompatible with postgres');
        $this->skipIf($driver instanceof Sqlserver, 'Incompatible with sqlserver');
        $this->_compareBakeTableResult('ProductVersions', __FUNCTION__);
    }
}
