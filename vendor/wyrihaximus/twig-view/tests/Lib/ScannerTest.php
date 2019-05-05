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

namespace WyriHaximus\CakePHP\Tests\TwigView\Lib;

use Cake\Routing\Router;
use WyriHaximus\CakePHP\Tests\TwigView\TestCase;
use WyriHaximus\TwigView\Lib\Scanner;

/**
 * Class ScannerTest.
 * @package WyriHaximus\CakePHP\Tests\TwigView\Lib\Twig
 */
class ScannerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Router::reload();
        $this->loadPlugins(['TestTwigView']);
    }

    public function tearDown()
    {
        $this->removePlugins(['TestTwigView']);

        parent::tearDown();
    }

    public function testAll()
    {
        $this->assertSame([
            'APP' => [
                PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'templates' . DS . 'Blog' . DS . 'index.twig',
                PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'templates' . DS . 'element' . DS . 'element.twig',
                PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'templates' . DS . 'exception.twig',
                PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'templates' . DS . 'layout.twig',
                PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'templates' . DS . 'layout' . DS . 'layout.twig',
                PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'templates' . DS . 'syntaxerror.twig',
            ],
            //'Bake' => Scanner::plugin('Bake'),
            'TestTwigView' => [
                PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestTwigView' . DS . 'templates' . DS . 'Controller' . DS . 'Component' . DS . 'magic.twig',
                PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestTwigView' . DS . 'templates' . DS . 'Controller' . DS . 'index.twig',
                PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestTwigView' . DS . 'templates' . DS . 'Controller' . DS . 'view.twig',
                PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestTwigView' . DS . 'templates' . DS . 'twig.twig',
            ],
        ], Scanner::all());
    }

    public function testPlugin()
    {
        $this->assertSame([
            PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestTwigView' . DS . 'templates' . DS . 'Controller' . DS . 'Component' . DS . 'magic.twig',
            PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestTwigView' . DS . 'templates' . DS . 'Controller' . DS . 'index.twig',
            PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestTwigView' . DS . 'templates' . DS . 'Controller' . DS . 'view.twig',
            PLUGIN_REPO_ROOT . 'tests' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestTwigView' . DS . 'templates' . DS . 'twig.twig',
        ], Scanner::plugin('TestTwigView'));
    }
}
