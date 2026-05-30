<?php
// ============================================================
// GrooveKut — Database Connection
// File: includes/db_connect.php
// Include at the top of any PHP file that needs DB access
// ============================================================

define('DB_HOST',    'localhost');
define('DB_USER',    'root');       // XAMPP default — change if you set a MySQL password
define('DB_PASS',    '');           // XAMPP default is empty
define('DB_NAME',    'groovekut_db');
define('DB_CHARSET', 'utf8mb4');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'error'   => 'DB connection failed: ' . $conn->connect_error
    ]));
}

$conn->set_charset(DB_CHARSET);
