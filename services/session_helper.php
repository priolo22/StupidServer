<?php
require_once '../services/ConnectionDB.php';
require_once 'Output.php';

function begin_session_helper () {
    global $cnn, $input, $output;
    
    // connessione al DB
    $cnn = new ConnectionDB();
    
    // parametri di input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    
    // output restituito
    $output = new Output();
    
    $output->input = $input;
}

function end_session_helper () {
    global $cnn, $output;
    $cnn->close();
    
    if ( $output->type=="ok" ) {
        http_response_code(200);
    } else {
        http_response_code(500);
    }
    
    echo $output->toJson();
}
