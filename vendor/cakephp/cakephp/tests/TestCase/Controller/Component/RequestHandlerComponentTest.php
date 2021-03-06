<?php
declare(strict_types=1);
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\AjaxView;
use Cake\View\JsonView;
use Cake\View\XmlView;
use TestApp\Controller\Component\RequestHandlerExtComponent;
use TestApp\Controller\RequestHandlerTestController;
use TestApp\View\AppView;
use Zend\Diactoros\Stream;

/**
 * RequestHandlerComponentTest class
 */
class RequestHandlerComponentTest extends TestCase
{
    /**
     * @var RequestHandlerTestController
     */
    public $Controller;

    /**
     * @var \TestApp\Controller\Component\RequestHandlerExtComponent
     */
    public $RequestHandler;

    /**
     * @var ServerRequest
     */
    public $request;

    /**
     * Backup of $_SERVER
     *
     * @var array
     */
    protected $server = [];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
        static::setAppNamespace();
        $this->_init();
    }

    /**
     * init method
     *
     * @return void
     */
    protected function _init(): void
    {
        $request = new ServerRequest(['url' => 'controller_posts/index']);
        /** @var \Cake\Http\Response|\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->getMockBuilder(Response::class)
            ->setMethods(['_sendHeader', 'stop'])
            ->getMock();
        $this->Controller = new RequestHandlerTestController($request, $response);
        $this->RequestHandler = $this->Controller->components()->load(RequestHandlerExtComponent::class);
        $this->request = $request;

        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->setExtensions('json');
            $routes->fallbacks('InflectedRoute');
        });
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Router::reload();
        $_SERVER = $this->server;
        unset($this->RequestHandler, $this->Controller);
    }

    /**
     * Test that the constructor sets the config.
     *
     * @return void
     */
    public function testConstructorConfig(): void
    {
        $config = [
            'viewClassMap' => ['json' => 'MyPlugin.MyJson'],
        ];
        /** @var \Cake\Controller\Controller|\PHPUnit\Framework\MockObject\MockObject $controller */
        $controller = $this->getMockBuilder(Controller::class)
            ->setMethods(['redirect'])
            ->getMock();
        $collection = new ComponentRegistry($controller);
        $requestHandler = new RequestHandlerComponent($collection, $config);
        $this->assertEquals(['json' => 'MyPlugin.MyJson'], $requestHandler->getConfig('viewClassMap'));
    }

    /**
     * testInitializeCallback method
     *
     * @return void
     */
    public function testInitializeCallback(): void
    {
        $this->assertNull($this->RequestHandler->ext);
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('_ext', 'rss'));
        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('rss', $this->RequestHandler->ext);
    }

    /**
     * test that a mapped Accept-type header will set $this->ext correctly.
     *
     * @return void
     */
    public function testInitializeContentTypeSettingExt(): void
    {
        Router::reload();
        $this->Controller->setRequest($this->request->withHeader('Accept', 'application/json'));

        $this->RequestHandler->setExt(null);
        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('json', $this->RequestHandler->getExt());
    }

    /**
     * Test that RequestHandler sets $this->ext when jQuery sends its wonky-ish headers.
     *
     * @return void
     */
    public function testInitializeContentTypeWithjQueryAccept(): void
    {
        Router::reload();
        $this->Controller->setRequest($this->request
            ->withHeader('Accept', 'application/json, application/javascript, */*; q=0.01')
            ->withHeader('X-Requested-With', 'XMLHttpRequest'));
        $this->RequestHandler->setExt(null);
        Router::extensions('json', false);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('json', $this->RequestHandler->ext);
    }

    /**
     * Test that RequestHandler does not set extension to csv for text/plain mimetype
     *
     * @return void
     */
    public function testInitializeContentTypeWithjQueryTextPlainAccept(): void
    {
        Router::reload();
        $this->Controller->setRequest($this->request->withHeader('Accept', 'text/plain, */*; q=0.01'));

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertNull($this->RequestHandler->ext);
    }

    /**
     * Test that RequestHandler sets $this->ext when jQuery sends its wonky-ish headers
     * and the application is configured to handle multiple extensions
     *
     * @return void
     */
    public function testInitializeContentTypeWithjQueryAcceptAndMultiplesExtensions(): void
    {
        Router::reload();
        $this->Controller->setRequest($this->request->withHeader('Accept', 'application/json, application/javascript, */*; q=0.01'));
        $this->RequestHandler->setExt(null);
        Router::extensions(['rss', 'json'], false);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('json', $this->RequestHandler->ext);
    }

    /**
     * Test that RequestHandler does not set $this->ext when multiple accepts are sent.
     *
     * @return void
     */
    public function testInitializeNoContentTypeWithSingleAccept(): void
    {
        Router::reload();
        $_SERVER['HTTP_ACCEPT'] = 'application/json, text/html, */*; q=0.01';
        $this->assertNull($this->RequestHandler->ext);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertNull($this->RequestHandler->ext);
    }

    /**
     * Test that ext is set to the first listed extension with multiple accepted
     * content types.
     * Having multiple types accepted with same weight, means the client lets the
     * server choose the returned content type.
     *
     * @return void
     */
    public function testInitializeNoContentTypeWithMultipleAcceptedTypes(): void
    {
        $this->Controller->setRequest($this->request->withHeader(
            'Accept',
            'application/json, application/javascript, application/xml, */*; q=0.01'
        ));
        $this->RequestHandler->setExt(null);
        Router::extensions(['xml', 'json'], false);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('xml', $this->RequestHandler->ext);

        $this->RequestHandler->setExt(null);
        Router::extensions(['json', 'xml'], false);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('json', $this->RequestHandler->ext);
    }

    /**
     * Test that ext is set to type with highest weight
     *
     * @return void
     */
    public function testInitializeContentTypeWithMultipleAcceptedTypes(): void
    {
        Router::reload();
        $this->Controller->setRequest($this->request->withHeader(
            'Accept',
            'text/csv;q=1.0, application/json;q=0.8, application/xml;q=0.7'
        ));
        $this->RequestHandler->setExt(null);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertEquals('json', $this->RequestHandler->ext);
    }

    /**
     * Test that ext is not set with confusing android accepts headers.
     *
     * @return void
     */
    public function testInitializeAmbiguousAndroidAccepts(): void
    {
        Router::reload();
        $this->request = $this->request->withEnv(
            'HTTP_ACCEPT',
            'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5'
        );
        $this->RequestHandler->setExt(null);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertNull($this->RequestHandler->ext);
    }

    /**
     * Test that the headers sent by firefox are not treated as XML requests.
     *
     * @return void
     */
    public function testInititalizeFirefoxHeaderNotXml(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;image/png,image/jpeg,image/*;q=0.9,*/*;q=0.8';
        Router::extensions(['xml', 'json'], false);

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertNull($this->RequestHandler->ext);
    }

    /**
     * Test that a type mismatch doesn't incorrectly set the ext
     *
     * @return void
     */
    public function testInitializeContentTypeAndExtensionMismatch(): void
    {
        $this->assertNull($this->RequestHandler->ext);
        $extensions = Router::extensions();
        Router::extensions('xml', false);

        /** @var \Cake\Http\ServerRequest|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->getMockBuilder(ServerRequest::class)
            ->setMethods(['accepts'])
            ->getMock();

        $this->Controller->setRequest($request);
        $this->Controller->getRequest()->expects($this->any())
            ->method('accepts')
            ->will($this->returnValue(['application/json']));

        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->assertNull($this->RequestHandler->ext);

        Router::extensions($extensions, false);
    }

    /**
     * testViewClassMap
     *
     * @return void
     */
    public function testViewClassMap(): void
    {
        $this->RequestHandler->setConfig(['viewClassMap' => ['json' => 'CustomJson']]);
        $result = $this->RequestHandler->getConfig('viewClassMap');
        $expected = [
            'json' => 'CustomJson',
            'xml' => 'Xml',
            'ajax' => 'Ajax',
        ];
        $this->assertEquals($expected, $result);
        $this->RequestHandler->setConfig(['viewClassMap' => ['xls' => 'Excel.Excel']]);
        $result = $this->RequestHandler->getConfig('viewClassMap');
        $expected = [
            'json' => 'CustomJson',
            'xml' => 'Xml',
            'ajax' => 'Ajax',
            'xls' => 'Excel.Excel',
        ];
        $this->assertEquals($expected, $result);

        $this->RequestHandler->renderAs($this->Controller, 'json');
        $this->assertEquals('TestApp\View\CustomJsonView', $this->Controller->viewBuilder()->getClassName());
    }

    /**
     * Verify that isAjax is set on the request params for ajax requests
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testIsAjaxParams(): void
    {
        $this->Controller->setRequest($this->request->withHeader('X-Requested-With', 'XMLHttpRequest'));
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->beforeFilter($event);
        $this->RequestHandler->startup($event);
        $this->assertTrue($this->Controller->getRequest()->getParam('isAjax'));
    }

    /**
     * testAutoAjaxLayout method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAutoAjaxLayout(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->setRequest($this->request->withHeader('X-Requested-With', 'XMLHttpRequest'));
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);

        $view = $this->Controller->createView();
        $this->assertInstanceOf(AjaxView::class, $view);
        $this->assertEquals('ajax', $view->getLayout());

        $this->_init();
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('_ext', 'js'));
        $this->RequestHandler->startup($event);
        $this->assertNotEquals(AjaxView::class, $this->Controller->viewBuilder()->getClassName());
    }

    /**
     * @return array
     */
    public function defaultExtensionsProvider()
    {
        return [['html'], ['htm']];
    }

    /**
     * Tests that the default extensions are using the default view.
     *
     * @param string $extension Extension to test.
     * @dataProvider defaultExtensionsProvider
     * @return void
     */
    public function testDefaultExtensions($extension)
    {
        Router::extensions([$extension], false);

        $this->Controller->setRequest($this->request->withParam('_ext', $extension));
        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->RequestHandler->beforeRender(new Event('Controller.beforeRender', $this->Controller));

        $this->assertEquals($extension, $this->RequestHandler->ext);
        $this->assertEquals('text/html', $this->Controller->getResponse()->getType());

        $view = $this->Controller->createView();
        $this->assertInstanceOf(AppView::class, $view);
        $this->assertEmpty($view->getLayoutPath());
        $this->assertEmpty($view->getSubDir());
    }

    /**
     * Tests that the default extensions can be overwritten by the accept header.
     *
     * @param string $extension Extension to test.
     * @dataProvider defaultExtensionsProvider
     * @return void
     */
    public function testDefaultExtensionsOverwrittenByAcceptHeader($extension)
    {
        Router::extensions([$extension], false);

        $request = $this->request->withHeader(
            'Accept',
            'application/xml'
        );
        $this->Controller->setRequest($request->withParam('_ext', $extension));
        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));
        $this->RequestHandler->beforeRender(new Event('Controller.beforeRender', $this->Controller));

        $this->assertEquals('xml', $this->RequestHandler->ext);
        $this->assertEquals('application/xml', $this->Controller->getResponse()->getType());

        $view = $this->Controller->createView();
        $this->assertInstanceOf(XmlView::class, $view);
        $this->assertEquals('xml', $view->getLayoutPath());
        $this->assertEquals('xml', $view->getSubDir());
    }

    /**
     * test custom JsonView class is loaded and correct.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testJsonViewLoaded(): void
    {
        Router::extensions(['json', 'xml', 'ajax'], false);
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('_ext', 'json'));
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $view = $this->Controller->createView();
        $this->assertInstanceOf(JsonView::class, $view);
        $this->assertEquals('json', $view->getLayoutPath());
        $this->assertEquals('json', $view->getSubDir());
    }

    /**
     * test custom XmlView class is loaded and correct.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testXmlViewLoaded(): void
    {
        Router::extensions(['json', 'xml', 'ajax'], false);
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('_ext', 'xml'));
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $view = $this->Controller->createView();
        $this->assertInstanceOf(XmlView::class, $view);
        $this->assertEquals('xml', $view->getLayoutPath());
        $this->assertEquals('xml', $view->getSubDir());
    }

    /**
     * test custom AjaxView class is loaded and correct.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAjaxViewLoaded(): void
    {
        Router::extensions(['json', 'xml', 'ajax'], false);
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('_ext', 'ajax'));
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $view = $this->Controller->createView();
        $this->assertInstanceOf(AjaxView::class, $view);
        $this->assertEquals('ajax', $view->getLayout());
    }

    /**
     * test configured extension but no view class set.
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testNoViewClassExtension(): void
    {
        Router::extensions(['json', 'xml', 'ajax', 'csv'], false);
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('_ext', 'csv'));
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->Controller->getEventManager()->on('Controller.beforeRender', function () {
            return $this->Controller->getResponse();
        });
        $this->Controller->render();
        $this->assertEquals('RequestHandlerTest' . DS . 'csv', $this->Controller->viewBuilder()->getTemplatePath());
        $this->assertEquals('csv', $this->Controller->viewBuilder()->getLayoutPath());
    }

    /**
     * Tests that configured extensions that have no configured mimetype do not silently fallback to HTML.
     *
     * @return void
     * @expectedException \Cake\Http\Exception\NotFoundException
     * @expectedExceptionMessage Invoked extension not recognized/configured: foo
     */
    public function testUnrecognizedExtensionFailure()
    {
        Router::extensions(['json', 'foo'], false);
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('_ext', 'foo'));
        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->Controller->getEventManager()->on('Controller.beforeRender', function () {
            return $this->Controller->getResponse();
        });
        $this->Controller->render();
        $this->assertEquals('RequestHandlerTest' . DS . 'csv', $this->Controller->viewBuilder()->getTemplatePath());
    }

    /**
     * testStartupCallback method
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testStartupCallback(): void
    {
        $event = new Event('Controller.beforeRender', $this->Controller);
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['CONTENT_TYPE'] = 'application/xml';
        $this->Controller->setRequest(new ServerRequest());
        $this->RequestHandler->beforeRender($event);
        $this->assertInternalType('array', $this->Controller->getRequest()->getData());
        $this->assertNotInternalType('object', $this->Controller->getRequest()->getData());
    }

    /**
     * testStartupCallback with charset.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupCallbackCharset(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['CONTENT_TYPE'] = 'application/xml; charset=UTF-8';
        $this->Controller->setRequest(new ServerRequest());
        $this->RequestHandler->startup($event);
        $this->assertInternalType('array', $this->Controller->getRequest()->getData());
        $this->assertNotInternalType('object', $this->Controller->getRequest()->getData());
    }

    /**
     * Test that processing data results in an array.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupProcessDataInvalid(): void
    {
        $this->Controller->setRequest(new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
        ]));

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->assertEquals([], $this->Controller->getRequest()->getData());

        $stream = new Stream('php://memory', 'w');
        $stream->write('"invalid"');
        $this->Controller->setRequest($this->Controller->getRequest()->withBody($stream));
        $this->RequestHandler->startup($event);
        $this->assertEquals(['invalid'], $this->Controller->getRequest()->getData());
    }

    /**
     * Test that processing data results in an array.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupProcessData(): void
    {
        $this->Controller->setRequest(new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
        ]));

        $stream = new Stream('php://memory', 'w');
        $stream->write('{"valid":true}');
        $this->Controller->setRequest($this->Controller->getRequest()->withBody($stream));
        $event = new Event('Controller.startup', $this->Controller);

        $this->RequestHandler->startup($event);
        $this->assertEquals(['valid' => true], $this->Controller->getRequest()->getData());
    }

    /**
     * Test that file handles are ignored as XML data.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupIgnoreFileAsXml(): void
    {
        $this->Controller->setRequest(new ServerRequest([
            'input' => '/dev/random',
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/xml',
            ],
        ]));

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->assertEquals([], $this->Controller->getRequest()->getData());
    }

    /**
     * Test that input xml is parsed
     *
     * @return void
     */
    public function testStartupConvertXmlDataWrapper(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<data>
<article id="1" title="first"></article>
</data>
XML;
        $this->Controller->setRequest((new ServerRequest(['input' => $xml]))
            ->withEnv('REQUEST_METHOD', 'POST')
            ->withEnv('CONTENT_TYPE', 'application/xml'));

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $expected = [
            'data' => [
                'article' => [
                    '@id' => 1,
                    '@title' => 'first',
                ],
            ],
        ];
        $this->assertEquals($expected, $this->Controller->getRequest()->getData());
    }

    /**
     * Test that input xml is parsed
     *
     * @return void
     */
    public function testStartupConvertXmlElements(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<article>
    <id>1</id>
    <title><![CDATA[first]]></title>
</article>
XML;
        $this->Controller->setRequest((new ServerRequest(['input' => $xml]))
            ->withEnv('REQUEST_METHOD', 'POST')
            ->withEnv('CONTENT_TYPE', 'application/xml'));

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $expected = [
            'article' => [
                'id' => 1,
                'title' => 'first',
            ],
        ];
        $this->assertEquals($expected, $this->Controller->getRequest()->getData());
    }

    /**
     * Test that input xml is parsed
     *
     * @return void
     */
    public function testStartupConvertXmlIgnoreEntities(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE item [
  <!ENTITY item "item">
  <!ENTITY item1 "&item;&item;&item;&item;&item;&item;">
  <!ENTITY item2 "&item1;&item1;&item1;&item1;&item1;&item1;&item1;&item1;&item1;">
  <!ENTITY item3 "&item2;&item2;&item2;&item2;&item2;&item2;&item2;&item2;&item2;">
  <!ENTITY item4 "&item3;&item3;&item3;&item3;&item3;&item3;&item3;&item3;&item3;">
  <!ENTITY item5 "&item4;&item4;&item4;&item4;&item4;&item4;&item4;&item4;&item4;">
  <!ENTITY item6 "&item5;&item5;&item5;&item5;&item5;&item5;&item5;&item5;&item5;">
  <!ENTITY item7 "&item6;&item6;&item6;&item6;&item6;&item6;&item6;&item6;&item6;">
  <!ENTITY item8 "&item7;&item7;&item7;&item7;&item7;&item7;&item7;&item7;&item7;">
]>
<item>
  <description>&item8;</description>
</item>
XML;
        $this->Controller->setRequest((new ServerRequest(['input' => $xml]))
            ->withEnv('REQUEST_METHOD', 'POST')
            ->withEnv('CONTENT_TYPE', 'application/xml'));

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->assertEquals([], $this->Controller->getRequest()->getData());
    }

    /**
     * Test that data isn't processed when parsed data already exists.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupSkipDataProcess(): void
    {
        $this->Controller->setRequest(new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
        ]));

        $event = new Event('Controller.startup', $this->Controller);
        $this->RequestHandler->startup($event);
        $this->assertEquals([], $this->Controller->getRequest()->getData());

        $stream = new Stream('php://memory', 'w');
        $stream->write('{"new": "data"}');
        $this->Controller->setRequest($this->Controller->getRequest()
            ->withBody($stream)
            ->withParsedBody(['old' => 'news']));
        $this->RequestHandler->startup($event);
        $this->assertEquals(['old' => 'news'], $this->Controller->getRequest()->getData());
    }

    /**
     * testRenderAs method
     *
     * @return void
     */
    public function testRenderAs(): void
    {
        $this->RequestHandler->renderAs($this->Controller, 'rss');

        $this->Controller->viewBuilder()->setTemplatePath('request_handler_test\\rss');
        $this->RequestHandler->renderAs($this->Controller, 'js');
        $this->assertEquals('request_handler_test' . DS . 'js', $this->Controller->viewBuilder()->getTemplatePath());
    }

    /**
     * test that attachment headers work with renderAs
     *
     * @return void
     */
    public function testRenderAsWithAttachment(): void
    {
        $this->Controller->setRequest($this->request->withHeader('Accept', 'application/xml;q=1.0'));

        $this->RequestHandler->renderAs($this->Controller, 'xml', ['attachment' => 'myfile.xml']);
        $this->assertEquals(XmlView::class, $this->Controller->viewBuilder()->getClassName());
        $this->assertEquals('application/xml', $this->Controller->getResponse()->getType());
        $this->assertEquals('UTF-8', $this->Controller->getResponse()->getCharset());
        $this->assertContains('myfile.xml', $this->Controller->getResponse()->getHeaderLine('Content-Disposition'));
    }

    /**
     * test that respondAs works as expected.
     *
     * @return void
     */
    public function testRespondAs(): void
    {
        $result = $this->RequestHandler->respondAs('json');
        $this->assertTrue($result);
        $this->assertEquals('application/json', $this->Controller->getResponse()->getType());

        $result = $this->RequestHandler->respondAs('text/xml');
        $this->assertTrue($result);
        $this->assertEquals('text/xml', $this->Controller->getResponse()->getType());
    }

    /**
     * test that attachment headers work with respondAs
     *
     * @return void
     */
    public function testRespondAsWithAttachment(): void
    {
        $result = $this->RequestHandler->respondAs('xml', ['attachment' => 'myfile.xml']);
        $this->assertTrue($result);
        $response = $this->Controller->getResponse();
        $this->assertContains('myfile.xml', $response->getHeaderLine('Content-Disposition'));
        $this->assertContains('application/xml', $response->getType());
    }

    /**
     * test that calling renderAs() more than once continues to work.
     *
     * @link #6466
     * @return void
     */
    public function testRenderAsCalledTwice(): void
    {
        $this->Controller->getEventManager()->on('Controller.beforeRender', function (\Cake\Event\EventInterface $e) {
            return $e->getSubject()->getResponse();
        });
        $this->Controller->render();

        $this->RequestHandler->renderAs($this->Controller, 'print');
        $this->assertEquals('RequestHandlerTest' . DS . 'print', $this->Controller->viewBuilder()->getTemplatePath());
        $this->assertEquals('print', $this->Controller->viewBuilder()->getLayoutPath());

        $this->RequestHandler->renderAs($this->Controller, 'js');
        $this->assertEquals('RequestHandlerTest' . DS . 'js', $this->Controller->viewBuilder()->getTemplatePath());
        $this->assertEquals('js', $this->Controller->viewBuilder()->getLayoutPath());
    }

    /**
     * testRequestContentTypes method
     *
     * @return void
     */
    public function testRequestContentTypes(): void
    {
        $this->Controller->setRequest($this->request->withEnv('REQUEST_METHOD', 'GET'));
        $this->assertNull($this->RequestHandler->requestedWith());

        $this->Controller->setRequest($this->request->withEnv('REQUEST_METHOD', 'POST')
            ->withEnv('CONTENT_TYPE', 'application/json'));
        $this->assertEquals('json', $this->RequestHandler->requestedWith());

        $result = $this->RequestHandler->requestedWith(['json', 'xml']);
        $this->assertEquals('json', $result);

        $result = $this->RequestHandler->requestedWith(['rss', 'atom']);
        $this->assertFalse($result);

        $this->Controller->setRequest($this->request
            ->withEnv('REQUEST_METHOD', 'PATCH')
            ->withEnv('CONTENT_TYPE', 'application/json'));
        $this->assertEquals('json', $this->RequestHandler->requestedWith());

        $this->Controller->setRequest($this->request
            ->withEnv('REQUEST_METHOD', 'DELETE')
            ->withEnv('CONTENT_TYPE', 'application/json'));
        $this->assertEquals('json', $this->RequestHandler->requestedWith());

        $this->Controller->setRequest($this->request
            ->withEnv('REQUEST_METHOD', 'POST')
            ->withEnv('CONTENT_TYPE', 'application/json'));
        $result = $this->RequestHandler->requestedWith(['json', 'xml']);
        $this->assertEquals('json', $result);

        $result = $this->RequestHandler->requestedWith(['rss', 'atom']);
        $this->assertFalse($result);

        $this->Controller->setRequest($this->request->withHeader(
            'Accept',
            'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*'
        ));
        $this->assertTrue($this->RequestHandler->prefers('xml'));
        $this->assertFalse($this->RequestHandler->prefers('atom'));
        $this->assertFalse($this->RequestHandler->prefers('rss'));

        $this->Controller->setRequest($this->request->withHeader(
            'Accept',
            'application/atom+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*'
        ));
        $this->assertTrue($this->RequestHandler->prefers('atom'));
        $this->assertFalse($this->RequestHandler->prefers('rss'));

        $this->Controller->setRequest($this->request->withHeader(
            'Accept',
            'application/rss+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*'
        ));
        $this->assertFalse($this->RequestHandler->prefers('atom'));
        $this->assertTrue($this->RequestHandler->prefers('rss'));
    }

    /**
     * test that map alias converts aliases to content types.
     *
     * @return void
     */
    public function testMapAlias(): void
    {
        $result = $this->RequestHandler->mapAlias('xml');
        $this->assertEquals('application/xml', $result);

        $result = $this->RequestHandler->mapAlias('text/html');
        $this->assertNull($result);

        $result = $this->RequestHandler->mapAlias('wap');
        $this->assertEquals('text/vnd.wap.wml', $result);

        $result = $this->RequestHandler->mapAlias(['xml', 'js', 'json']);
        $expected = ['application/xml', 'application/javascript', 'application/json'];
        $this->assertEquals($expected, $result);
    }

    /**
     * test accepts() on the component
     *
     * @return void
     */
    public function testAccepts(): void
    {
        $this->Controller->setRequest($this->request->withHeader(
            'Accept',
            'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5'
        ));
        $this->assertTrue($this->RequestHandler->accepts(['js', 'xml', 'html']));
        $this->assertFalse($this->RequestHandler->accepts(['gif', 'jpeg', 'foo']));

        $this->Controller->setRequest($this->request->withHeader('Accept', '*/*;q=0.5'));
        $this->assertFalse($this->RequestHandler->accepts('rss'));
    }

    /**
     * test accepts and prefers methods.
     *
     * @return void
     */
    public function testPrefers(): void
    {
        $this->Controller->setRequest($this->request->withHeader(
            'Accept',
            'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*'
        ));
        $this->assertNotEquals('rss', $this->RequestHandler->prefers());

        $this->RequestHandler->setExt('rss');
        $this->assertEquals('rss', $this->RequestHandler->prefers());
        $this->assertFalse($this->RequestHandler->prefers('xml'));
        $this->assertEquals('xml', $this->RequestHandler->prefers(['js', 'xml', 'xhtml']));
        $this->assertFalse($this->RequestHandler->prefers(['red', 'blue']));
        $this->assertEquals('xhtml', $this->RequestHandler->prefers(['js', 'json', 'xhtml']));
        $this->assertTrue($this->RequestHandler->prefers(['rss']), 'Should return true if input matches ext.');
        $this->assertFalse($this->RequestHandler->prefers(['html']), 'No match with ext, return false.');

        $this->_init();
        $this->Controller->setRequest($this->request->withHeader(
            'Accept',
            'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5'
        ));
        $this->assertEquals('xml', $this->RequestHandler->prefers());

        $this->Controller->setRequest($this->request->withHeader('Accept', '*/*;q=0.5'));
        $this->assertEquals('html', $this->RequestHandler->prefers());
        $this->assertFalse($this->RequestHandler->prefers('rss'));

        $this->Controller->setRequest($this->request->withEnv('HTTP_ACCEPT', ''));
        $this->RequestHandler->setExt('json');
        $this->assertFalse($this->RequestHandler->prefers('xml'));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testCheckNotModifiedByEtagStar(): void
    {
        $response = new Response();
        $response = $response->withEtag('something')
            ->withHeader('Content-Type', 'text/plain')
            ->withStringBody('keeper');
        $this->Controller->setResponse($response);
        $this->Controller->setRequest($this->request->withHeader('If-None-Match', '*'));

        $event = new Event('Controller.beforeRender', $this->Controller);
        $requestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->assertNull($requestHandler->beforeRender($event));
        $this->assertTrue($event->isStopped());
        $this->assertEquals(304, $this->Controller->getResponse()->getStatusCode());
        $this->assertEquals('', (string)$this->Controller->getResponse()->getBody());
        $this->assertFalse($this->Controller->getResponse()->hasHeader('Content-Type'), 'header should not be removed.');
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender
     */
    public function testCheckNotModifiedByEtagExact(): void
    {
        $response = new Response();
        $response = $response->withEtag('something', true)
            ->withHeader('Content-Type', 'text/plain')
            ->withStringBody('keeper');
        $this->Controller->setResponse($response);

        $this->Controller->setRequest($this->request->withHeader('If-None-Match', 'W/"something", "other"'));
        $event = new Event('Controller.beforeRender', $this->Controller);

        $requestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->assertNull($requestHandler->beforeRender($event));
        $this->assertTrue($event->isStopped());
        $this->assertEquals(304, $this->Controller->getResponse()->getStatusCode());
        $this->assertEquals('', (string)$this->Controller->getResponse()->getBody());
        $this->assertFalse($this->Controller->getResponse()->hasHeader('Content-Type'));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testCheckNotModifiedByEtagAndTime(): void
    {
        $this->Controller->setRequest($this->request
            ->withHeader('If-None-Match', 'W/"something", "other"')
            ->withHeader('If-Modified-Since', '2012-01-01 00:00:00'));

        $response = new Response();
        $response = $response->withEtag('something', true)
            ->withHeader('Content-type', 'text/plain')
            ->withStringBody('should be removed')
            ->withModified('2012-01-01 00:00:00');
        $this->Controller->setResponse($response);

        $event = new Event('Controller.beforeRender', $this->Controller);
        $requestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->assertNull($requestHandler->beforeRender($event));
        $this->assertTrue($event->isStopped());

        $this->assertEquals(304, $this->Controller->getResponse()->getStatusCode());
        $this->assertEquals('', (string)$this->Controller->getResponse()->getBody());
        $this->assertFalse($this->Controller->getResponse()->hasHeader('Content-type'));
    }

    /**
     * Test checkNotModified method
     *
     * @return void
     * @triggers Controller.beforeRender $this->Controller
     */
    public function testCheckNotModifiedNoInfo(): void
    {
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['notModified', 'stop'])
            ->getMock();
        $response->expects($this->never())->method('notModified');
        $this->Controller->setResponse($response);

        $event = new Event('Controller.beforeRender', $this->Controller);
        $requestHandler = new RequestHandlerComponent($this->Controller->components());
        $this->assertNull($requestHandler->beforeRender($event));
    }

    /**
     * Test default options in construction
     *
     * @return void
     */
    public function testConstructDefaultOptions(): void
    {
        $requestHandler = new RequestHandlerComponent($this->Controller->components());
        $viewClass = $requestHandler->getConfig('viewClassMap');
        $expected = [
            'json' => 'Json',
            'xml' => 'Xml',
            'ajax' => 'Ajax',
        ];
        $this->assertEquals($expected, $viewClass);

        $inputs = $requestHandler->getConfig('inputTypeMap');
        $this->assertArrayHasKey('json', $inputs);
        $this->assertArrayHasKey('xml', $inputs);
    }

    /**
     * Test options in constructor replace defaults
     *
     * @return void
     */
    public function testConstructReplaceOptions(): void
    {
        $requestHandler = new RequestHandlerComponent(
            $this->Controller->components(),
            [
                'viewClassMap' => ['json' => 'Json'],
                'inputTypeMap' => ['json' => ['json_decode', true]],
            ]
        );
        $viewClass = $requestHandler->getConfig('viewClassMap');
        $expected = [
            'json' => 'Json',
        ];
        $this->assertEquals($expected, $viewClass);

        $inputs = $requestHandler->getConfig('inputTypeMap');
        $this->assertArrayHasKey('json', $inputs);
        $this->assertCount(1, $inputs);
    }

    /**
     * test beforeRender() doesn't override response type set in controller action
     *
     * @return void
     */
    public function testBeforeRender(): void
    {
        $this->Controller->set_response_type();
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $this->assertEquals('text/plain', $this->Controller->getResponse()->getType());
    }

    /**
     * tests beforeRender automatically uses renderAs when a supported extension is found
     *
     * @return void
     */
    public function testBeforeRenderAutoRenderAs(): void
    {
        $this->Controller->setRequest($this->request->withParam('_ext', 'csv'));
        $this->RequestHandler->startup(new Event('Controller.startup', $this->Controller));

        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->RequestHandler->beforeRender($event);
        $this->assertEquals('text/csv', $this->Controller->getResponse()->getType());
    }
}
