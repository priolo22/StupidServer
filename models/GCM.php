<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GCM
 *
 * @author Ravi Tamada
 */
class GCM {

    //put your code here
    // constructor
    function __construct() {
        
    }

    /**
     * Sending Push Notification
     */
    public function send_notification($registatoin_ids, $message) {
        global $output;
        
        // Set POST variables
        $url = "https://android.googleapis.com/gcm/send";
        $server_key = "AIzaSyBWSefD-Ru2aRuoCGuAiUVTQJGKh5ta79U";

        $fields = array(
            'registration_ids' => $registatoin_ids,
            'data' => $message,
            //'time_to_live' => 3,
            //'collapse_key' => 'push'
        );
        
        $headers = array(
            'Authorization: key=' . $server_key,
            'Content-Type: application/json'
        );
        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        // Execute post
        $result = curl_exec($ch);
        if ($result === FALSE) {
            throw new Exception(curl_error($ch));
        }

        // Close connection
        curl_close($ch);
        $output->data = $result;

    }

}

?>
