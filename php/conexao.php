<?php
$host = 'sql300.infinityfree.com';
$db   = 'if0_42937639_espaco_agroecologico';
$user = 'if0_42937639';
$pass = 'espacoagro2026';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>