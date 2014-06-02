<?php
require_once 'core/ModelDB.php';



class ServiceDB extends ModelDB {
     
    public static function GetFields() {
        if ( ServiceDB::$Fields == null ) {
            ServiceDB::$Fields = array(
                new Field("name","name", Field::TYPE_STRING)
                ,new Field("latitude","latitude",  Field::TYPE_FLOAT)
                ,new Field("longitude","longitude",  Field::TYPE_FLOAT)
                ,new Field("currentPosition","current_position",  Field::TYPE_INT)
                ,new Field("lastPosition","last_position",  Field::TYPE_INT)
                ,new Field("clientAdmin","id_client_admin",  Field::TYPE_OBJECT, "Client" )
            );
        }
        return ServiceDB::$Fields;
    }
    private static $Fields;
    
    public static function GetTable() {
        return "services";
    }
    
    public static function GetClassName() {
        return "Service";
    }
    
    public static function GetPrimaryKey () {
        if ( ServiceDB::$PrimaryKey == null ) {
            ServiceDB::$PrimaryKey = new Field("id","id", Field::TYPE_INT);
        }
        return ServiceDB::$PrimaryKey;
    }
    private static $PrimaryKey = null;
}



/**
 * Description of newPHPClass
 *
 * @author iorio_000
 */
class Service {
    
    public $id;
    
    public $name;
    
    public $latitude;
    
    public $longitude;
    
    public $currentPosition;
    
    public $lastPosition;

    public $clientAdmin;
    
    
    public $db;
    
    
    
    public function __construct() {
        $this->db = new ServiceDB($this);
    }
    
    
    
}
