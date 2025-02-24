<?php
// Configuración de la base de datos
define('DB_HOST', 'dbhost');
define('DB_USER', 'dbuser');
define('DB_PASS', 'dbpass');
define('DB_NAME', 'dbname');

// Configuración del sitio
define('SITE_NAME', 'Riffdinanza');
define('SITE_AUTHOR', 'Javi Solera');
define('CONTACT_EMAIL', 'info@turbillosolera.com');

// Configuración de reCAPTCHA (necesitarás registrarte en Google reCAPTCHA)
define('RECAPTCHA_SITE_KEY', 'googlesitekey');
define('RECAPTCHA_SECRET_KEY', 'googlesecret');

// Configuración de correo para IONOS
define('SMTP_HOST', 'mailsmtp');
define('SMTP_USER', 'mailuser');  // Tu dirección de correo completa
define('SMTP_PASS', 'mailpasspass');           // La contraseña de tu correo
define('SMTP_PORT', 587);                       // Puerto SMTP de IONOS
define('SMTP_FROM', 'info@turbillosolera.com'); // Tu dirección de correo

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
