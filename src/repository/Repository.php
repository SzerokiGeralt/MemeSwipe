<?php

require_once __DIR__.'/../../Database.php';

class Repository {
    protected $database;

    public function __construct()
    {
        // Use singleton instance of Database
        $this->database = Database::getInstance();
    }
}