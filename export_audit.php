<?php
require_once 'misvars.php';
session_start();

// Verificar si el usuario está logueado y tiene permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] > 4) {
    header('Location: index.php');
    exit();
}

// Obtener filtros
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$db = getDBConnection();

// Construir la consulta
$query = "SELECT 
            la.attempt_time,
            la.email,
            la.ip_address,
            la.status,
            la.user_agent,
            la.details
          FROM login_audit la 
          LEFT JOIN users u ON la.user_id = u.id 
          WHERE 1=1";
$params = [];

// Aplicar filtros
if ($status_filter) {
    $query .= " AND la.status = ?";
    $params[] = $status_filter;
}
if ($date_from) {
    $query .= " AND DATE(la.attempt_time) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $query .= " AND DATE(la.attempt_time) <= ?";
    $params[] = $date_to;
}
if ($search) {
    $query .= " AND (la.email LIKE ? OR la.ip_address LIKE ? OR la.details LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY la.attempt_time DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);

// Configurar headers para descarga de CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=auditoria_' . date('Y-m-d') . '.csv');

// Crear el archivo CSV
$output = fopen('php://output', 'w');

// BOM para Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados del CSV
fputcsv($output, [
    'Fecha y Hora',
    'Email',
    'Dirección IP',
    'Estado',
    'Navegador',
    'Detalles'
]);

// Datos
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $status = match($row['status']) {
        'success' => 'Exitoso',
        'failed' => 'Fallido',
        'blocked' => 'Bloqueado',
        default => $row['status']
    };
    
    fputcsv($output, [
        $row['attempt_time'],
        $row['email'],
        $row['ip_address'],
        $status,
        $row['user_agent'],
        $row['details']
    ]);
}

fclose($output); 