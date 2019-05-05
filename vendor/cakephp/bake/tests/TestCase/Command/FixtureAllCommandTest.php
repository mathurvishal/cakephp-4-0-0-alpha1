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
use Bake\Utility\SubsetSchemaCollection;
use Cake\Console\Command;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;

/**
 * FixtureAllCommand Test
 *
 */
class FixtureAllCommandTest extends TestCase
{
    /**
     * fixtures
     *
     * @var array
     */
    public $fixtures = [
        'core.Articles',
        'core.Comments',
    ];

    /**
     * @var array
     */
    protected $tables = ['articles', 'comments'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->_compareBasePath = Plugin::path('Bake') . 'tests' . DS . 'comparisons' . DS . 'Fixture' . DS;
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
    }

    /**
     * test that execute runs all() when args[0] = all
     *
     * @return void
     */
    public function testMainIntoAll()
    {
        $this->generatedFiles = [
             ROOT . 'tests/Fixture/ArticlesFixture.php',
             ROOT . 'tests/Fixture/CommentsFixture.php',
        ];
        $this->exec('bake fixture all --connection test');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertFileContains('class ArticlesFixture', $this->generatedFiles[0]);
        $this->assertFileContains('class CommentsFixture', $this->generatedFiles[1]);
    }

    /**
     * test using all() with -count and -records
     *
     * @return void
     */
    public function testAllWithCountAndRecordsFlags()
    {
        $this->generatedFiles = [
             ROOT . 'tests/Fixture/ArticlesFixture.php',
             ROOT . 'tests/Fixture/CommentsFixture.php',
        ];
        $this->exec('bake fixture all --connection test --count 10 --records');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertFileContains("'title' => 'Third Article'", $this->generatedFiles[0]);
        $this->assertFileContains(
            "'comment' => 'First Comment for First Article'",
            $this->generatedFiles[1]
        );
    }

    /**
     * test using all() with -schema
     *
     * @return void
     */
    public function testAllWithSchemaImport()
    {
        $this->generatedFiles = [
             ROOT . 'tests/Fixture/ArticlesFixture.php',
             ROOT . 'tests/Fixture/CommentsFixture.php',
        ];
        $this->exec('bake fixture all --connection test --schema');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);
        $this->assertFileContains(
            "public \$import = ['table' => 'articles'",
            $this->generatedFiles[0]
        );
        $this->assertFileContains(
            "public \$import = ['table' => 'comments'",
            $this->generatedFiles[1]
        );
    }
}
