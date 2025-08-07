<!-- to do:
edit the edit table to make sure it is displayed over the usertable and not at the bottom
make sure admin cannot edit the role of users
make sure the if an admin is inactif they shouldnt be logged in -->
<?php
require_once 'session_check.php';
include 'sidenav.php';
require_once 'db.php';
$mysqli = $conn;

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

// Get available roles based on current user's permissions
$available_roles = [];
if ($current_user_role === 'Super Admin') {
    // Super Admin can assign all roles
    $roles_query = "SELECT role_id, role_name FROM roles ORDER BY role_name";
} else {
    // Regular Admin can only assign Customer role
    $roles_query = "SELECT role_id, role_name FROM roles WHERE role_name = 'Customer' ORDER BY role_name";
}

$roles_result = $mysqli->query($roles_query);
while ($role = $roles_result->fetch_assoc()) {
    $available_roles[] = $role;
}

// Build users list query based on user role
if ($current_user_role === 'Super Admin') {
    // Super Admin can see all users
    $users_query = "
        SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.phone, 
               u.status, u.created_at, r.role_name, r.role_id,
               creator.username as created_by_username
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        LEFT JOIN users creator ON u.created_by = creator.user_id
        ORDER BY u.created_at DESC
    ";
} else {
    // Regular Admin can only see Customer accounts
    $users_query = "
        SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.phone, 
               u.status, u.created_at, r.role_name, r.role_id,
               creator.username as created_by_username
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        LEFT JOIN users creator ON u.created_by = creator.user_id
        WHERE r.role_name = 'Customer'
        ORDER BY u.created_at DESC
    ";
}

$users_result = $mysqli->query($users_query);
$users = [];
while ($user = $users_result->fetch_assoc()) {
    $users[] = $user;
}

// Handle user selection and editing
$selected_user = null;
$edit_user_id = $_GET['edit_id'] ?? ($_POST['edit_user_id'] ?? null);

if ($edit_user_id) {
    // Get selected user details with permission check
    if ($current_user_role === 'Super Admin') {
        $user_stmt = $mysqli->prepare("
            SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.phone, 
                   u.status, r.role_name, r.role_id
            FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.user_id = ?
        ");
    } else {
        $user_stmt = $mysqli->prepare("
            SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.phone, 
                   u.status, r.role_name, r.role_id
            FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.user_id = ? AND r.role_name = 'Customer'
        ");
    }
    
    $user_stmt->bind_param('i', $edit_user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows === 1) {
        $selected_user = $user_result->fetch_assoc();
    }
    $user_stmt->close();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $edit_user_id = intval($_POST['edit_user_id']);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $change_password = isset($_POST['change_password']) && $_POST['change_password'] === '1';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';

    // Validation
    if (!$username || !$email || !$first_name || !$last_name || !$role_id) {
        $error = 'Tous les champs obligatoires doivent être remplis.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif ($phone && !preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $phone)) {
        $error = 'Format de téléphone invalide.';
    } else {
        // Check if role is allowed for current user
        $allowed_role = false;
        foreach ($available_roles as $role) {
            if ($role['role_id'] == $role_id) {
                $allowed_role = true;
                break;
            }
        }
        
        if (!$allowed_role) {
            $error = 'Vous n\'êtes pas autorisé à assigner ce rôle.';
        } else {
            // If changing password, validate admin password and new password
            if ($change_password) {
                if (!$admin_password) {
                    $error = 'Vous devez entrer votre mot de passe pour changer le mot de passe de l\'utilisateur.';
                } elseif (!$new_password) {
                    $error = 'Le nouveau mot de passe est requis.';
                } elseif (strlen($new_password) < 8) {
                    $error = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
                } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
                    $error = 'Le nouveau mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.';
                } elseif ($new_password !== $confirm_new_password) {
                    $error = 'Les nouveaux mots de passe ne correspondent pas.';
                } else {
                    // Verify admin password
                    $admin_stmt = $mysqli->prepare("SELECT password_hash FROM users WHERE user_id = ?");
                    $admin_stmt->bind_param('i', $current_user_id);
                    $admin_stmt->execute();
                    $admin_result = $admin_stmt->get_result();
                    
                    if ($admin_result->num_rows === 1) {
                        $admin_row = $admin_result->fetch_assoc();
                        if (!password_verify($admin_password, $admin_row['password_hash'])) {
                            $error = 'Mot de passe administrateur incorrect.';
                        }
                    } else {
                        $error = 'Erreur de vérification du mot de passe administrateur.';
                    }
                    $admin_stmt->close();
                }
            }
            
            if (!$error) {
                // Check if username or email already exists for other users
                $check_stmt = $mysqli->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
                $check_stmt->bind_param('ssi', $username, $email, $edit_user_id);
                $check_stmt->execute();
                $check_stmt->bind_result($count);
                $check_stmt->fetch();
                $check_stmt->close();

                if ($count > 0) {
                    $error = 'Ce nom d\'utilisateur ou cette adresse email est déjà utilisé par un autre utilisateur.';
                } else {
                    // Update user
                    if ($change_password) {
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_stmt = $mysqli->prepare("
                            UPDATE users SET 
                            username = ?, email = ?, first_name = ?, last_name = ?, 
                            phone = ?, role_id = ?, status = ?, password_hash = ?
                            WHERE user_id = ?
                        ");
                        $update_stmt->bind_param('ssssssssi', 
                            $username, $email, $first_name, $last_name, 
                            $phone, $role_id, $status, $password_hash, $edit_user_id
                        );
                    } else {
                        $update_stmt = $mysqli->prepare("
                            UPDATE users SET 
                            username = ?, email = ?, first_name = ?, last_name = ?, 
                            phone = ?, role_id = ?, status = ?
                            WHERE user_id = ?
                        ");
                        $update_stmt->bind_param('sssssssi', 
                            $username, $email, $first_name, $last_name, 
                            $phone, $role_id, $status, $edit_user_id
                        );
                    }

                    if ($update_stmt->execute()) {
                        $success = 'Utilisateur mis à jour avec succès!';
                        // Refresh selected user data
                        if ($current_user_role === 'Super Admin') {
                            $refresh_stmt = $mysqli->prepare("
                                SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.phone, 
                                       u.status, r.role_name, r.role_id
                                FROM users u 
                                JOIN roles r ON u.role_id = r.role_id 
                                WHERE u.user_id = ?
                            ");
                        } else {
                            $refresh_stmt = $mysqli->prepare("
                                SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.phone, 
                                       u.status, r.role_name, r.role_id
                                FROM users u 
                                JOIN roles r ON u.role_id = r.role_id 
                                WHERE u.user_id = ? AND r.role_name = 'Customer'
                            ");
                        }
                        $refresh_stmt->bind_param('i', $edit_user_id);
                        $refresh_stmt->execute();
                        $refresh_result = $refresh_stmt->get_result();
                        
                        if ($refresh_result->num_rows === 1) {
                            $selected_user = $refresh_result->fetch_assoc();
                        }
                        $refresh_stmt->close();
                    } else {
                        $error = 'Erreur lors de la mise à jour de l\'utilisateur: ' . $mysqli->error;
                    }
                    $update_stmt->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Utilisateur</title>
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
            background: linear-gradient(135deg, var(--highlight-color) 0%, #8b1212 100%);
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

        .content {
            padding: 40px;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 20px;
            color: var(--secondary-text);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent-bg);
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

        .users-table-container {
            background: var(--primary-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(46, 65, 86, 0.1);
            margin-bottom: 30px;
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

        .users-table tr.selected {
            background: rgba(86, 124, 141, 0.1);
            border-left: 4px solid var(--accent-text);
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
            background: rgba(162, 20, 20, 0.1);
            color: var(--highlight-color);
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-super-admin {
            background: rgba(162, 20, 20, 0.1);
            color: var(--highlight-color);
        }

        .role-admin {
            background: rgba(217, 119, 6, 0.1);
            color: var(--warning-color);
        }

        .role-customer {
            background: rgba(86, 124, 141, 0.1);
            color: var(--accent-text);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: var(--highlight-color);
            color: white;
        }

        .btn-primary:hover {
            background: #8b1212;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--accent-bg);
            color: var(--primary-text);
        }

        .btn-secondary:hover {
            background: var(--accent-text);
            color: white;
        }

        .edit-form-container {
            background: var(--primary-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(46, 65, 86, 0.1);
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-text);
            font-weight: 500;
        }

        .required {
            color: var(--highlight-color);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--accent-bg);
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            background-color: var(--primary-bg);
            color: var(--primary-text);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent-text);
            box-shadow: 0 0 0 3px rgba(86, 124, 141, 0.1);
        }

        .password-section {
            background: var(--surface-bg);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid var(--warning-color);
        }

        .password-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .password-toggle input[type="checkbox"] {
            width: auto;
        }

        .password-fields {
            display: none;
        }

        .password-fields.show {
            display: block;
        }

        .password-requirements {
            background: var(--surface-bg);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid var(--accent-text);
        }

        .password-requirements h4 {
            color: var(--secondary-text);
            margin-bottom: 10px;
        }

        .password-requirements ul {
            list-style: none;
            padding-left: 0;
        }

        .password-requirements li {
            color: var(--accent-text);
            margin-bottom: 5px;
            padding-left: 20px;
            position: relative;
        }

        .password-requirements li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--highlight-color);
        }

        .admin-password-section {
            background: rgba(162, 20, 20, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid var(--highlight-color);
        }

        .admin-password-section h4 {
            color: var(--highlight-color);
            margin-bottom: 10px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--accent-bg);
        }

        .btn-large {
            padding: 12px 24px;
            font-size: 16px;
        }

        .error-message {
            background: #fee;
            color: var(--highlight-color);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #fed7d7;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .no-user-selected {
            text-align: center;
            padding: 60px 20px;
            color: var(--accent-text);
        }

        .no-user-selected h3 {
            margin-bottom: 10px;
            color: var(--secondary-text);
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

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .users-table-container {
                overflow-x: auto;
            }

            .users-table {
                min-width: 800px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>Modifier un Utilisateur</h1>
                <p>
                    <?php if ($current_user_role === 'Super Admin'): ?>
                        Sélectionnez et modifiez tout utilisateur du système
                    <?php else: ?>
                        Sélectionnez et modifiez un compte client
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="content">
            <!-- User Selection Section -->
            <div class="section">
                <h2 class="section-title">Sélectionner un Utilisateur</h2>
                
                <!-- Filters -->
                <div class="filters">
                    <div class="filter-group">
                        <label>Rechercher</label>
                        <input type="text" id="searchInput" placeholder="Nom, email, téléphone...">
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
                            <option value="Super Admin">Super Admin</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Users Table -->
                <div class="users-table-container">
                    <?php if (empty($users)): ?>
                        <div class="empty-state">
                            <h3>Aucun utilisateur trouvé</h3>
                            <p>Il n'y a actuellement aucun utilisateur dans le système.</p>
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
                            <tbody id="usersTableBody">
                                <?php foreach ($users as $user): ?>
                                    <tr data-user-id="<?= $user['user_id'] ?>" 
                                        data-status="<?= $user['status'] ?>" 
                                        data-role="<?= $user['role_name'] ?>"
                                        data-search="<?= strtolower($user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['email'] . ' ' . $user['username'] . ' ' . ($user['phone'] ?? '')) ?>"
                                        <?= ($selected_user && $selected_user['user_id'] == $user['user_id']) ? 'class="selected"' : '' ?>>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                                                </div>
                                                <div class="user-details">
                                                    <h4><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                                                    <p><?= htmlspecialchars($user['email']) ?></p>
                                                    <p>@<?= htmlspecialchars($user['username']) ?></p>
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
                                        <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <a href="?edit_id=<?= $user['user_id'] ?>" class="btn btn-primary">
                                                Modifier
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Edit Form Section -->
            <?php if ($selected_user): ?>
            <div class="section">
                <h2 class="section-title">Modifier l'Utilisateur</h2>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <span>⚠️</span>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        <span>✅</span>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <div class="edit-form-container">
                    <form method="POST" id="editUserForm">
                        <input type="hidden" name="edit_user_id" value="<?= $selected_user['user_id'] ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Nom d'utilisateur <span class="required">*</span></label>
                                <input type="text" id="username" name="username" required 
                                       minlength="3" maxlength="50" 
                                       value="<?= htmlspecialchars($selected_user['username']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Adresse email <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required 
                                       maxlength="100" 
                                       value="<?= htmlspecialchars($selected_user['email']) ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">Prénom <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" required 
                                       maxlength="50" 
                                       value="<?= htmlspecialchars($selected_user['first_name']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Nom <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" required 
                                       maxlength="50" 
                                       value="<?= htmlspecialchars($selected_user['last_name']) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Téléphone</label>
                            <input type="tel" id="phone" name="phone" 
                                   pattern="[\d\s\-\+\(\)]{10,20}" 
                                   value="<?= htmlspecialchars($selected_user['phone'] ?? '') ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="role_id">Rôle <span class="required">*</span></label>
                                <select id="role_id" name="role_id" required>
                                    <?php foreach ($available_roles as $role): ?>
                                        <option value="<?= $role['role_id'] ?>" 
                                                <?= ($selected_user['role_id'] == $role['role_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['role_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status">Statut</label>
                                <select id="status" name="status">
                                    <option value="active" <?= ($selected_user['status'] === 'active') ? 'selected' : '' ?>>Actif</option>
                                    <option value="inactive" <?= ($selected_user['status'] === 'inactive') ? 'selected' : '' ?>>Inactif</option>
                                </select>
                            </div>
                        </div>

                        <!-- Password Change Section -->
                        <div class="password-section">
                            <div class="password-toggle">
                                <input type="checkbox" id="change_password" name="change_password" value="1">
                                <label for="change_password">Changer le mot de passe</label>
                            </div>
                            
                            <div class="password-fields" id="passwordFields">
                                <div class="admin-password-section">
                                    <h4>Authentification Administrateur</h4>
                                    <p>Pour des raisons de sécurité, veuillez entrer votre mot de passe pour confirmer ce changement.</p>
                                    <div class="form-group">
                                        <label for="admin_password">Votre mot de passe <span class="required">*</span></label>
                                        <input type="password" id="admin_password" name="admin_password">
                                    </div>
                                </div>

                                <div class="password-requirements">
                                    <h4>Exigences du nouveau mot de passe</h4>
                                    <ul>
                                        <li>Au moins 8 caractères</li>
                                        <li>Au moins une lettre majuscule</li>
                                        <li>Au moins une lettre minuscule</li>
                                        <li>Au moins un chiffre</li>
                                    </ul>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="new_password">Nouveau mot de passe <span class="required">*</span></label>
                                        <input type="password" id="new_password" name="new_password" minlength="8">
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_new_password">Confirmer le nouveau mot de passe <span class="required">*</span></label>
                                        <input type="password" id="confirm_new_password" name="confirm_new_password" minlength="8">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="edit_user.php" class="btn btn-secondary btn-large">Annuler</a>
                            <button type="submit" name="update_user" class="btn btn-primary btn-large">Mettre à jour l'utilisateur</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="section">
                <div class="no-user-selected">
                    <h3>Aucun utilisateur sélectionné</h3>
                    <p>Veuillez sélectionner un utilisateur dans la liste ci-dessus pour le modifier.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            filterTable();
        });

        document.getElementById('statusFilter').addEventListener('change', function() {
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
            const roleFilter = document.getElementById('roleFilter') ? document.getElementById('roleFilter').value : '';
            
            const rows = document.querySelectorAll('#usersTableBody tr');
            
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                const status = row.getAttribute('data-status');
                const role = row.getAttribute('data-role');
                
                const matchesSearch = searchData.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesRole = !roleFilter || role === roleFilter;
                
                if (matchesSearch && matchesStatus && matchesRole) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Password change toggle
        const changePasswordCheckbox = document.getElementById('change_password');
        const passwordFields = document.getElementById('passwordFields');
        
        if (changePasswordCheckbox) {
            changePasswordCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    passwordFields.classList.add('show');
                    // Make password fields required
                    document.getElementById('admin_password').required = true;
                    document.getElementById('new_password').required = true;
                    document.getElementById('confirm_new_password').required = true;
                } else {
                    passwordFields.classList.remove('show');
                    // Make password fields not required
                    document.getElementById('admin_password').required = false;
                    document.getElementById('new_password').required = false;
                    document.getElementById('confirm_new_password').required = false;
                    // Clear password fields
                    document.getElementById('admin_password').value = '';
                    document.getElementById('new_password').value = '';
                    document.getElementById('confirm_new_password').value = '';
                }
            });
        }

        // Password confirmation validation
        const newPasswordInput = document.getElementById('new_password');
        const confirmNewPasswordInput = document.getElementById('confirm_new_password');
        
        if (confirmNewPasswordInput) {
            confirmNewPasswordInput.addEventListener('input', function() {
                const newPassword = newPasswordInput.value;
                const confirmNewPassword = this.value;
                
                if (newPassword !== confirmNewPassword) {
                    this.setCustomValidity('Les mots de passe ne correspondent pas');
                } else {
                    this.setCustomValidity('');
                }
            });
        }

        // Real-time password strength validation
        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /\d/.test(password)
                };
                
                // Trigger confirm password validation
                if (confirmNewPasswordInput && confirmNewPasswordInput.value) {
                    confirmNewPasswordInput.dispatchEvent(new Event('input'));
                }
            });
        }

        // Form submission validation
        const editForm = document.getElementById('editUserForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                const changePassword = document.getElementById('change_password').checked;
                
                if (changePassword) {
                    const adminPassword = document.getElementById('admin_password').value;
                    const newPassword = document.getElementById('new_password').value;
                    const confirmNewPassword = document.getElementById('confirm_new_password').value;
                    
                    if (!adminPassword) {
                        e.preventDefault();
                        alert('Veuillez entrer votre mot de passe administrateur');
                        return false;
                    }
                    
                    if (!newPassword) {
                        e.preventDefault();
                        alert('Veuillez entrer un nouveau mot de passe');
                        return false;
                    }
                    
                    if (newPassword !== confirmNewPassword) {
                        e.preventDefault();
                        alert('Les nouveaux mots de passe ne correspondent pas');
                        return false;
                    }
                    
                    if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(newPassword)) {
                        e.preventDefault();
                        alert('Le nouveau mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre');
                        return false;
                    }
                }
                
                // Confirm the update
                const userName = document.getElementById('first_name').value + ' ' + document.getElementById('last_name').value;
                const confirmMessage = changePassword 
                    ? `Êtes-vous sûr de vouloir mettre à jour l'utilisateur "${userName}" et changer son mot de passe?`
                    : `Êtes-vous sûr de vouloir mettre à jour l'utilisateur "${userName}"?`;
                    
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    </script>
</body>
</html>