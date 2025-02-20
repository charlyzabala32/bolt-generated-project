<?php
require_once __DIR__ . '/config.php'; // Use __DIR__ for absolute path

$host = 'localhost'; // Usually 'localhost' on cPanel
$dbname = 'u369746653_bolt'; //  Your database name.
$username = 'u369746653_bolt'; //  Your database username.
$password = 'Charly3269!'; //  Your database password.

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage()); // For production, log this, don't display it.
}
?>
