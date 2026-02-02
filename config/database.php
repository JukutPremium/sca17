<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_complaint');

// Koneksi Database
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    
    return $conn;
}

// Fungsi helper untuk query
function query($sql) {
    $conn = getConnection();
    $result = $conn->query($sql);
    $conn->close();
    return $result;
}

// Fungsi helper untuk insert/update/delete
function execute($sql) {
    $conn = getConnection();
    $result = $conn->query($sql);
    $conn->close();
    return $result;
}
?>
