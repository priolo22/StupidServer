<?php

function IISendSocket ( $ip, $port, $message ) {

    
    
    
    /*
     * print "Sending heartbeat to IP $ip, port $port\n";
    if ($socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)) {
        if ( socket_sendto($socket, $message, strlen($message), 0, $ip, $port) !== false ) {
            print "Time: " . date("%r") . "\n";
        } else {
            print "Errore nell'invio del socket";
        }
        socket_close($socket);
    } else {
        print("can't create socket\n");
    }
     * 
     */
    
    
    
    
    
    
    /* Create a TCP/IP socket. */
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        return true;
    } else {
        echo "OK.\n";
    }
    
    echo "Attempting to connect to '$ip' on port '$port'...";
    $result = socket_connect($socket, $ip, $port);
    if ($result === false) {
        echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
        return false;
    } else {
        echo "OK.\n";
    }


    echo "Sending message...";
    socket_write($socket, $message, strlen($message));
    echo "OK.\n";    
    socket_close($socket);  

    return true;
}
