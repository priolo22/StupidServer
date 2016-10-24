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
                ,new Field("dateCreation","date_creation",  Field::TYPE_TIMESTAMP_CREATION )
                ,new Field("workTimeTotal","work_time_total",  Field::TYPE_INT )
                ,new Field("workCustomersServed","work_customers_served",  Field::TYPE_INT )
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
    
    public $dateCreation;
    
    public $workTimeTotal;
    
    public $workCustomersServed;
    
    
    public $db;
    
    
    
    public function __construct() {
        $this->db = new ServiceDB($this);
    }
    
    
    
}
