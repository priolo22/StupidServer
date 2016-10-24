<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Output
 *
 * @author iorio_000
 */
class Output {
    //put your code here
    
    public $type;
    public $err;
    public $debug;
    public $data;
    
    public function __construct() {
        $this->type = "ok";
        $this->input = "";
        $this->err = "";
        $this->debug = "";
    }
    
    /**
     * Restituisce questo oggetto in formato json
     * @return string 
     */
    public function toJson() {
        return json_encode($this);
    }
    
    
}
