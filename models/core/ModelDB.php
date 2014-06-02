<?php
require_once 'Field.php';
require_once 'ModelCore.php';
require_once 'Collection.php';

/**
 * Si occupa di collegare il "model" con i dati contenuti in un database relazionare
 * Permette l'accesso CRUD al DB
 *
 * TODO cambiare in "ControlDB"
 * 
 * @author iorio_000
 */


class ModelDB extends ModelCore {
    
    
    
    // STATIC //
    
    /**
     * E' una funzione astratta, deve essere implementata dallo specifica controller.
     * Restituisce il nome della tabella a cui è assciato il "controller"
     * 
     * @abstract
     * @return string
     */
    public static function GetTable() {}
   
    
    
    /**
     * Restituisce un "CollectionSql" che permette di prelevare oggetti dal db 
     * a partire dalla tabella gestita da questo "controller"
     * @return Collection
     */
    public function GetCollection () {
        return new Collection($this);
    }
   
    // END STATIC //
    
    
    
    // COSTRUCTORS //
    
    public function __construct( $obj ) {
        parent::__construct($obj);
    }
    
    // COSTRUCTORS //
    
    
    
    // METHODS //
    
    /**
     * restituisce tutti i nomi(db!) delle proprieta' divise con la virgola.
     * Per esempio: 
     * city,name,surname
     * Si puo' specificare anche se utilizzare l'id
     * 
     * @param type $show_key
     * @return string
     */
    public static function GetFieldsName ( $show_key=FALSE ) {
        $fields = static::GetFields();
        $sql_fields = "";
        foreach ( $fields as $field ) {
            if ( !empty($sql_fields) ) $sql_fields .= ",";
            $sql_fields .= $field->name;
        }
        
        // ci mette pure il parametro della key principale.
        if ( $show_key === TRUE ) {
            $sql_fields .= ",". static::GetPrimaryKey()->name;
        }
        
        return $sql_fields;
    }
    
    
    
    /**
     * restituisce tutti i nomi(db!) delle proprieta' divise con la virgola.
     * A DIFFERENZA del metodo statico, le proprieta' non settate non vengono restituite.
     * Per esempio: 
     * city,name,surname
     * Si puo' specificare anche se utilizzare l'id
     * 
     * @param type $show_key
     * @return string
     */
    public function getFieldsName2 ( $show_key=FALSE ) {
        $fields = static::GetFields();
        $sql_fields = "";
        foreach ( $fields as $field ) {
            if ( $field->is_set($this->obj)==FALSE ) continue;
            if ( !empty($sql_fields) ) $sql_fields .= ",";
            $sql_fields .= $field->name;
        }
        
        // ci mette pure il parametro della key principale.
        if ( $show_key === TRUE ) {
            $sql_fields .= ",". static::GetPrimaryKey()->name;
        }
        
        return $sql_fields;
    }
    
    
    
    /**
     * Restituisce tutti i nomi(db!) dei field in un array
     * per esempio:
     * ["name","surname","city"]
     * 
     * @return array
     */
    public static function GetFieldsNameArray () {
        $fields = static::GetFields();
        $a = array();
        foreach ( $fields as $field ) {
            $a[] = $field->name;
        }
        return $a;
    }
    
    
    
    /**
     * Restituisce i field nella forma [table_name].[campo_name]
     * Da notare che se si tratta della tabella principale, cioe' non c'e' il parametro $param_name
     * Allora l'alias sarà valorizzato opportunamente
     * 
     * per esempio:
     * table.prop1 as 'prop1', customer.name as 'customer.name', customer.surname as 'customer.surname' ...
     * 
     * @return string
     */
    public static function GetFieldsNameForSelect ( $param_name = "" ) {

        // $isSubParam indica se si tratta di un paramtro della tabella principale (FALSE) oppure un parametro di una tabella di join (TRUE)
        $isSubParam = FALSE;
        if ( !empty($param_name) ) {
            $param_name .= ".";
            $isSubParam = TRUE;
        } 
        
        $sql = static::GetTable() . "." . static::GetPrimaryKey()->name
            . " as '" . $param_name . static::GetPrimaryKey()->name . "'";
        
        $fields = static::GetFields();
        foreach ( $fields as $field ) {
            $sql .= ",". static::GetTable() .".". $field->name;
            if ( $isSubParam==TRUE ) {
                $sql .= " as '". $param_name . $field->propertyName ."'";
            } else {
                $sql .= " as '". $field->name ."'";
            }
            
        }
        
        return $sql;
    }
    
    
    
    /**
     * Restituisce una stringa con tutti i valori, del tipo:
     * "bologna","Mario","Rossi"
     * 
     * @return string
     */
    public function getFieldsValue ( $show_key=FALSE ) {
        $fields = static::GetFields();
        $sql_value = "";
        foreach ( $fields as $field ) {
            if ( $field->is_set($this->obj)==FALSE ) continue;
            if ( strlen($sql_value)>0 ) $sql_value .= ",";
            $sql_value .= $field->sql_value($this->obj);
        }
     
        if ( $show_key === TRUE ) {
            $sql_value .= ",". static::GetPrimaryKey()->sql_value($this->obj);
        }
        
        return $sql_value;
    }
    
    
    
    /**
     * Restituisce una stringa del tipo:
     * city="bologna",name="Mario",surname="Rossi"
     * 
     * TODO da eliminare a favore della funzione getFieldsAssign2
     * 
     * @return string
     */
    public function getFieldsAssign () {
        $fields = static::GetFields();
        $sql_value = "";
        foreach ( $fields as $field ) {
            if ( $field->is_set($this->obj) ) {
                if ( !empty($sql_value) ) $sql_value .= ",";
                $sql_value .= $field->sql_assign($this->obj);
            }
        }
        return $sql_value;
    }
    
    /**
     * Restituisce una stringa del tipo:
     * client.city="bologna",client.name="Mario",client.surname="Rossi"
     * 
     * @return string
     */
    public function getFieldsAssign2 ( $div ) {
        $fields = static::GetFields();
        $sql_value = "";
        foreach ( $fields as $field ) {
            if ( $field->is_set($this->obj) ) {
                if ( !empty($sql_value) ) $sql_value .= $div;
                $sql_value .= static::GetTable() . "." . $field->sql_assign($this->obj);
            }
        }
        return $sql_value;
    }
    
    
    /**
     * OVERRIDING: ModelCore->set
     * 
     * @param array $data
     */
    public function set ( $data ) {
        // se non è un array ma un semplice vlore allora costruisco un array con la "primary key"
        if (!is_array($data)) {
            $field_key = static::GetPrimaryKey();
            $data = array ( 
                $field_key->propertyName => $data
            );
        }
        parent::set($data);
    }
    
    /**
     * setta i valori dell'oggetto gestito con i valori con l'array associativo "$data"
     * le key sono i nomi dei campi della tabella
     * 
     * TODO estendere la funzione parent::set
     * 
     * @param array $data
     */
    public function setFromResultset ( $data ) {
        
        // se non è un array allora lancia un errore
        if ( !is_array($data) ) {
            throw new Exception("Il parametro deve essere un array associativo.");
        }
        
        $data2 = array();
        
        foreach ( $data as $key => $val ) {
            $split = explode(".",$key);
            if (count($split)==1) {
                $data2[$key] = $val;
            } else {
                if ( !isset($data2[$split[0]]) ) {
                    $data2[$split[0]] = array();
                }
                $data2[$split[0]][$split[1]] = $val;
            }
            
        }
        
        // ciclo tutti i valori
        $fields = static::GetFields();        
        foreach ( $fields as $field ) {
            
            $prop_name = $field->name;
            
            if ( $field->type == Field::TYPE_OBJECT ) {
                if ( isset($data2[$field->propertyName]) ) {
                    $prop_name = $field->propertyName;
                }
            }
            
            // se esiste nell'array associativo la proprietà
            if ( isset($data2[$prop_name]) ) {
               $field->set($this->obj, $data2[$prop_name]);
            }
        }
        
        $field_key = static::GetPrimaryKey();
        if ( isset($data2[$field_key->propertyName]) ) {
            $data_value = $data[$field_key->propertyName];
            $field_key->set($this->obj, $data_value);
        }
    }
    
    
    /**
     * Carica l'oggetto dal db
     * 
     * @param array(string) $array_include
     * 
     * @global type $output
     * @global type $cnn
     */
    public function loadDB ( $array_include ) {
        global $output, $cnn;
  
        $coll = $this->GetCollection()
            ->where(static::GetTable().".".static::GetPrimaryKey()->sql_assign($this->obj));
        foreach ( $array_include as $i ) {
            $coll = $coll->load($i);
        }
        $data = $coll->getArray();
        
        // se c'e' almeno un record valorizzato setto l'oggetto
        if ( $data[0] ) {
            $this->set($data[0]);
        // ... altrimenti setto l'id a -1 (non so se è conveniente)
        } else {
            $this->id = -1;
        }
    }
    
    /**
     * Aggiorna l'oggetto nel db.
     * Se l'oggetto è nuovo allora inserisce il record INSERT
     * Se l'oggetto non è nuovo allora fa l'UPDATE
     * 
     * @global type $cnn
     * @global type $output
     */
    public function updateDB () {
        global $cnn, $output;
      
        $sql = "";
        if ( static::GetPrimaryKey()->void($this->obj) ) {
            $sql = "INSERT INTO " . static::GetTable()
                . " ( " . $this->getFieldsName2() . " ) "
                . "VALUES"
                . " ( " . $this->getFieldsValue() . " ) ";
            $output->debug .= $sql."\n\r";
            if ( $cnn->query($sql) == FALSE ) {
                throw new Exception("sql:insert:modeldb");
            }
            static::GetPrimaryKey()->set($this->obj, $cnn->insert_id);
            
        } else {
            $sql = "UPDATE " . static::GetTable()
                . " SET "
                . $this->getFieldsAssign()
                . " WHERE "
                . static::GetPrimaryKey()->sql_assign($this->obj);
            $output->debug .= $sql."\n\r";
            $cnn->query($sql);
        }
        
    }
    
    /**
     * Cancella l'oggetto dal DB
     * 
     * @global type $output
     * @global type $cnn
     */
    public function deleteDB () {
        global $output, $cnn;
        
        $sql = "DELETE FROM " . static::GetTable()
            . " WHERE " 
            . static::GetPrimaryKey()->sql_assign($this->obj);
        
        $output->debug .= $sql."\n\r";
        
        $cnn->query($sql);
    }
    
    // METHODS //
    
}






