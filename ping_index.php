<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        
        $server_ip   = $_SERVER['REMOTE_ADDR'];
        $server_port = 5000;
        $beat_period = 5;
        $message     = 'PyHB';
        print "Sending heartbeat to IP $server_ip, port $server_port\n";
        print "press Ctrl-C to stop\n";
        if ($socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)) {
            while (1) {
                socket_sendto($socket, $message, strlen($message), 0, $server_ip, $server_port);
                print "Time: " . date("%r") . "\n";
                sleep($beat_period);
            }
        } else {
            print("can't create socket\n");
        }
        
        ?>
    </body>
</html>
