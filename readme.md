# Riffdinanza

Portal web para compartir y recomendar vídeos de YouTube.

## Descripción

Riffdinanza es una plataforma que permite a los usuarios:
- Ver una galería de vídeos de YouTube
- Registrarse e iniciar sesión
- Recomendar nuevos vídeos
- Contactar con el administrador

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Composer (para gestionar dependencias)
- Cuenta de Google reCAPTCHA

## Instalación

1. Clonar el repositorio:


git clone https://github.com/tu-usuario/riffdinanza.git


2. Copiar el archivo de configuración:


cp misvars.example.php misvars.php

3. Configurar las variables en `misvars.php`:
- Credenciales de base de datos
- Claves de reCAPTCHA
- Configuración de SMTP

4. Importar la estructura de la base de datos:


mysql -u usuario -p nombre_base_datos < database.sql


5. Instalar dependencias:


composer install

## Estructura del Proyecto

riffdinanza/
├── assets/
│ ├── css/
│ │ └── style.css
│ └── js/
├── includes/
├── vendor/
├── .gitignore
├── README.md
├── index.php # Página principal con galería de vídeos
├── login.php # Inicio de sesión
├── register.php # Registro de usuarios
├── contact.php # Página de contacto
├── submit-video.php # Formulario para recomendar vídeos
├── logout.php # Cierre de sesión
├── database.sql # Estructura de la base de datos
├── misvars.example.php # Ejemplo de configuración
└── misvars.php # Configuración real (no incluida en git)


## Configuración

### Base de Datos
El archivo `database.sql` contiene la estructura de las siguientes tablas:
- `users`: Almacena información de usuarios registrados
- `videos`: Almacena los vídeos recomendados
- `contact_messages`: Almacena mensajes del formulario de contacto

### Variables de Entorno
Crear un archivo `misvars.php` basado en `misvars.example.php` con las siguientes configuraciones:


<?php
// Configuración de la base de datos
define('DB_HOST', 'host');
define('DB_USER', 'user');
define('DB_PASS', 'pass');
define('DB_NAME', 'dbname');

// Configuración del sitio
define('SITE_NAME', 'Riffdinanza');
define('SITE_AUTHOR', 'Javi Solera');
define('CONTACT_EMAIL', 'info@turbillosolera.com');

// Configuración de reCAPTCHA (necesitarás registrarte en Google reCAPTCHA)
define('RECAPTCHA_SITE_KEY', 'sitekey');
define('RECAPTCHA_SECRET_KEY', 'secretkey');

// Configuración de correo para IONOS
define('SMTP_HOST', 'smtp');
define('SMTP_USER', 'info@turbillosolera.com');  // Tu dirección de correo completa
define('SMTP_PASS', '');           // La contraseña de tu correo
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



## Seguridad

El proyecto implementa las siguientes medidas de seguridad:
- Contraseñas hasheadas usando `password_hash()`
- Protección contra SQL injection usando PDO
- Validación de formularios en servidor
- Protección contra CSRF
- Captcha en formularios sensibles
- Sanitización de datos de entrada
- Sesiones seguras

## Uso

### Usuarios No Registrados
- Pueden ver la galería de vídeos
- Pueden usar el formulario de contacto
- Pueden registrarse

### Usuarios Registrados
- Pueden iniciar/cerrar sesión
- Pueden recomendar nuevos vídeos
- Pueden ver la galería de vídeos

### Recomendación de Vídeos
1. Iniciar sesión
2. Ir a "Recomendar Vídeo"
3. Introducir URL de YouTube y título
4. Verificar la vista previa
5. Enviar recomendación

## Mantenimiento

### Respaldo de Base de Datos
Se recomienda realizar respaldos periódicos:


mysqldump -u usuario -p riffdinanza > backup_$(date +%Y%m%d).sql



### Logs
Los logs de error se encuentran en:
- Logs de PHP: `/var/log/php/error.log`
- Logs de Apache/Nginx: `/var/log/apache2/error.log`

## Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Autor

Javi Solera
- Email: info@turbillosolera.com
- Website: [turbillosolera.com](https://turbillosolera.com)

## Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## Agradecimientos

- [PHPMailer](https://github.com/PHPMailer/PHPMailer) para el envío de emails
- [Google reCAPTCHA](https://www.google.com/recaptcha) para la protección contra spam


