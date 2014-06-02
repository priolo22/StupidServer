<?php

/**
 * E' la base del "controller"
 * A questo livello si occupa di serializzare e deserializzare il "modello" da un array
 * Contiene pure la "primary key" che in realta' serve per il controller su DB
 *  
 * TODO cambiare nome in "ControlCore"
 * 
 * @author iorio_000
 */
class ModelCore {
    
    /**
     * Restituisce tutti i "field" che questo "controller" gestisce per il "model"
     * 
     * @abstract
     * @return array (Field)
     */
    public static function GetFields() {}
    
    /**
     * Restituisce l'oggetto "Field" che rappresenta il campo "primary key" dell'oggetto
     * 
     * @abstract
     * @return Field
     */
    public static function GetPrimaryKey () {}
    
    /**
     * Restituisce il nome della classe a cui è accoppiato questo "controller"
     * 
     * @abstract
     * @return string
     */
    public static function GetClassName() {}
    
    /**
     * Restituisce un array di stringhe con i nomi di tutti i "fields" del "model"
     * per esempio:
     * {"name", "surname", "city"}
     * 
     * TODO: cambiare in 
     * 
     * @return array (string)
     */
    public static function All_fields_obj_array () {
        $fields = static::GetFields();
        $a = array();
        foreach ( $fields as $field ) {
            $a[] = $field->propertyName;
        }
        return $a;
    }
    
    /**
     * Restituisce un field tramite il suo nome.
     * 
     * @param string $name
     * @return Field
     */
    public static function GetField ( $name ) {
        if ( $name == static::GetPrimaryKey()->propertyName ) {
            return static::GetPrimaryKey();
        }
        
        $fields = static::GetFields();
        foreach($fields as $field) {
            if ( $name == $field->propertyName ) {
                return $field;
            }
        }
        return NULL;
    }
    
    
    
    // PROPERTIES //
    
    /**
     * E' il "model" che questo "controller" gestisce.
     * @var object
     */
    protected $obj;
    public function getModel() {
        return $this->obj;
    }
    
    // END PROPERTIES //
    
    
    
    /**
     * 
     * @param object $model
     */
    public function __construct( $model ) {
        $this->obj = $model;
    }
    
    
    
    /**
     * Setta i valori del "model" con i valori con l'array associativo "$data"
     * 
     * @param array $data
     * @return void
     */
    public function set ( $data ) {
        
        // se non è un array allora lancia un errore
        if ( !is_array($data) ) {
            throw new Exception("Il parametro deve essere un array associativo.");
        }

        // ciclo tutti i valori
        $fields = static::GetFields();        
//echo "object : " . var_dump($data)."\n\r\n\r";        
        foreach ( $fields as $field ) {
//echo " set data " . $field->propertyName . " = ". $data[$field->propertyName] . " isset " . isset($data[$field->propertyName]) . "\n\r";
            if ( isset($data[$field->propertyName]) ) {
                $field->set($this->obj, $data[$field->propertyName]);
            }
        }
        
        // valorizzo, se c'e', anche la "primary key"
        $field_key = static::GetPrimaryKey();
        if ( isset($data[$field_key->propertyName]) ) {
            $data_value = $data[$field_key->propertyName];
            $field_key->set($this->obj, $data_value);
        }
    }
    
    /**
     * Prelevo tutti i parametri del "model" come ARRAY ASSOCIATIVO
     * 
     * @return array 
     */
    public function get () {
        
        // array da restituire
        $data = array();
        
        // prelevo tutti i "field" dell'oggetto
        $fields = static::GetFields();        
        // setta l'array associativo con i valori dei field
        foreach ( $fields as $field ) {
            if ( $field->is_set($this->obj) ) {
                $data[$field->propertyName] = $field->get($this->obj);
            }
        }
        
        // setto anche la "primary key" nell'array associativo
        $field_key = static::GetPrimaryKey();
        if ( $field_key->is_set($this->obj) ) {
            $data[$field_key->propertyName] = $field_key->get($this->obj);
        }
        
        return $data;
    }
    
}
