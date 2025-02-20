<?php
session_start();
require_once 'config/config.php'; // Include the config file

// If user is already logged in, redirect to index.php
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: ' . $base_url . 'index.php'); // Use $base_url
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="<?php echo $base_url; ?>assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Iniciar Sesión</h1>
        <?php
        if (isset($_SESSION['login_error'])) {
            echo '<p class="text-red-500 text-sm mb-4">' . $_SESSION['login_error'] . '</p>';
            unset($_SESSION['login_error']); // Clear the error message
        }
        ?>
        <form action="<?php echo $base_url; ?>authenticate.php" method="POST">
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700">Usuario:</label>
                <input type="text" id="username" name="username" required class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Contraseña:</label>
                <input type="password" id="password" name="password" required class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div class="flex items-center justify-center">
                <button type="submit" class="btn-primary w-full">Ingresar</button>
            </div>
        </form>
    </div>
</body>
</html>
