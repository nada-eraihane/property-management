<?php
// Start output buffering to prevent header issues
ob_start();

require_once 'session_check.php';
require_once 'db.php';
$mysqli = $conn;

// Check if user is logged in and is admin
if (!isset($_SESSION['username'])) {
    ob_clean();
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

// Simple query to get current user info
$user_query = "SELECT u.user_id, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.username = '" . $mysqli->real_escape_string($current_username) . "'";
$result = $mysqli->query($user_query);

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $current_user_role = $row['role_name'];
    $current_user_id = $row['user_id'];
} else {
    die('User not found.');
}

// Check authorization
if (!in_array($current_user_role, ['Admin', 'Super Admin'])) {
    die('Access denied. Admin privileges required.');
}

// Get user ID from URL
$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) {
    header('Location: users_edit.php');
    exit;
}

// Get user details - simple query
$user_query = "SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.phone, 
               u.status, u.created_at, r.role_id, r.role_name,
               creator.username as created_by_username
               FROM users u 
               JOIN roles r ON u.role_id = r.role_id 
               LEFT JOIN users creator ON u.created_by = creator.user_id
               WHERE u.user_id = " . $user_id;

$result = $mysqli->query($user_query);

if (!$result || $result->num_rows === 0) {
    die('User not found.');
}

$user = $result->fetch_assoc();

// Check if current user can edit this user
$can_edit = false;
if ($current_user_role === 'Super Admin') {
    // Super Admin can edit all users except themselves
    $can_edit = ($user['user_id'] != $current_user_id);
} else if ($current_user_role === 'Admin') {
    // Regular Admin can only edit Customer accounts
    $can_edit = ($user['role_name'] === 'Customer');
}

if (!$can_edit) {
    die('Access denied. You do not have permission to edit this user.');
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
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

    // Basic validation
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
            // Check if username or email already exists for other users
            $check_query = "SELECT COUNT(*) as count FROM users WHERE (username = '" . $mysqli->real_escape_string($username) . "' OR email = '" . $mysqli->real_escape_string($email) . "') AND user_id != " . $user_id;
            $check_result = $mysqli->query($check_query);
            $check_row = $check_result->fetch_assoc();

            if ($check_row['count'] > 0) {
                $error = 'Ce nom d\'utilisateur ou cette adresse email est déjà utilisé par un autre utilisateur.';
            } else {
                // Password change validation
                if ($change_password) {
                    if (!$admin_password) {
                        $error = 'Votre mot de passe administrateur est requis pour changer le mot de passe utilisateur.';
                    } elseif (strlen($new_password) < 8) {
                        $error = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
                    } elseif ($new_password !== $confirm_new_password) {
                        $error = 'Les nouveaux mots de passe ne correspondent pas.';
                    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
                        $error = 'Le nouveau mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.';
                    } else {
                        // Verify admin password
                        $admin_query = "SELECT password_hash FROM users WHERE user_id = " . $current_user_id;
                        $admin_result = $mysqli->query($admin_query);
                        $admin_row = $admin_result->fetch_assoc();

                        if (!password_verify($admin_password, $admin_row['password_hash'])) {
                            $error = 'Mot de passe administrateur incorrect.';
                        }
                    }
                }

                if (!$error) {
                    // Build and execute update query
                    $role_id = (int)$role_id;
                    $user_id = (int)$user_id;
                    
                    // Escape all string values for safety
                    $username_escaped = $mysqli->real_escape_string($username);
                    $email_escaped = $mysqli->real_escape_string($email);
                    $first_name_escaped = $mysqli->real_escape_string($first_name);
                    $last_name_escaped = $mysqli->real_escape_string($last_name);
                    $status_escaped = $mysqli->real_escape_string($status);
                    
                    // Handle phone field
                    if (empty($phone)) {
                        $phone_sql = "NULL";
                    } else {
                        $phone_sql = "'" . $mysqli->real_escape_string($phone) . "'";
                    }
                    
                    if ($change_password) {
                        // Update with password change
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $password_hash_escaped = $mysqli->real_escape_string($password_hash);
                        
                        $sql = "UPDATE users SET 
                                username = '$username_escaped',
                                email = '$email_escaped',
                                first_name = '$first_name_escaped',
                                last_name = '$last_name_escaped',
                                phone = $phone_sql,
                                role_id = $role_id,
                                status = '$status_escaped',
                                password_hash = '$password_hash_escaped'
                                WHERE user_id = $user_id";
                    } else {
                        // Update without password change
                        $sql = "UPDATE users SET 
                                username = '$username_escaped',
                                email = '$email_escaped',
                                first_name = '$first_name_escaped',
                                last_name = '$last_name_escaped',
                                phone = $phone_sql,
                                role_id = $role_id,
                                status = '$status_escaped'
                                WHERE user_id = $user_id";
                    }
                    
                    // Execute the query
                    if ($mysqli->query($sql)) {
                        ob_clean();
                        header('Location: users_edit.php?success=1');
                        exit;
                    } else {
                        $error = 'Erreur lors de la mise à jour: ' . $mysqli->error;
                    }
                }
            }
        }
    }
}

// Include sidenav after all processing is complete
include 'sidenav.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'Utilisateur - <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></title>
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
            max-width: 900px;
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
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .content {
            padding: 40px;
        }

        .user-info-card {
            background: var(--surface-bg);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid var(--accent-text);
        }

        .user-info-card h3 {
            color: var(--secondary-text);
            margin-bottom: 15px;
        }

        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-size: 12px;
            color: var(--accent-text);
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-value {
            color: var(--secondary-text);
            font-weight: 500;
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
            border-radius: 15px;
            margin: 30px 0;
            border-left: 5px solid var(--warning-color);
        }

        .password-section h4 {
            color: var(--secondary-text);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }

        .password-fields {
            display: none;
            gap: 20px;
        }

        .password-fields.active {
            display: flex;
            flex-direction: column;
        }

        .security-note {
            background: rgba(162, 20, 20, 0.1);
            color: var(--highlight-color);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 14px;
            border: 1px solid rgba(162, 20, 20, 0.2);
        }

        .password-requirements {
            background: var(--primary-bg);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border: 1px solid var(--accent-bg);
        }

        .password-requirements h5 {
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

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--accent-bg);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--highlight-color) 0%, #8b1212 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(162, 20, 20, 0.3);
        }

        .btn-secondary {
            background: var(--accent-bg);
            color: var(--primary-text);
        }

        .btn-secondary:hover {
            background: var(--accent-text);
            color: white;
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

        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
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

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-active {
            background: rgba(45, 125, 45, 0.1);
            color: var(--success-color);
        }

        .status-inactive {
            background: rgba(162, 20, 20, 0.1);
            color: var(--highlight-color);
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .form-actions {
                flex-direction: column;
            }

            .user-info-grid {
                grid-template-columns: 1fr;
            }

            .password-fields {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Modifier l'Utilisateur</h1>
            <p><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (@<?= htmlspecialchars($user['username']) ?>)</p>
        </div>

        <div class="content">
            <!-- Current User Information -->
            <div class="user-info-card">
                <h3>Informations actuelles</h3>
                <div class="user-info-grid">
                    <div class="info-item">
                        <span class="info-label">Nom complet</span>
                        <span class="info-value"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Rôle actuel</span>
                        <span class="role-badge role-<?= strtolower(str_replace(' ', '-', $user['role_name'])) ?>">
                            <?= htmlspecialchars($user['role_name']) ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Statut actuel</span>
                        <span class="status-badge status-<?= $user['status'] ?>">
                            <?= $user['status'] === 'active' ? 'Actif' : 'Inactif' ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Créé le</span>
                        <span class="info-value"><?= date('d/m/Y à H:i', strtotime($user['created_at'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Créé par</span>
                        <span class="info-value"><?= $user['created_by_username'] ? htmlspecialchars($user['created_by_username']) : 'Système' ?></span>
                    </div>
                </div>
            </div>

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

            <form method="POST" id="editUserForm">
                <!-- Basic Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur <span class="required">*</span></label>
                        <input type="text" id="username" name="username" required 
                               minlength="3" maxlength="50" 
                               value="<?= htmlspecialchars($user['username']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Adresse email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required 
                               maxlength="100" 
                               value="<?= htmlspecialchars($user['email']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">Prénom <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" required 
                               maxlength="50" 
                               value="<?= htmlspecialchars($user['first_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Nom <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" required 
                               maxlength="50" 
                               value="<?= htmlspecialchars($user['last_name']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <input type="tel" id="phone" name="phone" 
                           pattern="[\d\s\-\+\(\)]{10,20}" 
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="role_id">Rôle <span class="required">*</span></label>
                        <select id="role_id" name="role_id" required>
                            <option value="">Sélectionnez un rôle</option>
                            <?php foreach ($available_roles as $role): ?>
                                <option value="<?= $role['role_id'] ?>" 
                                        <?= ($user['role_id'] == $role['role_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Statut</label>
                        <select id="status" name="status">
                            <option value="active" <?= ($user['status'] === 'active') ? 'selected' : '' ?>>Actif</option>
                            <option value="inactive" <?= ($user['status'] === 'inactive') ? 'selected' : '' ?>>Inactif</option>
                        </select>
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="password-section">
                    <h4>🔒 Changement de mot de passe</h4>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="change_password" name="change_password" value="1">
                        <label for="change_password">Je souhaite changer le mot de passe de cet utilisateur</label>
                    </div>

                    <div class="security-note">
                        <strong>Sécurité:</strong> Pour changer le mot de passe d'un utilisateur, vous devez saisir votre propre mot de passe administrateur pour confirmer cette action.
                    </div>

                    <div id="passwordFields" class="password-fields">
                        <div class="password-requirements">
                            <h5>Exigences du nouveau mot de passe</h5>
                            <ul>
                                <li>Au moins 8 caractères</li>
                                <li>Au moins une lettre majuscule</li>
                                <li>Au moins une lettre minuscule</li>
                                <li>Au moins un chiffre</li>
                            </ul>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe</label>
                                <input type="password" id="new_password" name="new_password" 
                                       minlength="8">
                            </div>
                            <div class="form-group">
                                <label for="confirm_new_password">Confirmer le nouveau mot de passe</label>
                                <input type="password" id="confirm_new_password" name="confirm_new_password" 
                                       minlength="8">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="admin_password">Votre mot de passe administrateur <span class="required">*</span></label>
                            <input type="password" id="admin_password" name="admin_password" 
                                   placeholder="Saisissez votre mot de passe pour confirmer">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="users_edit.php" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password fields
        document.getElementById('change_password').addEventListener('change', function() {
            const passwordFields = document.getElementById('passwordFields');
            const newPasswordField = document.getElementById('new_password');
            const confirmNewPasswordField = document.getElementById('confirm_new_password');
            const adminPasswordField = document.getElementById('admin_password');
            
            if (this.checked) {
                passwordFields.classList.add('active');
                newPasswordField.required = true;
                confirmNewPasswordField.required = true;
                adminPasswordField.required = true;
            } else {
                passwordFields.classList.remove('active');
                newPasswordField.required = false;
                confirmNewPasswordField.required = false;
                adminPasswordField.required = false;
                newPasswordField.value = '';
                confirmNewPasswordField.value = '';
                adminPasswordField.value = '';
            }
        });

        // Password confirmation validation
        document.getElementById('confirm_new_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                this.setCustomValidity('');
            }
        });

        // Real-time password strength validation
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password)
            };
            
            // Visual feedback could be added here
            const confirmPassword = document.getElementById('confirm_new_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });

        // Form submission validation
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            const changePassword = document.getElementById('change_password').checked;
            
            if (changePassword) {
                const newPassword = document.getElementById('new_password').value;
                const confirmNewPassword = document.getElementById('confirm_new_password').value;
                const adminPassword = document.getElementById('admin_password').value;
                
                if (!adminPassword) {
                    e.preventDefault();
                    alert('Votre mot de passe administrateur est requis pour changer le mot de passe utilisateur.');
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
        });
    </script>
</body>
</html>