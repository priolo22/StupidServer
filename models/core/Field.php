<?php

/**
 * Description of Field
 *
 * @author iorio_000
 */
class Field {
    
    const TYPE_INT = 1;
    const TYPE_BOOL = 2;
    const TYPE_STRING = 3;
    const TYPE_FLOAT = 4;
    const TYPE_DATE = 5;
    const TYPE_HTML = 6;
    const TYPE_NOTHING = 7;
    
    const TYPE_OBJECT = 8;
    const TYPE_ARRAY = 9;
    
    /**
     * nome delle proprietà dell'oggetto gestito "$obj"
     * 
     * @var string
     */
    public $propertyName = "";
    
    /**
     * nome del field del database
     * 
     * @var string
     */
    public $name = "";
    
    /**
     * tipo di variabile
     * 
     * @var int 
     */
    public $type = self::TYPE_NOTHING;
    
    /**
     * Calsse dell'oggetto nel caso si tratti di un TYPE_OBJECT oppure TYPE_ARRAY
     * 
     * @var class
     */
    public $class;
    
    
    public function __construct($propName, $name, $type, $class=null) {
        $this->propertyName = $propName;
        $this->name = $name;
        $this->type = $type;
        $this->class = $class;
    }
    
    /**
     * restituisce una stringa nella forma 
     * proprieta = "valore"
     * 
     * @return string
    */
    public function sql_assign ( $obj ) {
        return $this->name . "=" . $this->sql_value($obj);
    }
    
    /**
     * restituisce il valore nella forma del tipo indicato
     * p.e. se si tratta di una stringa restituirà: "valore" 
     * se è un numero: 45
     * 
     * @return mixed 
     * */
    public function sql_value ( $obj ) {
        if ( $this->type == self::TYPE_STRING) {
            return "\"" . $this->get($obj) . "\"";
            
        } else if ( $this->type == self::TYPE_OBJECT) {
            $objVal = $this->getValue($obj);
            if ( is_null($objVal) ) return null;
            return $objVal->db->GetPrimaryKey()->sql_value($objVal);
            
        } else {
            return $this->get($obj);
        }
    }
    
    /**
     * Restituisce TRUE se questo "field" è settato nell'oggetto
     * 
     * TODO cambiare nome in "isSet"
     * @param mixed $obj
     * @return boolean
     */
    public function is_set ( $obj ) {
        return isset ( $obj->{$this->propertyName} );
    }
    
    
    
    
     
    /**
     * Setta il valore alla proprieta' dell'oggetto
     * 
     * @param type $obj
     * @param type $value
     */ 
    public function setValue ( $obj, $value ) {
//echo $this->propertyName;  
        if ( $this->type == self::TYPE_INT ) {
            $obj->{$this->propertyName} = (int)$value;
//echo " :: int\n\r";           
        } else if ( $this->type == self::TYPE_FLOAT ) {
            $obj->{$this->propertyName} = (double)$value;
//echo " :: double\n\r";                       
        } else {
            $obj->{$this->propertyName} = $value;
//echo " :: default\n\r";                       
        }
    }
    
    /**
     * Ricava il valore della proprieta' dell'oggetto.
     * @param type $obj
     * @return type
     */
    public function getValue ( $obj ) {
        return $obj->{$this->propertyName};
    }
    
    /**
     * Setta un valore alla proprietà
     * Oppure, se si tratta di un oggetto, setta l'oggetto tramite un ARRAY ASSOCIATIVO
     * 
     * @param type $obj
     * @param type $value
     */
    public function set ( $obj, $value ) {
        
        // questa proprieta' è un oggetto ...
        if ( $this->type == self::TYPE_OBJECT ) {
            
            // ricavo l'oggetto di questo "field", se non esiste lo creo
            $o = $obj->{$this->propertyName};
            if ( $o == null ) {
                $o = new $this->class; 
            }
            
            // setto l'oggetto con il valore
            $o->db->set($value);
            
            // setto l'oggetto in questo field
            $this->setValue($obj, $o);
            
        // si tratta di un numero intero
        } else {
            $this->setValue($obj, $value);
        }
    }
    
    /**
     * Restituisce il VALORE della proprietà
     * oppure, se si tratta di un oggetto, restituisce un ARRAY ASSOCIATIVO dell'oggetto
     * TODO: cambiare il nome in "getData"
     * 
     * @param type $obj
     * @return mixed
     */
    public function get ( $obj ) {
        if ( $this->type == self::TYPE_OBJECT ) {
            //$o = $obj->{$this->propertyName};
            $o = $this->getValue($obj);
            return $o==null ? null : $o->db->get();
        } else {
            //return $obj->{$this->propertyName};
            return $this->getValue($obj);            
        }
    }
    
    /**
     * Restituisce se la proprietà è vuota
     * @param type $obj
     * TODO: sostituire con is_void
     * 
     * @return boolean
     */
    public function void ( $obj ) {
        $var = $this->get($obj);
        return empty($var);
    }
}
