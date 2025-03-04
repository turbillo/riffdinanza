<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

require_once 'config/database.php';

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicializar la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener la ruta y el método de la solicitud
$requestMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);
$uri = array_values(array_filter($uri));

// Función para enviar respuesta JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Función para verificar el token JWT
function validateToken($token) {
    // Aquí implementarías la validación del token JWT
    // Por ahora retornamos true para el ejemplo
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

// Rutas de la API
if (count($uri) >= 3 && $uri[0] === 'api' && $uri[1] === 'v1' && $uri[2] === 'mobile') {
    
    // POST /api/v1/mobile/login
    if ($requestMethod === 'POST' && isset($uri[3]) && $uri[3] === 'login') {
        try {
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);
            
            if (!isset($data['email']) || !isset($data['password'])) {
                throw new Exception('Email y contraseña son requeridos');
            }
            
            // Aquí verificarías las credenciales contra la base de datos
            $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ? AND password = ?");
            $stmt->execute([$data['email'], hash('sha256', $data['password'])]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Aquí generarías el token JWT
                $token = "ejemplo_token_jwt"; // Implementar generación real de JWT
                
                sendResponse([
                    'success' => true,
                    'message' => 'Login exitoso',
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email']
                    ]
                ]);
            } else {
                throw new Exception('Credenciales inválidas');
            }
        } catch (Exception $e) {
            sendResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    // POST /api/v1/mobile/videos
    if ($requestMethod === 'POST' && isset($uri[3]) && $uri[3] === 'videos') {
        checkAuth(); // Verificar token
        
        try {
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);
            
            if (!isset($data['url']) || !isset($data['title'])) {
                throw new Exception('URL y título son requeridos');
            }
            
            // Validar que la URL sea válida
            if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                throw new Exception('URL inválida');
            }
            
            // Guardar el video en la base de datos
            $stmt = $db->prepare("INSERT INTO videos (user_id, title, url, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                1, // Aquí iría el ID del usuario obtenido del token
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
    
    // GET /api/v1/mobile/videos
    if ($requestMethod === 'GET' && isset($uri[3]) && $uri[3] === 'videos') {
        checkAuth(); // Verificar token
        
        try {
            $stmt = $db->prepare("SELECT * FROM videos ORDER BY created_at DESC");
            $stmt->execute();
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse([
                'success' => true,
                'data' => $videos
            ]);
        } catch (Exception $e) {
            sendResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

// Si no se encuentra la ruta
sendResponse([
    'success' => false,
    'message' => 'Ruta no encontrada'
], 404); 