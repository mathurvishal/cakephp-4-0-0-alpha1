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
 * @since         0.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Bake\Test\TestCase\Command;

use Bake\Test\App\Model\Table\BakeArticlesTable;
use Bake\Test\TestCase\TestCase;
use Cake\Console\Command;
use Cake\Core\Plugin;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * ControllerAllCommand test
 *
 */
class ControllerAllCommandTest extends TestCase
{
    /**
     * fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Bake.BakeArticles',
        'plugin.Bake.BakeComments',
    ];

    /**
     * @var array
     */
    protected $tables = ['bake_articles', 'bake_comments'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->_compareBasePath = Plugin::path('Bake') . 'tests' . DS . 'comparisons' . DS . 'Controller' . DS;
        $this->useCommandRunner();
        $this->setAppNamespace('Bake\Test\App');

        TableRegistry::getTableLocator()->get('BakeArticles', [
            'className' => BakeArticlesTable::class,
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
     * test that execute runs all when the first arg == all
     *
     * @return void
     */
    public function testExecute()
    {
        foreach ($this->tables as $table) {
            $plural = Inflector::camelize($table);

            $this->generatedFiles[] = APP . "Controller/{$plural}Controller.php";
        }
        $this->exec('bake controller all --connection test --no-test');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertFilesExist($this->generatedFiles);

        $this->assertFileNotExists(
            ROOT . 'tests/TestCase/Controller/BakeArticlesControllerTest.php',
            'Test should not be created as options should be forwarded'
        );
    }
}
