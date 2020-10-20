<?php

namespace StoreLocator\Tests;

use PHPUnit\Framework\TestCase;
use DBAL\Database;
use StoreLocator\Store;

class StoreTest extends TestCase
{
    
    protected $database;
    protected $store;
    
    protected function setUp(): void
    {
        $this->database = new Database($GLOBALS['hostname'], $GLOBALS['username'], $GLOBALS['password'], $GLOBALS['database']);
        if (!$this->database->isConnected()) {
            $this->markTestSkipped(
                'No local database connection is available'
            );
        }
        $this->database->query(file_get_contents(dirname(dirname(__FILE__)).'/database/mysql_database.sql'));
        $this->store = new Store($this->database);
        $this->store->setStoreDBTableName($GLOBALS['database']);
        $this->database->truncate($this->store->getStoreDBTableName());
        $this->database->query(file_get_contents(dirname(__FILE__).'/sample_data/example_data.sql'));
    }
    
    protected function tearDown(): void
    {
        $this->database = null;
        $this->store = null;
    }
    
    /**
     * @covers StoreLocator\Store::__construct
     * @covers StoreLocator\Store::setStoreDBTableName
     * @covers StoreLocator\Store::getStoreDBTableName
     */
    public function testSetTable()
    {
        $this->assertEquals('stores', $this->store->getStoreDBTableName());
        $this->store->setStoreDBTableName(5464);
        $this->assertEquals('stores', $this->store->getStoreDBTableName());
        $this->store->setStoreDBTableName(false);
        $this->assertEquals('stores', $this->store->getStoreDBTableName());
        $this->store->setStoreDBTableName('my_stores');
        $this->assertEquals('my_stores', $this->store->getStoreDBTableName());
    }
    
    /**
     * @covers StoreLocator\Store::__construct
     * @covers StoreLocator\Store::setStoreDBTableName
     * @covers StoreLocator\Store::getStoreDBTableName
     * @covers StoreLocator\Store::getStores
     */
    public function testListStores()
    {
        $this->assertEquals(10, count($this->store->getStores()));
        $this->assertEquals('Aberdeen', $this->store->getStores()[0]['name']);
        $this->assertArrayHasKey('lat', $this->store->getStores()[4]);
    }
    
    /**
     * @covers StoreLocator\Store::__construct
     * @covers StoreLocator\Store::setStoreDBTableName
     * @covers StoreLocator\Store::getStoreDBTableName
     * @covers StoreLocator\Store::getStoreByID
     */
    public function testGetStoreByID()
    {
        $this->assertArrayHasKey('name', $this->store->getStoreByID(7));
        $this->assertFalse($this->store->getStoreByID(206));
        $this->assertEquals('Southampton', $this->store->getStoreByID(5)['name']);
    }
    
    /**
     * @covers StoreLocator\Store::__construct
     * @covers StoreLocator\Store::setStoreDBTableName
     * @covers StoreLocator\Store::getStoreDBTableName
     * @covers StoreLocator\Store::getStoreByName
     */
    public function testGetStoreByName()
    {
        $this->assertFalse($this->store->getStoreByName('SomeRandomPlace'));
        $this->assertArrayHasKey('address', $this->store->getStoreByName('Plymouth'));
        $this->assertEquals('HU3 1TY', $this->store->getStoreByName('Hull')['postcode']);
    }
    
    /**
     * @covers StoreLocator\Store::__construct
     * @covers StoreLocator\Store::setStoreDBTableName
     * @covers StoreLocator\Store::getStoreDBTableName
     * @covers StoreLocator\Store::findClosest
     * @covers StoreLocator\Store::findClosestByLatLng
     */
    public function testFindClosest()
    {
        $this->assertFalse($this->store->findClosest('PostcodeNotValid'));
        $this->assertArrayHasKey('address', $this->store->findClosest('WF8 4PQ', 100)[0]);
        $this->assertEquals(4, count($this->store->findClosest('WF8 4PQ', 100)));
    }
    
    /**
     * @covers StoreLocator\Store::__construct
     * @covers StoreLocator\Store::setStoreDBTableName
     * @covers StoreLocator\Store::getStoreDBTableName
     * @covers StoreLocator\Store::addStore
     */
    public function testAddStore()
    {
        $this->assertFalse($this->store->addStore('PostcoeNotValid', ['name' => 'Postcode Not Valid', 'address' => '42 Some Random Place, Eureka, EH6 54ER']));
        $this->assertTrue($this->store->addStore('NR6 5DT', ['name' => 'Norwich', 'address' => 'Drayton High Rd, Norwich, NR6 5DT']));
        $this->assertTrue($this->store->addStore('CT1 1DG', ['name' => 'Canterbury']));
    }
    
    /**
     * @covers StoreLocator\Store::__construct
     * @covers StoreLocator\Store::setStoreDBTableName
     * @covers StoreLocator\Store::getStoreDBTableName
     * @covers StoreLocator\Store::updateStore
     */
    public function testUpdateStore()
    {
        $this->assertTrue($this->store->updateStore(10, 'CT1 1DG', ['name' => 'Canterbury', 'address' => 'Sturry Rd, Canterbury, CT1 1DG']));
        $this->assertFalse($this->store->updateStore(10, 'CT1 1DG', ['name' => 'Canterbury', 'address' => 'Sturry Rd, Canterbury, CT1 1DG']));
        $this->assertFalse($this->store->updateStore(15, 'CT1 1DG', ['name' => 'IDDoesntExist', 'address' => 'Some address']));
        $this->assertFalse($this->store->updateStore('sdfsdfsfdsdf', 'CT1 1DG', ['name' => 'IDDoesntExist', 'address' => 'Some address']));
        $this->assertFalse($this->store->updateStore(false, 'CT1 1DG', ['name' => 'IDDoesntExist', 'address' => 'Some address']));
    }
    
    /**
     * @covers StoreLocator\Store::__construct
     * @covers StoreLocator\Store::setStoreDBTableName
     * @covers StoreLocator\Store::getStoreDBTableName
     * @covers StoreLocator\Store::deleteStore
     * @covers StoreLocator\Store::getStoreByID
     */
    public function testDeleteStore()
    {
        $this->assertTrue($this->store->deleteStore(9));
        $this->assertFalse($this->store->getStoreByID(9));
        $this->assertFalse($this->store->deleteStore(9));
    }
}
