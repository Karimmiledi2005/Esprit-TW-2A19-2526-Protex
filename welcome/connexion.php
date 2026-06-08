<?php
if (!class_exists('config')) {
    class config
    {
        private static $pdo = null;

        public static function getConnexion()
        {
            if (!isset(self::$pdo)) {
                $configFile = __DIR__ . '/config.env.php';
                if (file_exists($configFile)) {
                    $env        = require $configFile;
                    $servername = $env['db_host']     ?? 'localhost';
                    $username   = $env['db_user']     ?? 'root';
                    $password   = $env['db_password'] ?? '';
                    $dbname     = $env['db_name']     ?? 'assurance';
                } else {
                    $servername = "localhost";
                    $username   = "root";
                    $password   = "";
                    $dbname     = "assurance";
                }

                try {
                    self::$pdo = new PDO(
                        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
                        $username,
                        $password,
                        [
                            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES   => false
                        ]
                    );
                } catch (Exception $e) {
                    error_log('welcome/connexion.php DB connection error: ' . $e->getMessage());
                    die('Erreur de connexion à la base de données.');
                }
            }
            return self::$pdo;
        }
    }
}
