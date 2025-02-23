<?php
// Configuración de la base de datos
define('DB_HOST', 'db5017270149.hosting-data.io');
define('DB_USER', 'dbu5544628');
define('DB_PASS', 'RiffBBDDDanza25');
define('DB_NAME', 'dbs13857869');

// Configuración del sitio
define('SITE_NAME', 'Riffdinanza');
define('SITE_AUTHOR', 'Javi Solera');
define('CONTACT_EMAIL', 'info@turbillosolera.com');

// Configuración de reCAPTCHA (necesitarás registrarte en Google reCAPTCHA)
define('RECAPTCHA_SITE_KEY', '6LekYeAqAAAAANWEVihfnzfqO_EQBP3Vh_KZpPZy');
define('RECAPTCHA_SECRET_KEY', '6LekYeAqAAAAAEd1ZvAHDRM6xumJ96-gk2Ojjogo');

// Configuración de correo para IONOS
define('SMTP_HOST', 'smtp.ionos.es');
define('SMTP_USER', 'info@turbillosolera.com');  // Tu dirección de correo completa
define('SMTP_PASS', 'Turbill0Corr10!');           // La contraseña de tu correo
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