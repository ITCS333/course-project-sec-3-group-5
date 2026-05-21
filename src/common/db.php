<?php
if (!function_exists('getDBConnection')) {
    function getDBConnection(): PDO
    {
        static $pdo = null;

        if ($pdo === null) {
            $host = '/tmp';
            $db   = 'postgres';
            $user = 'runner';
            $pass = '';

            $dsn = "pgsql:host={$host};port=5432;dbname={$db}";

            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        return $pdo;
    }
}
?>
