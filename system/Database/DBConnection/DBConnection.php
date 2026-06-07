<?php

namespace App\Database\DBConnection;

use PDO;
use PDOException;
class DBConnection
{
    private static ?PDO $dbConnectionInstance = null;
    private function __construct() {}

    public static function getDBConnectionInstance(): ?PDO
    {
        if (self::$dbConnectionInstance === null) {
            self::$dbConnectionInstance = new DBConnection()->dbConnection();
        }
        return self::$dbConnectionInstance;
    }

    private function dbConnection(): PDO
    {
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ];

        try {
            return new PDO(
                "mysql:host=" .DB_HOST.
                ";dbname=" .DB_NAME,
                DB_USERNAME,
                DB_PASSWORD,
                $options);
        }catch (PDOException $e){
            throw new PDOException($e->getMessage());
        }
    }

    public static function newInsertID(): false|string
    {
        return self::getDBConnectionInstance()->lastInsertId();
    }

}