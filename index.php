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
        include './connectionDB.php';
        
        $cnnDB = new connectionDB();
        if ($cnnDB->connect_errno) {
            echo 'Connect Error: ' . $cnnDB->connect_errno;
            exit();
        }
        
        //$cnnDB->autocommit(FALSE);
        $cnnDB->host_info ."\n";
        $query = "SELECT id, name FROM Services";
        $rows = [];
        if ($result = $cnnDB->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }
        
        $cnnDB->close();
        
        printf(json_encode($rows, JSON_NUMERIC_CHECK))
        ?>
    </body>
</html>
