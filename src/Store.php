<?php

/**
 * If a company has multiple UK store locations add the ability to select, add, update, delete new store locations into a database and search for the closest by postcode or latitude and longitude
 * @author Adam Binnersley
 * @link https://www.adambinnersley.co.uk
 */
namespace StoreLocator;

use DBAL\Database;
use Codescheme\Ukpostcodes\Postcode;

class Store{
    protected static $db;
    protected static $geocode;

    protected $test_centre_table;
    
    /**
     * Constructor to pass an instance of the database
     * @param Database $db This should be in instance of the database connection
     */
    public function __construct(Database $db){
        self::$db = $db;
        self::$geocode = new Postcode();
    }
    
    /**
     * Sets the table name where the class looks for the sores in the database
     * @param string $table This should be the table where the stores are located
     * @return $this
     */
    public function setStoreDBTableName($table){
        $this->test_centre_table = $table;
        return $this;
    }
    
    /**
     * Returns the table where the stores are located in the database
     * @return string This should be the table where the stores are located in the database
     */
    public function getStoreDBTableName(){
        return $this->test_centre_table;
    }
    
    /**
     * Get the store information for a single location based on its unique ID
     * @param inst $id This should be the stores unique ID in the database
     * @return array|false If the store exists it will be returned as an array else will return false
     */
    public function getStoreByID($id){
        return self::$db->select($this->getStoreDBTableName(), array('id' => intval($id)));
    }
    
    /**
     * Returns a list of all of the stores in the database
     * @return array|false If stores exist in the database they will all be returned else if none exist will return false
     */
    public function getStore(){
        return self::$db->selectAll($this->getStoreDBTableName());
    }
    
    /**
     * Adds a store to the database
     * @param string $postcode This should be the postcode of the store so Geocoding can be done with the approx location
     * @param array $information This should be an array with the stores information in with variable/field names and values
     * @return boolean If the store is added successfully will return true else returns false
     */
    public function addStore($postcode, $information = array()){
        $location = self::$geocode->postcodeLookup($postcode);
        if($location->status == 200){
            $variables = array_merge(array('lat' => $location->result->latitude, 'lng' => $location->result->longitude), $information);
            return self::$db->insert($this->getStoreDBTableName(), $variables);
        }
        return false;
    }

    /**
     * Updates a store and its information in the database
     * @param int $id The should be the unique ID assigned in the database
     * @param string $postcode This should be the postcode of the store just incase it has moved and needs its location updating
     * @param array $information This should be any information you wish to update, unchanged variables can be added but will have no impact
     * @return boolean If the store information is updated will return true else returns false
     */
    public function updateStore($id, $postcode, $information = array()){
        $location = self::$geocode->postcodeLookup($postcode);
        if($location->status == 200){
            $variables = array_merge(array('lat' => $location->result->latitude, 'lng' => $location->result->longitude), $information);
            return self::$db->update($this->getStoreDBTableName(), $variables, array('id' => intval($id)));
        }
        return false;
    }
    
    /**
     * Deletes a store an its information from the database
     * @param int $id This should be the unique store id to delete from the database
     * @return boolean If the store is deleted will return true else returns false
     */
    public function deleteStore($id){
        return self::$db->delete($this->getStoreDBTableName(), array('id' => intval($id)), 1);
    }
    
    /**
     * Finds of the closest stores to the postcode given if any exist within the maximum distance
     * @param string $postcode This should be the postcode you are searching for the closest store from
     * @param int $maxdistance The maximum distance you wish to search for a store in miles
     * @param int $limit The maximum amount of results you want to display
     * @return array|boolean If results are available will return them as an array else will return false if no results are found or if postcode lookup fails
     */
    public function findClosest($postcode, $maxdistance = 50, $limit = 5){
        $location = self::$geocode->postcodeLookup($postcode);
        if($location->status == 200){
            return $this->findClosestByLatLng($location->result->latitude, $location->result->longitude, intval($maxdistance), intval($limit));
        }
        return false;
    }
    
    /**
     * Finds the closest stores to the latitude an longitude given as long as results exist within the maximum distance set 
     * @param float(10,6) $lat This should be the latitude you wish to search for the closest store from
     * @param float(10,6) $lng This should be the longitude you wish to search for the closest store from
     * @param int $maxdistance This is the maximum distance you are willing to search for in the database
     * @param int $limit This is the maximum amount of results to display
     * @return array|boolean If results are available will return them as an array else will return false if no results are found
     */
    public function findClosestByLatLng($lat, $lng, $maxdistance = 50, $limit = 5){
        return self::$db->query("SELECT `{$this->getStoreDBTableName()}`.*, (3959 * acos(cos(radians('{$lat}')) * cos(radians(lat)) * cos(radians(lng) - radians('{$lng}')) + sin(radians('{$lat}')) * sin(radians(lat)))) AS `distance` FROM `{$this->getStoreDBTableName()}` HAVING `distance` < ? ORDER BY `distance` LIMIT ?;", array(intval($limit), intval($maxdistance)));
    }
}
