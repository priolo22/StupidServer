<?php


class ConnectionDB extends mysqli {
    public function __construct() {
        require_once 'config.php';
        parent::__construct(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE, DB_PORT);
    }
}
    