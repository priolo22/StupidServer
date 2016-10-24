<?php
require_once 'core/ModelDB.php';



class ServiceClientDB extends ModelDB {
    
    public static function GetFields() {
        if ( ServiceClientDB::$Fields == null ) {
            ServiceClientDB::$Fields = array(
                new Field("position","position", Field::TYPE_INT)
                ,new Field("service","id_service",  Field::TYPE_OBJECT, "Service")
                ,new Field("client","id_client",  Field::TYPE_OBJECT, "Client")
            );
        }
        return ServiceClientDB::$Fields;
    }
    private static $Fields;
    
    public static function GetTable() {
        return "service_client";
    }
    
    public static function GetClassName() {
        return "ServiceClient";
    }
    
    public static function GetPrimaryKey () {
        if ( ServiceClientDB::$PrimaryKey == null ) {
            ServiceClientDB::$PrimaryKey = new Field("id","id", Field::TYPE_INT);
        }
        return ServiceClientDB::$PrimaryKey;
    }
    private static $PrimaryKey = null;
}



/**
 * Description of ServiceClient
 *
 * @author iorio_000
 */
class ServiceClient {
    
    public $id;
    
    public $service;
    
    public $client;
    
    public $position;
    
    public $db;
    
    
    
    public function __construct() {
        $this->db = new ServiceClientDB($this);
    }
    
}
