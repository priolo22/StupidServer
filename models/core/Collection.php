<?php

/**
 * Rappresenta una collezone di oggetti presente nel DB
 * Generalmente viene creata dal "controller" (ControllerDB)
 * questa permette di ricavare una collezione di oggetti dal DB tramite la costruzione
 * dimanimca della SELECT
 *
 * TODO da cambiare in "CollectionSql"
 * 
 * @author iorio_000
 */
class Collection {
    
    /**
     * dati ricavati dall'SQL
     * @var type 
     */
    private $data;
    
    /**
     * ModelDB da utilizzare per ricavare i i Field
     * @var type 
     */
    private $control;
    
    /**
     * Stringa where
     * TODO: da sistemare... fare in maniera piu' strutturata
     * @var type 
     */
    private $sql_where = "";
    
    /**
     * Array di tabelle da inserire come join
     * @var type 
     */
    private $sql_load = array();
    
    
    public function __construct( $m ) {
        $this->control = $m;     
    }

    
    /**
     * Restituisce il risultato in un array di oggetti
     * @return \array
     */
    public function getData() {
        $this->execute();
        return $this->data;
    }
    
    /**
     * Restituisce il risultato in forma di array associativi
     * @return type
     */
    public function getArray() {
        $this->execute();
        
        $d = array();
        foreach ( $this->data as $item ) {
            $d[] = $item->db->get();
        }
        
        return $d;
    }
    
    
    /**
     * Elimina tutti gli elementi della collection
     * ATTENZIONE: non elimina gli oggetti collegati
     */
    public function delete() {
        global $output, $cnn;
        
        $sql = $this->sqlDelete();
        $output->debug .= $sql."\n\r";

        if ($result = $cnn->query($sql)) {
            return $cnn->affected_rows;
        } else {
            throw new Exception ("Collection::delete");
        }
    }
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * Esegue la query SELECT e restituisce un array di oggetti
     * 
     * @global type $output
     * @global type $cnn
     * @return \Collection
     */
    private function execute() {
        global $output, $cnn;

        // se il data non è stato ancora caricato allora crea l'sql e lo esegue.
        if ( !$this->data ) {

            $this->data = array();
            $m = $this->control;
            $sql = $this->sqlSelect();

            $output->debug .= $sql."\n\r";
            
            // creo l'istanza dell'oggetto e assegno i valori prelevati dal db
            if ($result = $cnn->query($sql)) {
                $class_name = $m::GetClassName();
                while ($d = $result->fetch_assoc()) {
                    $obj = new $class_name;
                    $obj->db->setFromResultset ( $d );
                    $this->data[] = $obj;
                }
                $result->free();
            } else {
                throw new Exception ("Collection::execute");
            }

        }
        return $this;
    }
    
    /**
     * Crea la stringa del comando SQL
     * @return string
     */
    private function sqlSelect() {
        $m = $this->control;
        
        // SELECT ----------------------
        $sql = "SELECT ";
        $sql .= $m::GetFieldsNameForSelect();
        foreach ( $this->sql_load as $field_name ) {
            $field = $m::GetField($field_name);
            if ( $field->type == Field::TYPE_OBJECT ) {
                $class_modelDB = $field->class . "DB";
                $field_modelDB = new $class_modelDB();
                $sql .=  ",". $field_modelDB::GetFieldsNameForSelect($field->propertyName);
            }                
        }

        // JOIN ------------------------
        $sql .= " FROM " . $m::GetTable();

        foreach ( $this->sql_load as $field_name ) {
            $field = $m::GetField($field_name);
            if ( $field->type == Field::TYPE_OBJECT ) {
                $class_model = $field->class . "DB";
                $field_model = new $class_model();
                $sql .= " INNER JOIN " . $field_model::GetTable() 
                        . " ON " 
                        . $m::GetTable() .".". $field->name 
                        . "="
                        . $field_model::GetTable() .".". $field_model::GetPrimaryKey()->name;
            }
        }

        // WHERE -----------------------
        if ( !empty($this->sql_where) ) {
            $sql .= " WHERE " . $this->sql_where;
        }
        
        return $sql;
    }
    
    
    
    /**
     * Crea una string sql che esegue il delete su db dell'oggetto specificato
     * ATTENZIONE: non esegue la cancellazione degli oggetti collegati.
     * 
     * @return string
     */
    private function sqlDelete() {
        $m = $this->control;
        
        $sql = "DELETE";
        $sql .= " FROM " . $m::GetTable();
        
        // WHERE -----------------------
        if ( !empty($this->sql_where) ) {
            $sql .= " WHERE " . $this->sql_where;
        }
        
        return $sql;
    }
    
    
    
    
    // WHERE //
    
    /**
     * Setta la clausola WHERE
     * 
     * @param String $w
     * @return void
     */
    public function where ( $w ) {
        $this->sql_where .= $w;
        return $this;
    }

    /**
     * Specificando una proprietà come parametro inserisce
     * nella clausola "where" il fatto che quella proprieta' deve essere corrispondente.
     * 
     * @param string $prop
     * @return \Collection
     */
    public function whereAnd ( $prop ) {
        // ricavo il controller del DB
        $m = $this->control;
        // ricavo la proprietà da inserire nella clausola "where"
        $field = $m::GetField($prop);
        // se la proprietà è settata ...
        if ( $field!=NULL && $field->is_set($m->getModel()) ) {
            // ricavo la sua stringa sql di assegnazione (p.e. nome="pippo") 
            // e la inserisco nella clausola where
            $sql = $field->sql_assign($m->getModel());
            if ( empty($this->sql_where) ) {
                $this->sql_where .= $sql;
            } else {
                $this->sql_where .= " AND (".$sql.") ";
            }
        }
        return $this;
    }
    
    /**
     * Inserisce nella clausola WHERE tutte le proprietà di questo oggetto SETTATE
     * @return \Collection
     */
    public function similar () {
        $m = $this->control;
        
        $sql = $m->getFieldsAssign2( " AND " );
        
        if ( empty($this->sql_where) ) {
            $this->sql_where .= $sql;
        } else {
            $this->sql_where .= " AND (".$sql.") ";
        }
        
        return $this;
    }
    
    // WHERE //
    
    
    /**
     * I parametro indicato deve essere caricato
     * per esempio:
     * $customerDB->getCollection()->load("address")->where("id=4")->getData()
     * 
     * carica tutti i dati del "customer" e anche i dati della tabella "address" collegata a customer.
     * applica una where
     * 
     * @param type $param
     */
    public function load ( $param ) {
        if ( !in_array($param, $this->sql_load) ) {
            $this->sql_load[] = $param;
        }
        return $this;
    }
    
    
}
