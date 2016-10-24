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
    
    // CONST //
    
    const QUERY_TYPE_UNKNOW = 0;
    const QUERY_TYPE_NOTHING = 1;
    const QUERY_TYPE_SELECT = 2;
    const QUERY_TYPE_INSERT = 3;
    const QUERY_TYPE_UPDATE = 4;
    const QUERY_TYPE_DELETE = 5;
    
    // CONST //
    
    
    
    // STATIC //
    
    /**
     * E' una funzione astratta, deve essere implementata dallo specifica controller.
     * Restituisce il nome della tabella a cui è assciato il "controller"
     * 
     * @abstract
     * @return string
     */
    public static function GetTable() {}
   
    // END STATIC //
    
    
    // PROPERTIES //
    
    // indica il tipo di query che si sta facendo in questo momento
    public $queryType = ModelDB::QUERY_TYPE_NOTHING;
    
    // PROPERTIES //
    
   
    
    
    
    // COSTRUCTORS //
    
    public function __construct( $obj ) {
        parent::__construct($obj);
    }
    
    // COSTRUCTORS //
    
    
    
    // METHODS //
    
    /**
     * Restituisce un "CollectionSql" che permette di prelevare oggetti dal db 
     * a partire dalla tabella gestita da questo "controller"
     * @return Collection
     */
    public function getCollection () {
        return new Collection($this);
    }    
    
    /**
     * restituisce tutti i nomi(db!) delle proprieta' divise con la virgola.
     * Per esempio: 
     * city,name,surname
     * Si puo' specificare anche se utilizzare l'id
     * 
     * @param type $show_key
     * @return string
     */
    /*
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
    */
    
    
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
        
        // ciclo tutti i field
        foreach ( $fields as $field ) {
            // 
            if ( $field->is_set($this->obj)==FALSE && $field->type!=Field::TYPE_TIMESTAMP_CREATION ){
                continue;
            }
            if ( !empty($sql_fields) ) {
                $sql_fields .= ",";
            }
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
        $value;
        
        // ciclo tutte le proprietà
        foreach ( $fields as $field ) {
            
            // se è una query di INSERT e del tipo TYPE_TIMESTAMP_CREATION ci metto la data di ora.
            if ( $this->queryType==ModelDB::QUERY_TYPE_INSERT && $field->type==Field::TYPE_TIMESTAMP_CREATION ) {
                $value = "NOW()";
                
            // ... se è un altro tipo
            } else {
                // se la proprietà non è settata allora non fare nulla....
                if ( $field->is_set($this->obj)==FALSE ) continue;
                // ... altrimenti inserisci il valore.
                $value = $field->sql_value($this->obj);
            }
            
            // se non è il primo inserimento nella stringa sql allora ci metto la virgola
            if ( strlen($sql_value)>0 ) $sql_value .= ",";
            $sql_value .= $value;
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
    /*
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
    */
    /**
     * Restituisce una stringa del tipo:
     * client.city="bologna",client.name="Mario",client.surname="Rossi"
     * 
     * @param string $div è la stringa da mettere tra un campo e l'altro (p.e. " AND " oppure " OR " oppure ",")
     * @param array $fields_to_get indica i campi che devono essere considerati, se null o vuota considera tutti i campi.
     * @return string
     */
    public function getFieldsAssign2 ( $div, $fields_to_get ) {
        $fields = static::GetFields();
        $sql_value = "";
        foreach ( $fields as $field ) {
            
            // se il campo è di tipo timestamp creation 
            // salto il giro perche' questa funzione non viene usata dall INSERT
            if ( $field->type==Field::TYPE_TIMESTAMP_CREATION ) {
                continue;
            }
            
            // se il campo è settato, cioe' ha un valore oppure, se l'array $fields non è vuoto e ha dentro il nome del campo...
            // allora inserisco il campo nell SQL.
            if ( $field->is_set($this->obj) 
                 && ( 
                    empty($fields_to_get) 
                        || 
                    (!empty($fields_to_get) && in_array($field->propertyName, $fields_to_get))
                 )
            ) {
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
     * è un array di nomi di parametri (di tipo OGGETTO)
     * indicano che quell'oggetto deve anche essere caricato dal db
     * p.e.
     * $customerDB->getCollection()->load("address")->where("id=4")->getData()
     * Carica l'oggetto 
     * @global type $output
     * @global type $cnn
     */
    public function loadDB ( $array_include ) {
        global $output, $cnn;
  
        $coll = $this->getCollection()
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
        $sql = "";
        
        // Se la chiave primaria è vuota vuol dire che il modello deve essere inserito nel DB
        if ( static::GetPrimaryKey()->void($this->obj) ) {
            $this->beginQuery(ModelDB::QUERY_TYPE_INSERT);
            
            $sql = "INSERT INTO " . static::GetTable()
                . " ( " . $this->getFieldsName2() . " ) "
                . "VALUES"
                . " ( " . $this->getFieldsValue() . " ) ";

        // altrimenti vuol dire che il record è gia' presente nell db quindi faccio l'update
        } else {
            $this->beginQuery(ModelDB::QUERY_TYPE_UPDATE);
            
            $sql = "UPDATE " . static::GetTable()
                . " SET "
                . $this->getFieldsAssign2(",")
                . " WHERE "
                . static::GetPrimaryKey()->sql_assign($this->obj);
        }
        
        $this->endQuery($sql);
    }
    
    /**
     * Cancella l'oggetto dal DB
     * 
     * @global type $output
     * @global type $cnn
     */
    public function delete () {
        $this->beginQuery(ModelDB::QUERY_TYPE_DELETE);
        
        $sql = "DELETE FROM " . static::GetTable()
            . " WHERE " 
            . static::GetPrimaryKey()->sql_assign($this->obj);
        
        $this->endQuery($sql);
    }
    
    // METHODS //
    
    
    
    // EXECUTOR QUERY METHODS //
    
    /**
     * Inizializza la procedura di esecuzione della query
     * @param type $type
     */
    public function beginQuery ( $type ) {
        $this->queryType = $type;
    }
     
    /**
     * Esegue praticamente la stringa sql mandata applicando il risultato
     * @param array $sql
     * @throws Exception
     */
    public function endQuery ( $sql ) {
        global $cnn, $output;
        $output->debug .= $sql."|| ||";

        $result = $cnn->query($sql);
        
        if ( $this->queryType == ModelDB::QUERY_TYPE_INSERT ) {
            if ( $result == FALSE ) throw new Exception("modeldb::sql::insert");
            static::GetPrimaryKey()->set($this->obj, $cnn->insert_id);
            
        } else if ( $this->queryType == ModelDB::QUERY_TYPE_UPDATE ) {
            if ( $result == FALSE ) throw new Exception("modeldb::sql::update");
            
        } else if ( $this->queryType == ModelDB::QUERY_TYPE_DELETE ) {
            if ( $result == FALSE ) throw new Exception("modeldb::sql::delete");
            return $cnn->affected_rows;
            
        } else if ( $this->queryType == ModelDB::QUERY_TYPE_SELECT ) {
            if ( $result == FALSE ) throw new Exception("modeldb::sql::select");
            $data = array();
            
            // creo l'istanza dell'oggetto e assegno i valori prelevati dal db
            $class_name = static::GetClassName();
            while ($d = $result->fetch_assoc()) {
                $obj = new $class_name;
                $obj->db->setFromResultset ( $d );
                $data[] = $obj;
            }
            
            $result->free();
            return $data;
        }
    }
    
    // PRIVATE METHODS //
    
}
