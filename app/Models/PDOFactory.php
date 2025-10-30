<?php

namespace App\Models;

use PDO;

class PDOFactory
{
    public function create(array $config): PDO
    {
        [
            'dbhost' => $dbhost,
            'dbname' => $dbname,
            'dbuser' => $dbuser,
            'dbpass' => $dbpass
        ] = $config;

        $dsn = "pgsql:host={$dbhost};dbname={$dbname};";
        return new PDO($dsn, $dbuser, $dbpass);
    }
}
