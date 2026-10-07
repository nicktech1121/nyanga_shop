<?php
// ---------------------------------------------------------------
// Connexion à la base PostgreSQL — adapte ces valeurs à ton install.
// ---------------------------------------------------------------
$DB_HOST = '127.0.0.1';
$DB_PORT = '5432';
$DB_NAME = 'nyanga_shop';
$DB_USER = 'postgres';
$DB_PASS = 'nina21022006'; // mot de passe défini à l'installation de PostgreSQL

try {
    $pdo = new PDO(
        "pgsql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME",
        $DB_USER,
        $DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(
        "Connexion à la base de données impossible. Vérifie que PostgreSQL "
        . "tourne, que la base 'nyanga_shop' existe, et que config.php contient "
        . "les bons identifiants.<br>Détail technique : " . $e->getMessage()
    );
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
