<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');
define('DB_NAME', 'riffdinanza');

// Configuración del sitio
define('SITE_NAME', 'Riffdinanza');
define('SITE_AUTHOR', 'Javi Solera');
define('CONTACT_EMAIL', 'info@turbillosolera.com');

// Configuración de reCAPTCHA (necesitarás registrarte en Google reCAPTCHA)
define('RECAPTCHA_SITE_KEY', 'tu_site_key');
define('RECAPTCHA_SECRET_KEY', 'tu_secret_key');

// Configuración de SMTP
define('SMTP_HOST', 'smtp.tuservidor.com');
define('SMTP_USER', 'tu_usuario_smtp');
define('SMTP_PASS', 'tu_password_smtp');
define('SMTP_PORT', 587);


// Configuración de sesión
session_start();

// Función de conexión a la base de datos
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}
?>