<?php
session_start();
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Por favor, ingrese usuario y contraseña.";
        header('Location: ' . $base_url . 'login.php');
        exit;
    }

    require_once __DIR__ . '/config/database.php';

    try {
        // Use prepared statements to prevent SQL injection
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, start the session and set session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ' . $base_url . 'index.php');
            exit;
        } else {
            $_SESSION['login_error'] = "Usuario o contraseña incorrectos.";
            header('Location: ' . $base_url . 'login.php');
            exit;
        }
    } catch(PDOException $e) {
        // Log the error for debugging (check your PHP error logs)
        error_log("Database error in authenticate.php: " . $e->getMessage());
        $_SESSION['login_error'] = "Error de conexión. Por favor, intente de nuevo más tarde.";
        header('Location: ' . $base_url . 'login.php');
        exit;
    }
} else {
    // Redirect to login page if accessed directly without POST
    header('Location: ' . $base_url . 'login.php');
    exit;
}
?>
