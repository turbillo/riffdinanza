<?php
require_once 'misvars.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] >= 4) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $db = getDBConnection();
        
        switch ($_POST['action']) {
            case 'edit':
                $userId = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                $role = filter_var($_POST['role'], FILTER_SANITIZE_STRING);
                
                // Si se proporcionó una nueva contraseña
                if (!empty($_POST['new_password'])) {
                    $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET email = ?, role = ?, password = ? WHERE id = ?");
                    $stmt->execute([$email, $role, $hashedPassword, $userId]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET email = ?, role = ? WHERE id = ?");
                    $stmt->execute([$email, $role, $userId]);
                }
                $success = "Usuario actualizado correctamente.";
                break;

            case 'delete':
                $userId = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
                
                // Evitar eliminar usuarios administradores (ID < 4)
                if ($userId < 4) {
                    throw new Exception("No se pueden eliminar usuarios administradores.");
                }
                
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $success = "Usuario eliminado correctamente.";
                break;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Obtener lista de usuarios
try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT id, email, role, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al obtener usuarios: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ... (estilos anteriores) ... */
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            margin-left: 0.5rem;
        }

        .password-group {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <!-- ... (navbar existente) ... -->

    <div class="users-container">
        <h2>Gestión de Usuarios</h2>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['role'] ?? 'usuario'); ?></td>
                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                    <td>
                        <button class="btn btn-edit" 
                                onclick="showEditForm(<?php echo $user['id']; ?>, 
                                                    '<?php echo htmlspecialchars($user['email']); ?>', 
                                                    '<?php echo htmlspecialchars($user['role'] ?? 'usuario'); ?>')">
                            Editar
                        </button>
                        <?php if ($user['id'] >= 4): ?>
                        <button class="btn btn-delete" 
                                onclick="confirmDelete(<?php echo $user['id']; ?>, 
                                                     '<?php echo htmlspecialchars($user['email']); ?>')">
                            Eliminar
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Formulario de Edición -->
        <div id="editForm" class="edit-form">
            <h3>Editar Usuario</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="editEmail">Email:</label>
                        <input type="email" id="editEmail" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editRole">Rol:</label>
                        <select id="editRole" name="role">
                            <option value="usuario">Usuario</option>
                            <option value="admin">Administrador</option>
                            <option value="editor">Editor</option>
                        </select>
                    </div>
                </div>

                <div class="password-group">
                    <div class="form-group">
                        <label for="newPassword">Nueva Contraseña (dejar en blanco para mantener la actual):</label>
                        <input type="password" id="newPassword" name="new_password" minlength="6">
                    </div>
                </div>

                <div class="form-row">
                    <button type="submit" class="btn btn-save">Guardar Cambios</button>
                    <button type="button" class="btn btn-cancel" onclick="hideEditForm()">Cancelar</button>
                </div>
            </form>
        </div>

        <!-- Modal de Confirmación de Eliminación -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h3>Confirmar Eliminación</h3>
                <p>¿Estás seguro de que deseas eliminar al usuario <span id="deleteUserEmail"></span>?</p>
                <div class="modal-buttons">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="button" class="btn btn-cancel" onclick="hideDeleteModal()">Cancelar</button>
                        <button type="submit" class="btn btn-delete">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showEditForm(userId, email, role) {
        document.getElementById('editForm').classList.add('active');
        document.getElementById('editUserId').value = userId;
        document.getElementById('editEmail').value = email;
        document.getElementById('editRole').value = role;
        document.getElementById('newPassword').value = '';
    }

    function hideEditForm() {
        document.getElementById('editForm').classList.remove('active');
    }

    function confirmDelete(userId, email) {
        document.getElementById('deleteModal').classList.add('active');
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteUserEmail').textContent = email;
    }

    function hideDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
    }
    </script>
</body>
</html> 