<?php
require_once 'core/ModelDB.php';
require_once 'GCM.php';

class ClientDB extends ModelDB {
    
    public static function GetFields() {
        if ( ClientDB::$Fields == null ) {
            ClientDB::$Fields = array(
                new Field("gcm_id","gcm_regid", Field::TYPE_STRING)
            );
        }
        return ClientDB::$Fields;
    }
    private static $Fields;
    
    public static function GetTable() {
        return "client";
    }
    
    public static function GetClassName() {
        return "Client";
    }
    
    public static function GetPrimaryKey () {
        if ( ClientDB::$PrimaryKey == null ) {
            ClientDB::$PrimaryKey = new Field("id","id", Field::TYPE_INT);
        }
        return ClientDB::$PrimaryKey;
    }
    private static $PrimaryKey = null;
}


class Client {
    
    public $id;
    
    public $gcm_id;
    
    public $db;
    
    
    
    public function __construct() {
        $this->db = new ClientDB($this);
    }
    
    public function gcmMessage ( $data ) {
        $registatoin_ids = array($this->gcm_id);
        $message = array("data" => $data);
        $gcm = new GCM();
        $gcm->send_notification($registatoin_ids, $message);
    }
    
}
