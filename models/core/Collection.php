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
     * Restituisce il risultato di una SELECT in un array di oggetti
     * @return \array
     */
    public function getData() {
        $m = $this->control;
        $m->beginQuery(ModelDB::QUERY_TYPE_SELECT);
        $sql = $this->sqlSelect();
        return $m->endQuery($sql);
    }
    
    /**
     * Restituisce il risultato di una SELECT in forma di array associativi
     * @return type
     */
    public function getArray() {
        $data = $this->getData();
        $d = array();
        foreach ( $data as $item ) {
            $d[] = $item->db->get();
        }
        return $d;
    }
    
    
    /**
     * Elimina tutti gli elementi della collection
     * Elimina anche tutti gli elementi "include"
     */
    public function delete() {
        $m = $this->control;
        $m->beginQuery(ModelDB::QUERY_TYPE_DELETE);
        $sql = $this->sqlDelete();
        return $m->endQuery($sql);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * Crea la stringa del comando SQL
     * @return string
     */
    private function sqlSelect() {
        $m = $this->control;
        
        // SELECT ----------------------
        $sql = "SELECT ";
        
        // inserisco i campi di questa tabella da selezionare
        // (p.e.) ... table.prop1 as 'prop1', table.prop2 as 'prop2', table.prop3 as 'prop3', ...
        $sql .= $m::GetFieldsNameForSelect();
        
//[II] ATTENZIONE!!! qua si suppone che il class model sia il nome della classe + "DB" cosa che potrebbe anche non essere vera        
        // inserisco anche i campi delle tabelle che carico con le join
        // (p.e.) ... customer.name as 'curstomer.name', messages.id as 'message.id', ...
        foreach ( $this->sql_load as $field_name ) {
            $field = $m::GetField($field_name);
            if ( $field->type == Field::TYPE_OBJECT ) {
                $class_modelDB = $field->class . "DB";
                $field_modelDB = new $class_modelDB();
                $sql .=  ",". $field_modelDB::GetFieldsNameForSelect($field->propertyName);
            }                
        }

        // (p.e) ... FROM table ...
        $sql .= " FROM " . $this->getJoin();

        // WHERE -----------------------
        if ( !empty($this->sql_where) ) {
            $sql .= " WHERE " . $this->sql_where;
        }
        
        return $sql;
    }
    
    
    /**
     * Crea una string sql che esegue il delete su db dell'oggetto specificato
     * 
     * (p.e.)
DELETE 
    service_client,services
FROM
    service_client 
    INNER JOIN services ON service_client.id_service=services.id 
WHERE 
    service_client.id_service=67

     * @return string
     */
    private function sqlDelete() {
        $m = $this->control;
        
        $sql = "DELETE ";
        $sql .= $this->getAllTables();
        $sql .= " FROM " . $this->getJoin();
        
        // WHERE -----------------------
        if ( !empty($this->sql_where) ) {
            $sql .= " WHERE " . $this->sql_where;
        }
        
        return $sql;
    }
    
    
    /**
     * Restituisce la lista di tutte le tabelle coinvolte
     * (p.e.) customers,address,messages ...
     * 
     * divisorio da utilizzare tra un nome e l'altro (di default ',')
     * @param string $div
     * 
     * @return string
     */
    private function getAllTables($div=",") {
        $m = $this->control;
        $sql = $m::GetTable();
        
        foreach ( $this->sql_load as $field_name ) {
            $field = $m::GetField($field_name);
            if ( $field->type == Field::TYPE_OBJECT ) {
// ATTENZIONE DA CORREGGERE                
                $class_modelDB = $field->class . "DB";
                $field_modelDB = new $class_modelDB();
                $sql .=  $div . $field_modelDB::GetTable();
            }                
        }
        
        return $sql;
    }
    
    
    /**
     * Prelevo la stringa JOIN di questa collection. 
     * Questa stringa tiene conto del campo "sql_include". Questo campo contiene tutti i "fields OGGETTO" da prendere in considerazione per la JOINT
     * Quindi se il "fields OGGETTO" è settato vado a prendere il controller della sua classe e prelevo i dati per costruire la joint
     * 
     * (p.e.) table1 INNER JOIN customers ON table1.customer_id=customers.id INNER JOIN messages ON table1.message_id=messages.id ...
     * 
     * @return string
     */
    private function getJoin () {
        $m = $this->control;
        $sql = $m::GetTable();

        // ciclo tutti i "field" di questo oggetto che devono essere legati alla JOIN
        foreach ( $this->sql_load as $field_name ) {
            
            // se si tratta di un campo OGGETTO
            $field = $m::GetField($field_name);
            if ( $field->type == Field::TYPE_OBJECT ) {
                
// [II] ATTENZIONE!! Qua si da per scontato che il controller del modello si chiami come la classe del modello +"DB" ma questo non è detto.
                // prelevo il controller dell'oggetto collegato a questo "field"
                $class_cnt = $field->class . "DB";
                $cnt = new $class_cnt();
                $sql .= " INNER JOIN " . $cnt::GetTable() 
                    . " ON " 
                    . $m::GetTable() .".". $field->name 
                    . "="
                    . $cnt::GetTable() .".". $cnt::GetPrimaryKey()->name;
            }
        }    
        
        return $sql;
    }
            

    /**
     * Restituisce, ciclando tutti i field include, per ogniuno tutti i campi della tabella associata
     * associa pure un alias che sara' utile quando devo analizzare il risultato in select
     * 
     * (p.e.) ... 
     * customer.name as 'curstomer.name', customer.surname as 'curstomer.surname', customer.address as 'curstomer.address'
     * , messages.id as 'message.id', messages.body as 'message.body'  
     * ...
     * 
     * @return string
     */
    private function includedFields() {
        $m = $this->control;
        $sql = "";
        
        //[II] ATTENZIONE!!! qua si suppone che il class model sia il nome della classe + "DB" cosa che potrebbe anche non essere vera        
        foreach ( $this->sql_load as $field_name ) {
            $field = $m::GetField($field_name);
            if ( $field->type == Field::TYPE_OBJECT ) {
                $class_modelDB = $field->class . "DB";
                $field_modelDB = new $class_modelDB();
                $sql .=  ",". $field_modelDB::GetFieldsNameForSelect($field->propertyName);
            }                
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
     * nella clausola "where" il fatto che quella proprieta' deve essere corrispondente alla proprietà del modello.
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
            // ricavo la sua stringa sql di assegnazione (p.e. name="pippo") 
            // inserisco anche il nome della tabella (p.e. clients.name="pippo"
            // e la inserisco nella clausola where
            $sql = $m::GetTable() .".". $field->sql_assign($m->getModel());
            //$sql = $field->sql_assign($m->getModel());
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
     * 
     * @param array $fields_to_get indica i campi che devono essere considerati, se null o vuota considera tutti i campi.
     * @return \Collection
     */
    public function similar ($fields_to_get) {
        $m = $this->control;
        
        $sql = $m->getFieldsAssign2( " AND ", $fields_to_get );
        
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
//[II] ATTENZIONE!! cambiare in "include"
    public function load ( $param ) {
        if ( !in_array($param, $this->sql_load) ) {
            $this->sql_load[] = $param;
        }
        return $this;
    }
    
    /**
     * 
     * @param type $fnc_item
     * @return \Collection
     */
    public function each ( $fnc_item ) {
        $data = $this->getData();
        foreach ( $data as $item ) {
            $fnc_item ( $item );
        }
        return $this;
    }
    
}
