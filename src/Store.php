<?php
/**
 * If a company has multiple UK store locations adds the ability to select, add, update, delete new store locations into a database and search for the closest by postcode or latitude and longitude
 * @author Adam Binnersley
 * @link https://www.adambinnersley.co.uk
 */

namespace StoreLocator;

use DBAL\Database;
use Jabranr\PostcodesIO\PostcodesIO;

class Store{
    protected $db;
    protected $geocode;

    protected $store_table;
    
    /**
     * Constructor to pass an instance of the database
     * @param Database $db This should be in instance of the database connection
     */
    public function __construct(Database $db) {
        $this->db = $db;
        $this->geocode = new PostcodesIO();
    }
    
    /**
     * Sets the table name where the class looks for the sores in the database
     * @param string $table This should be the table where the stores are located
     * @return $this
     */
    public function setStoreDBTableName($table) {
        if(is_string($table) && !empty(trim($table))) {
            $this->store_table = $table;
        }
        return $this;
    }
    
    /**
     * Returns the table where the stores are located in the database
     * @return string This should be the table where the stores are located in the database
     */
    public function getStoreDBTableName() {
        return $this->store_table;
    }
    
    /**
     * Get the store information for a single location based on its unique ID
     * @param inst $id This should be the stores unique ID in the database
     * @return array|false If the store exists it will be returned as an array else will return false
     */
    public function getStoreByID($id) {
        return $this->db->select($this->getStoreDBTableName(), ['id' => intval($id)]);
    }
    
    /**
     * Search for stores by name or partial name
     * @param string $search this should be the name or part of the name of the store you are looking for
     * @return array|boolean If any stores exist they will be returned as an array else will return false
     */
    public function getStoreByName($search) {
        $stores = $this->db->query("SELECT * FROM `{$this->getStoreDBTableName()}` WHERE `name` LIKE ?;", ['%'.$search.'%']);
        if(!empty($stores)) {
            return count($stores) > 1 ? $stores : $stores[0];
        }
        return false;
    }
    
    /**
     * Returns a list of all of the stores in the database
     * @return array|false If stores exist in the database they will all be returned else if none exist will return false
     */
    public function getStores() {
        return $this->db->selectAll($this->getStoreDBTableName());
    }
    
    /**
     * Adds a store to the database
     * @param string $postcode This should be the postcode of the store so Geocoding can be done with the approx location
     * @param array $information This should be an array with the stores information in with variable/field names and values
     * @return boolean If the store is added successfully will return true else returns false
     */
    public function addStore($postcode, $information = []) {
        if(is_string($postcode) && !empty(trim($postcode)) && is_string($information['name'])){
            $location = $this->geocode->query($postcode);
            if($location->status == 200 && !empty($location->result)) {
                $variables = array_merge(['lat' => $location->result->latitude, 'lng' => $location->result->longitude], $information, ['postcode' => strtoupper($postcode)]);
                return $this->db->insert($this->getStoreDBTableName(), $variables);
            }
        }
        return false;
    }

    /**
     * Updates a store and its information in the database
     * @param int $id The should be the unique ID assigned in the database
     * @param string $postcode This should be the postcode of the store if it has moved and needs its location updating
     * @param array $information This should be any information you wish to update, unchanged variables can be added but will have no impact
     * @return boolean If the store information is updated will return true else returns false
     */
    public function updateStore($id, $postcode, $information = []) {
        if(is_numeric($id) && is_string($postcode) && !empty(trim($postcode)) && is_array($information) && !empty($information)) {
            $location = $this->geocode->query($postcode);
            if($location->status == 200 && !empty($location->result)) {
                $variables = array_merge(['lat' => $location->result->latitude, 'lng' => $location->result->longitude], $information, ['postcode' => strtoupper($postcode)]);
                return $this->db->update($this->getStoreDBTableName(), $variables, ['id' => intval($id)]);
            }
        }
        return false;
    }
    
    /**
     * Deletes a store an its information from the database
     * @param int $id This should be the unique store id to delete from the database
     * @return boolean If the store is deleted will return true else returns false
     */
    public function deleteStore($id) {
        return $this->db->delete($this->getStoreDBTableName(), ['id' => intval($id)], 1);
    }
    
    /**
     * Finds of the closest stores to the postcode given if any exist within the maximum distance
     * @param string $postcode This should be the postcode you are searching for the closest store from
     * @param int $maxdistance The maximum distance you wish to search for a store in miles
     * @param int $limit The maximum amount of results you want to display
     * @return array|boolean If results are available will return them as an array else will return false if no results are found or if postcode lookup fails
     */
    public function findClosest($postcode, $maxdistance = 50, $limit = 5) {
        $location = $this->geocode->query($postcode);
        if($location->status == 200 && !empty($location->result)) {
            return $this->findClosestByLatLng($location->result->latitude, $location->result->longitude, intval($maxdistance), intval($limit));
        }
        return false;
    }
    
    /**
     * Finds the closest stores to the latitude an longitude given as long as results exist within the maximum distance set 
     * @param float $lat This should be the latitude you wish to search for the closest store from
     * @param float $lng This should be the longitude you wish to search for the closest store from
     * @param int $maxdistance This is the maximum distance you are willing to search for in the database
     * @param int $limit This is the maximum amount of results to display
     * @return array|boolean If results are available will return them as an array else will return false if no results are found
     */
    public function findClosestByLatLng($lat, $lng, $maxdistance = 50, $limit = 5) {
        return $this->db->query("SELECT `{$this->getStoreDBTableName()}`.*, (3959 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS `distance` FROM `{$this->getStoreDBTableName()}` HAVING `distance` < ? ORDER BY `distance` LIMIT ".intval($limit).";", [$lat, $lng, $lat, intval($maxdistance)]);
    }
}
