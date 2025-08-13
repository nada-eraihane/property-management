<!-- to do:
admin can only delete customer profiles
super admin can delete any profile except super admin ones
if profile has any activity on the database like if they are part of a foriegn key to a request or created a profile or anything
they should be not deletable, 
have 2 buttons : set to unactive or activate, and delete
delete is just for new users with no foreign keys in the database -->

<?php
require_once 'session_check.php';
include 'sidenav.php';
require_once 'db.php';
$mysqli = $conn;

// CSRF Protection - Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in and is admin
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Get current user's role and details from database
$current_user_role = null;
$current_username = $_SESSION['username'];
$current_user_id = null;

if ($mysqli->connect_error) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$stmt = $mysqli->prepare("
    SELECT u.user_id, r.role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.role_id 
    WHERE u.username = ?
");
$stmt->bind_param('s', $current_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $current_user_role = $row['role_name'];
    $current_user_id = $row['user_id'];
}
$stmt->close();

// Check authorization
if (!in_array($current_user_role, ['Admin', 'Super Admin'])) {
    die('Access denied. Admin privileges required.');
}

// Audit trail logging function
function logUserAction($mysqli, $admin_id, $target_user_id, $action, $details = '') {
    // Check if audit log table exists first
    $check_table = $mysqli->query("SHOW TABLES LIKE 'user_audit_log'");
    if ($check_table->num_rows === 0) {
        // Table doesn't exist, log to error log instead
        error_log("User Action: Admin ID $admin_id performed '$action' on User ID $target_user_id. Details: $details");
        return;
    }
    
    $stmt = $mysqli->prepare("
        INSERT INTO user_audit_log (admin_id, target_user_id, action, details, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param('iiss', $admin_id, $target_user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}

// Optimized function to get all user dependencies at once
function getAllUserDependencies($mysqli, $user_ids) {
    if (empty($user_ids)) return [];
    
    $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
    
    $query = "
        SELECT 
            u.user_id,
            COUNT(DISTINCT created_users.user_id) as created_users_count,
            COUNT(DISTINCT p.property_id) as properties_count,
            COUNT(DISTINCT pi.image_id) as images_count,
            COUNT(DISTINCT pv.video_id) as videos_count,
            COUNT(DISTINCT pu.update_id) as updates_count,
            COUNT(DISTINCT ui.image_id) as update_images_count,
            COUNT(DISTINCT uv.video_id) as update_videos_count,
            COUNT(DISTINCT ur.request_id) as requests_count
        FROM users u
        LEFT JOIN users created_users ON u.user_id = created_users.created_by
        LEFT JOIN properties p ON u.user_id = p.created_by
        LEFT JOIN property_images pi ON u.user_id = pi.uploaded_by
        LEFT JOIN property_videos pv ON u.user_id = pv.uploaded_by
        LEFT JOIN property_updates pu ON u.user_id = pu.created_by
        LEFT JOIN update_images ui ON u.user_id = ui.uploaded_by
        LEFT JOIN update_videos uv ON u.user_id = uv.uploaded_by
        LEFT JOIN user_requests ur ON u.user_id = ur.user_id
        WHERE u.user_id IN ($placeholders)
        GROUP BY u.user_id
    ";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param(str_repeat('i', count($user_ids)), ...$user_ids);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Convert to associative array and build dependency descriptions
    $dependencies = [];
    foreach ($results as $row) {
        $user_deps = [];
        
        if ($row['created_users_count'] > 0) {
            $user_deps[] = "A créé {$row['created_users_count']} utilisateur(s)";
        }
        if ($row['properties_count'] > 0) {
            $user_deps[] = "A créé {$row['properties_count']} propriété(s)";
        }
        if ($row['images_count'] > 0) {
            $user_deps[] = "A téléchargé {$row['images_count']} image(s) de propriété";
        }
        if ($row['videos_count'] > 0) {
            $user_deps[] = "A téléchargé {$row['videos_count']} vidéo(s) de propriété";
        }
        if ($row['updates_count'] > 0) {
            $user_deps[] = "A créé {$row['updates_count']} mise(s) à jour de propriété";
        }
        if ($row['update_images_count'] > 0) {
            $user_deps[] = "A téléchargé {$row['update_images_count']} image(s) de mise à jour";
        }
        if ($row['update_videos_count'] > 0) {
            $user_deps[] = "A téléchargé {$row['update_videos_count']} vidéo(s) de mise à jour";
        }
        if ($row['requests_count'] > 0) {
            $user_deps[] = "A fait {$row['requests_count']} demande(s)";
        }
        
        $dependencies[$row['user_id']] = $user_deps;
    }
    
    return $dependencies;
}

// Handle POST actions with improved error handling
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = 'Token de sécurité invalide. Veuillez actualiser la page.';
    } else {
        // Input validation
        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
        $allowed_actions = ['delete', 'activate', 'deactivate'];
        $action = $_POST['action'];
        
        if (!$user_id || !in_array($action, $allowed_actions)) {
            $error_message = 'Paramètres invalides.';
        } else {
            try {
                $mysqli->begin_transaction();
                
                // Get user details for authorization check
                $stmt = $mysqli->prepare("
                    SELECT u.username, u.status, r.role_name 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.role_id 
                    WHERE u.user_id = ?
                ");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $user_result = $stmt->get_result();
                
                if ($user_result->num_rows === 1) {
                    $user_data = $user_result->fetch_assoc();
                    $user_role = $user_data['role_name'];
                    $user_status = $user_data['status'];
                    $username = $user_data['username'];
                    
                    // Authorization checks
                    $authorized = false;
                    
                    if ($current_user_role === 'Super Admin') {
                        // Super Admin can manage all users except other Super Admins
                        $authorized = ($user_role !== 'Super Admin');
                    } else if ($current_user_role === 'Admin') {
                        // Admin can only manage Customers
                        $authorized = ($user_role === 'Customer');
                    }
                    
                    if (!$authorized) {
                        throw new Exception('Vous n\'êtes pas autorisé à effectuer cette action sur ce compte.');
                    }
                    
                    switch ($action) {
                        case 'delete':
                            // Check for dependencies using optimized function
                            $dependencies = getAllUserDependencies($mysqli, [$user_id]);
                            $user_dependencies = $dependencies[$user_id] ?? [];
                            
                            if (!empty($user_dependencies)) {
                                throw new Exception('Impossible de supprimer cet utilisateur. Il a des dépendances: ' . implode(', ', $user_dependencies));
                            }
                            
                            // Delete user
                            $delete_stmt = $mysqli->prepare("DELETE FROM users WHERE user_id = ?");
                            $delete_stmt->bind_param('i', $user_id);
                            
                            if (!$delete_stmt->execute()) {
                                throw new Exception('Erreur lors de la suppression: ' . $mysqli->error);
                            }
                            
                            $delete_stmt->close();
                            // logUserAction($mysqli, $current_user_id, $user_id, 'DELETE', "Deleted user: $username");
                            $success_message = "L'utilisateur {$username} a été supprimé avec succès.";
                            break;
                            
                        case 'deactivate':
                            $update_stmt = $mysqli->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
                            $update_stmt->bind_param('i', $user_id);
                            
                            if (!$update_stmt->execute()) {
                                throw new Exception('Erreur lors de la désactivation: ' . $mysqli->error);
                            }
                            
                            $update_stmt->close();
                            // logUserAction($mysqli, $current_user_id, $user_id, 'DEACTIVATE', "Deactivated user: $username");
                            $success_message = "L'utilisateur {$username} a été désactivé avec succès.";
                            break;
                            
                        case 'activate':
                            $update_stmt = $mysqli->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
                            $update_stmt->bind_param('i', $user_id);
                            
                            if (!$update_stmt->execute()) {
                                throw new Exception('Erreur lors de l\'activation: ' . $mysqli->error);
                            }
                            
                            $update_stmt->close();
                            // logUserAction($mysqli, $current_user_id, $user_id, 'ACTIVATE', "Activated user: $username");
                            $success_message = "L'utilisateur {$username} a été activé avec succès.";
                            break;
                    }
                    
                    $mysqli->commit();
                } else {
                    throw new Exception('Utilisateur introuvable.');
                }
                $stmt->close();
                
            } catch (Exception $e) {
                $mysqli->rollback();
                $error_message = $e->getMessage();
                error_log("User management error: " . $e->getMessage());
            }
        }
    }
}

// Build query based on user role
if ($current_user_role === 'Super Admin') {
    // Super Admin can see all users except themselves and other Super Admins
    $users_query = "
        SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.phone, 
               u.status, u.created_at, r.role_name,
               creator.username as created_by_username
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        LEFT JOIN users creator ON u.created_by = creator.user_id
        WHERE u.user_id != ? AND r.role_name != 'Super Admin'
        ORDER BY u.created_at DESC
    ";
    $stmt = $mysqli->prepare($users_query);
    $stmt->bind_param('i', $current_user_id);
} else {
    // Regular Admin can only see Customer accounts
    $users_query = "
        SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.phone, 
               u.status, u.created_at, r.role_name,
               creator.username as created_by_username
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        LEFT JOIN users creator ON u.created_by = creator.user_id
        WHERE r.role_name = 'Customer'
        ORDER BY u.created_at DESC
    ";
    $stmt = $mysqli->prepare($users_query);
}

$stmt->execute();
$users_result = $stmt->get_result();
$users = [];
$user_ids = [];

while ($user = $users_result->fetch_assoc()) {
    $users[] = $user;
    $user_ids[] = $user['user_id'];
}
$stmt->close();

// Get all dependencies at once (optimized)
$all_dependencies = getAllUserDependencies($mysqli, $user_ids);

// Add dependencies to users array
foreach ($users as &$user) {
    $user['dependencies'] = $all_dependencies[$user['user_id']] ?? [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer les Utilisateurs</title>
    <style>
        /* Theme CSS Custom Properties for Light Theme */
        :root {
            --primary-bg: #ffffff;
            --secondary-bg: #fff7ea;
            --accent-bg: #c8d9e6;
            --surface-bg: #f5efeb;
            --primary-text: #2e4156;
            --secondary-text: #1b2639;
            --accent-text: #567c8d;
            --highlight-color: #a21414;
            --footer-bg-color: #1b2639;
            --footer-txt-color: #ffffff;
            --success-color: #2d7d2d;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --current-theme: 'light';
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--primary-bg);
            min-height: 100vh;
            padding: 20px;
            color: var(--primary-text);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--secondary-bg);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(46, 65, 86, 0.15);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--danger-color) 0%, #b91c1c 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header-content p {
            opacity: 0.9;
            font-size: 16px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-secondary {
            background: var(--accent-bg);
            color: var(--primary-text);
        }

        .btn-secondary:hover {
            background: var(--accent-text);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background: #b45309;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #166534;
        }

        .content {
            padding: 40px;
        }

        .success-message {
            background: #f0f9f0;
            color: var(--success-color);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6c3;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message {
            background: #fee;
            color: var(--danger-color);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #fed7d7;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters {
            background: var(--surface-bg);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 12px;
            color: var(--accent-text);
            font-weight: 600;
            text-transform: uppercase;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 2px solid var(--accent-bg);
            border-radius: 8px;
            font-size: 14px;
            background: var(--primary-bg);
            color: var(--primary-text);
        }

        /* FIXED: Stats grid layout */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--surface-bg);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid var(--accent-text);
        }

        .stat-card h3 {
            color: var(--secondary-text);
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: var(--highlight-color);
        }

        .warning-info {
            background: #fef3c7;
            color: #92400e;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 4px solid var(--warning-color);
        }

        .warning-info h4 {
            margin-bottom: 10px;
        }

        .warning-info ul {
            margin-left: 20px;
        }

        .users-table-container {
            background: var(--primary-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(46, 65, 86, 0.1);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            background: var(--accent-bg);
            color: var(--secondary-text);
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .users-table td {
            padding: 15px 12px;
            border-bottom: 1px solid var(--accent-bg);
            vertical-align: middle;
        }

        .users-table tr:hover {
            background: var(--surface-bg);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-text);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-details h4 {
            color: var(--secondary-text);
            margin-bottom: 4px;
        }

        .user-details p {
            color: var(--accent-text);
            font-size: 12px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(45, 125, 45, 0.1);
            color: var(--success-color);
        }

        .status-inactive {
            background: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-admin {
            background: rgba(217, 119, 6, 0.1);
            color: var(--warning-color);
        }

        .role-customer {
            background: rgba(86, 124, 141, 0.1);
            color: var(--accent-text);
        }

        .user-row-deletable {
            border-left: 4px solid var(--success-color);
        }
        
        .user-row-not-deletable {
            border-left: 4px solid var(--danger-color);
        }

        .dependencies-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .dependencies-indicator.deletable {
            color: var(--success-color);
        }

        .dependencies-indicator.not-deletable {
            color: var(--danger-color);
        }

        .actions-cell {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--accent-text);
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--secondary-text);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: var(--primary-bg);
            margin: 15% auto;
            padding: 20px;
            border-radius: 15px;
            width: 80%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: var(--secondary-text);
        }

        .close {
            color: var(--accent-text);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: var(--danger-color);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }

            .users-table-container {
                overflow-x: auto;
            }

            .users-table {
                min-width: 900px;
            }

            .actions-cell {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>Supprimer les Utilisateurs</h1>
                <p>
                    <?php if ($current_user_role === 'Super Admin'): ?>
                        Gérer et supprimer les utilisateurs (sauf Super Admin)
                    <?php else: ?>
                        Gérer et supprimer les comptes clients uniquement
                    <?php endif; ?>
                </p>
            </div>
            <div class="header-actions">
                <a href="users.php" class="btn btn-primary">
                    Retour à la liste
                </a>
            </div>
        </div>

        <div class="content">
            <?php if ($success_message): ?>
                <div class="success-message">
                    <span>✓ Succès</span>
                    <span><?= htmlspecialchars($success_message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message">
                    <span>⚠ Erreur</span>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <div class="warning-info">
                <h4>Règles de suppression et indicateurs visuels</h4>
                <ul>
                    <li><strong>Bordure verte :</strong> Utilisateur supprimable (aucune dépendance)</li>
                    <li><strong>Bordure rouge :</strong> Utilisateur non supprimable (a des dépendances)</li>
                    <li><strong>Désactivation :</strong> Alternative sûre pour préserver l'intégrité des données</li>
                    <li><strong>Admin :</strong> Peut seulement gérer les comptes Client</li>
                    <li><strong>Super Admin :</strong> Peut gérer tous les comptes sauf les autres Super Admin</li>
                </ul>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Utilisateurs totaux</h3>
                    <div class="number"><?= count($users) ?></div>
                </div>
                <div class="stat-card">
                    <h3>Utilisateurs actifs</h3>
                    <div class="number"><?= count(array_filter($users, fn($u) => $u['status'] === 'active')) ?></div>
                </div>
                <div class="stat-card">
                    <h3>Utilisateurs inactifs</h3>
                    <div class="number"><?= count(array_filter($users, fn($u) => $u['status'] === 'inactive')) ?></div>
                </div>
                <div class="stat-card">
                    <h3>Supprimables</h3>
                    <div class="number"><?= count(array_filter($users, fn($u) => empty($u['dependencies']))) ?></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <div class="filter-group">
                    <label>Rechercher</label>
                    <input type="text" id="searchInput" placeholder="Nom, email, username...">
                </div>
                <div class="filter-group">
                    <label>Statut</label>
                    <select id="statusFilter">
                        <option value="">Tous les statuts</option>
                        <option value="active">Actif</option>
                        <option value="inactive">Inactif</option>
                    </select>
                </div>
                <?php if ($current_user_role === 'Super Admin'): ?>
                <div class="filter-group">
                    <label>Rôle</label>
                    <select id="roleFilter">
                        <option value="">Tous les rôles</option>
                        <option value="Customer">Customer</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="filter-group">
                    <label>Supprimable</label>
                    <select id="deletableFilter">
                        <option value="">Tous</option>
                        <option value="yes">Supprimable</option>
                        <option value="no">Non supprimable</option>
                    </select>
                </div>
            </div>
            
            <div class="users-table-container">
                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <h3>Aucun utilisateur à gérer</h3>
                        <p>Il n'y a actuellement aucun utilisateur que vous pouvez supprimer.</p>
                    </div>
                <?php else: ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Téléphone</th>
                                <th>Créé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr class="<?= empty($user['dependencies']) ? 'user-row-deletable' : 'user-row-not-deletable' ?>" 
                                    data-user-id="<?= $user['user_id'] ?>" 
                                    data-status="<?= $user['status'] ?>" 
                                    data-role="<?= $user['role_name'] ?>"
                                    data-deletable="<?= empty($user['dependencies']) ? 'yes' : 'no' ?>"
                                    data-search="<?= strtolower($user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['email'] . ' ' . $user['username']) ?>">
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                                            </div>
                                            <div class="user-details">
                                                <h4><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                                                <p><?= htmlspecialchars($user['email']) ?></p>
                                                <p>@<?= htmlspecialchars($user['username']) ?></p>
                                                <?php if (!empty($user['dependencies'])): ?>
                                                    <div class="dependencies-indicator not-deletable" title="<?= implode(', ', array_map('htmlspecialchars', $user['dependencies'])) ?>">
                                                        A des dépendances
                                                    </div>
                                                <?php else: ?>
                                                    <div class="dependencies-indicator deletable">
                                                        Supprimable
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?= strtolower(str_replace(' ', '-', $user['role_name'])) ?>">
                                            <?= htmlspecialchars($user['role_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $user['status'] ?>">
                                            <?= $user['status'] === 'active' ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </td>
                                    <td><?= $user['phone'] ? htmlspecialchars($user['phone']) : '-' ?></td>
                                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <div class="actions-cell">
                                            <?php if ($user['status'] === 'active'): ?>
                                                <button class="btn btn-warning btn-small" 
                                                        onclick="showModal('deactivate', <?= $user['user_id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                    Désactiver
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-success btn-small" 
                                                        onclick="showModal('activate', <?= $user['user_id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                    Activer
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (empty($user['dependencies'])): ?>
                                                <button class="btn btn-danger btn-small" 
                                                        onclick="showModal('delete', <?= $user['user_id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                    Supprimer
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Confirmer l'action</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody">
                <p id="modalMessage"></p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="user_id" id="modalUserId">
                    <input type="hidden" name="action" id="modalAction">
                    <button type="submit" class="btn" id="modalConfirmBtn">Confirmer</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            filterTable();
        });

        document.getElementById('statusFilter').addEventListener('change', function() {
            filterTable();
        });

        document.getElementById('deletableFilter').addEventListener('change', function() {
            filterTable();
        });

        <?php if ($current_user_role === 'Super Admin'): ?>
        document.getElementById('roleFilter').addEventListener('change', function() {
            filterTable();
        });
        <?php endif; ?>

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const deletableFilter = document.getElementById('deletableFilter').value;
            const roleFilter = document.getElementById('roleFilter') ? document.getElementById('roleFilter').value : '';
            
            const rows = document.querySelectorAll('.users-table tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                const status = row.getAttribute('data-status');
                const role = row.getAttribute('data-role');
                const deletable = row.getAttribute('data-deletable');
                
                const matchesSearch = searchData.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesRole = !roleFilter || role === roleFilter;
                const matchesDeletable = !deletableFilter || deletable === deletableFilter;
                
                if (matchesSearch && matchesStatus && matchesRole && matchesDeletable) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show/hide empty state message
            const emptyState = document.querySelector('.empty-state');
            const tableContainer = document.querySelector('.users-table');
            
            if (visibleCount === 0 && rows.length > 0) {
                if (tableContainer) tableContainer.style.display = 'none';
                if (!document.querySelector('.filter-empty-state')) {
                    const filterEmptyState = document.createElement('div');
                    filterEmptyState.className = 'empty-state filter-empty-state';
                    filterEmptyState.innerHTML = '<h3>Aucun résultat</h3><p>Aucun utilisateur ne correspond aux filtres sélectionnés.</p>';
                    document.querySelector('.users-table-container').appendChild(filterEmptyState);
                }
            } else {
                if (tableContainer) tableContainer.style.display = '';
                const filterEmptyState = document.querySelector('.filter-empty-state');
                if (filterEmptyState) filterEmptyState.remove();
            }
        }

        function showModal(action, userId, username) {
            const modal = document.getElementById('confirmModal');
            const title = document.getElementById('modalTitle');
            const message = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('modalConfirmBtn');
            const userIdInput = document.getElementById('modalUserId');
            const actionInput = document.getElementById('modalAction');
            
            userIdInput.value = userId;
            actionInput.value = action;
            
            switch(action) {
                case 'delete':
                    title.textContent = 'Confirmer la suppression';
                    message.innerHTML = `<strong>Attention !</strong><br>Êtes-vous sûr de vouloir supprimer définitivement l'utilisateur <strong>${username}</strong> ?<br><br>Cette action est <strong>irréversible</strong>.`;
                    confirmBtn.textContent = 'Supprimer définitivement';
                    confirmBtn.className = 'btn btn-danger';
                    break;
                    
                case 'deactivate':
                    title.textContent = 'Confirmer la désactivation';
                    message.innerHTML = `Êtes-vous sûr de vouloir désactiver l'utilisateur <strong>${username}</strong> ?<br><br>L'utilisateur ne pourra plus se connecter mais ses données seront conservées.`;
                    confirmBtn.textContent = 'Désactiver';
                    confirmBtn.className = 'btn btn-warning';
                    break;
                    
                case 'activate':
                    title.textContent = 'Confirmer l\'activation';
                    message.innerHTML = `Êtes-vous sûr de vouloir réactiver l'utilisateur <strong>${username}</strong> ?<br><br>L'utilisateur pourra à nouveau se connecter au système.`;
                    confirmBtn.textContent = 'Activer';
                    confirmBtn.className = 'btn btn-success';
                    break;
            }
            
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('confirmModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Auto-hide success/error messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.querySelector('.success-message');
            const errorMessage = document.querySelector('.error-message');
            
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 5000);
            }
            
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>