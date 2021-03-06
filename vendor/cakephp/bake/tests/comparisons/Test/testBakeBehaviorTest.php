<?php
namespace Bake\Test\App\Test\TestCase\Model\Behavior;

use Bake\Test\App\Model\Behavior\ExampleBehavior;
use Cake\TestSuite\TestCase;

/**
 * Bake\Test\App\Model\Behavior\ExampleBehavior Test Case
 */
class ExampleBehaviorTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Bake\Test\App\Model\Behavior\ExampleBehavior
     */
    public $Example;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Example = new ExampleBehavior();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Example);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
