<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\LadiesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\LadiesTable Test Case
 */
class LadiesTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\LadiesTable
     */
    public $Ladies;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
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
        $config = TableRegistry::exists('Ladies') ? [] : ['className' => 'App\Model\Table\LadiesTable'];
        $this->Ladies = TableRegistry::get('Ladies', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Ladies);

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
}
