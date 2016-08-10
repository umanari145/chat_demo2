<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\LogintimesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\LogintimesTable Test Case
 */
class LogintimesTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\LogintimesTable
     */
    public $Logintimes;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.logintimes',
        'app.ladies'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Logintimes') ? [] : ['className' => 'App\Model\Table\LogintimesTable'];
        $this->Logintimes = TableRegistry::get('Logintimes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Logintimes);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
