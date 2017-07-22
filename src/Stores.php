<?php

/**
 * Description of TestCentre
 *
 * @author Adams
 */
namespace StoreFinder;

use DBAL\Database;

class Stores{
    protected static $db;
    
    protected $test_centre_table;
    
    /**
     * 
     * @param Database $db
     */
    public function __construct(Database $db){
        self::$db = $db;
    }
    
    /**
     * Sets the 
     * @param type $table
     * @return $this
     */
    public function setStoreDBTableName($table){
        $this->test_centre_table = $table;
        return $this;
    }
    
    /**
     * 
     * @return type
     */
    public function getStoreDBTableName(){
        return $this->test_centre_table;
    }

    public function addStore($name, $additional = []){
        self::$db->insert($this->getStoreDBTableName());
    }
    
    /**
     * Get the Test Centre information for a single location based on its unique ID
     * @param inst $id This should be the tests centres unique ID in the database
     * @return array|false If the test centre exists it will be returned as an array else will return false
     */
    public function getStoreByID($id){
        return self::$db->select($this->getStoreDBTableName(), array('id' => intval($id)));
    }
    
    /**
     * Returns a list of all of the test centres in the database
     * @return array|false If test centres exist in the database they will all be returned else if none exist will return false
     */
    public function getStore(){
        return self::$db->selectAll($this->getStoreDBTableName());
    }

    
    public function updateStore($id){
        return self::$db->update($this->getStoreDBTableName());
    }
    
    public function findClosestStore(){
        return self::$db->query();
    }
}
