<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Manejo de errores personalizado
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    sendResponse([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => [
            'error' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]
    ], 500);
}
set_error_handler("customErrorHandler");

// Captura de errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        sendResponse([
            'success' => false,
            'message' => 'Error fatal del servidor',
            'debug' => $error
        ], 500);
    }
});

// Incluir archivo de configuración de base de datos
$config_path = __DIR__ . '/config/database.php';
if (!file_exists($config_path)) {
    sendResponse([
        'success' => false,
        'message' => 'Error de configuración del servidor'
    ], 500);
}
require_once $config_path;

// Función para enviar respuesta JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

try {
    // Inicializar la conexión a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Obtener la ruta y el método de la solicitud
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', $uri);
    
    // Eliminar elementos vacíos y ajustar la ruta
    $uri = array_values(array_filter($uri, function($segment) {
        return $segment !== '';
    }));

    // Función para verificar el token JWT
    function validateToken($token) {
        // Por ahora retornamos true para pruebas
        return true;
    }

    // Verificar token para rutas protegidas
    function checkAuth() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            sendResponse([
                'success' => false,
                'message' => 'Token no proporcionado'
            ], 401);
        }
        
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!validateToken($token)) {
            sendResponse([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);
        }
    }

    // Manejar solicitud OPTIONS (para CORS)
    if ($requestMethod === 'OPTIONS') {
        sendResponse(['status' => 'ok']);
    }

    // GET /api/v1/mobile/videos
    if ($requestMethod === 'GET' && 
        isset($uri[2]) && $uri[2] === 'mobile' && 
        isset($uri[3]) && $uri[3] === 'videos') {
        
        try {
            $stmt = $db->prepare("SELECT * FROM videos ORDER BY created_at DESC");
            $stmt->execute();
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse([
                'success' => true,
                'data' => $videos
            ]);
        } catch (PDOException $e) {
            sendResponse([
                'success' => false,
                'message' => 'Error al obtener los videos',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    // POST /api/v1/mobile/videos
    if ($requestMethod === 'POST' && 
        isset($uri[2]) && $uri[2] === 'mobile' && 
        isset($uri[3]) && $uri[3] === 'videos') {
        
        try {
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);
            
            if (!isset($data['url']) || !isset($data['title'])) {
                throw new Exception('URL y título son requeridos');
            }
            
            if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                throw new Exception('URL inválida');
            }
            
            $stmt = $db->prepare("INSERT INTO videos (title, url, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([
                $data['title'],
                $data['url']
            ]);
            
            sendResponse([
                'success' => true,
                'message' => 'Video guardado correctamente',
                'data' => [
                    'id' => $db->lastInsertId(),
                    'title' => $data['title'],
                    'url' => $data['url']
                ]
            ], 201);
            
        } catch (Exception $e) {
            sendResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Si no se encuentra la ruta
    sendResponse([
        'success' => false,
        'message' => 'Ruta no encontrada'
    ], 404);

} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ], 500);
} 