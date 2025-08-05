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

// Get current user's role from database
$current_user_role = null;
$current_username = $_SESSION['username'];

if ($mysqli->connect_error) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$stmt = $mysqli->prepare("
    SELECT r.role_name 
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
}
$stmt->close();

// Check authorization
if (!in_array($current_user_role, ['Admin', 'Super Admin'])) {
    die('Access denied. Admin privileges required.');
}

// Get available roles based on current user's permissions
$available_roles = [];
if ($current_user_role === 'Super Admin') {
    // Super Admin can create all roles
    $roles_query = "SELECT role_id, role_name FROM roles ORDER BY role_name";
} else {
    // Regular Admin can only create Customer accounts
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
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    // Validation
    if (!$username || !$email || !$password || !$first_name || !$last_name || !$role_id) {
        $error = 'Tous les champs obligatoires doivent être remplis.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $error = 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.';
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
            $error = 'Vous n\'êtes pas autorisé à créer un utilisateur avec ce rôle.';
        } else {
            // Check if username or email already exists
            $check_stmt = $mysqli->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $check_stmt->bind_param('ss', $username, $email);
            $check_stmt->execute();
            $check_stmt->bind_result($count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($count > 0) {
                $error = 'Ce nom d\'utilisateur ou cette adresse email est déjà utilisé.';
            } else {
                // Get current user ID for created_by field
                $creator_stmt = $mysqli->prepare("SELECT user_id FROM users WHERE username = ?");
                $creator_stmt->bind_param('s', $current_username);
                $creator_stmt->execute();
                $creator_stmt->bind_result($created_by);
                $creator_stmt->fetch();
                $creator_stmt->close();

                // Hash password and insert user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $insert_stmt = $mysqli->prepare("
                    INSERT INTO users (username, email, password_hash, first_name, last_name, phone, role_id, status, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insert_stmt->bind_param('ssssssssi', 
                    $username, $email, $password_hash, $first_name, $last_name, 
                    $phone, $role_id, $status, $created_by
                );

                if ($insert_stmt->execute()) {
                    $success = 'Utilisateur créé avec succès!';
                    // Clear form data
                    $username = $email = $password = $confirm_password = $first_name = $last_name = $phone = '';
                    $role_id = 0;
                    $status = 'active';
                } else {
                    $error = 'Erreur lors de la création de l\'utilisateur: ' . $mysqli->error;
                }
                $insert_stmt->close();
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
    <title>Ajouter un Utilisateur</title>
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
            max-width: 800px;
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

        .password-requirements {
            background: var(--surface-bg);
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
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

        .role-info {
            background: var(--surface-bg);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid var(--accent-text);
        }

        .role-info h4 {
            color: var(--secondary-text);
            margin-bottom: 8px;
        }

        .role-info p {
            color: var(--accent-text);
            font-size: 14px;
        }

        @media (max-width: 768px) {
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
            <h1>Ajouter un Utilisateur</h1>
            <p>Créer un nouveau compte utilisateur dans le système</p>
        </div>

        <div class="content">
            <div class="role-info">
                <h4>Vos permissions actuelles</h4>
                <p>
                    <?php if ($current_user_role === 'Super Admin'): ?>
                        En tant que Super Admin, vous pouvez créer tous types d'utilisateurs.
                    <?php else: ?>
                        En tant qu'Admin, vous pouvez uniquement créer des comptes Client.
                    <?php endif; ?>
                </p>
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

            <form method="POST" id="userForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur <span class="required">*</span></label>
                        <input type="text" id="username" name="username" required 
                               minlength="3" maxlength="50" 
                               value="<?= htmlspecialchars($username ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Adresse email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required 
                               maxlength="100" 
                               value="<?= htmlspecialchars($email ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">Prénom <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" required 
                               maxlength="50" 
                               value="<?= htmlspecialchars($first_name ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Nom <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" required 
                               maxlength="50" 
                               value="<?= htmlspecialchars($last_name ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <input type="tel" id="phone" name="phone" 
                           pattern="[\d\s\-\+\(\)]{10,20}" 
                           value="<?= htmlspecialchars($phone ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="role_id">Rôle <span class="required">*</span></label>
                        <select id="role_id" name="role_id" required>
                            <option value="">Sélectionnez un rôle</option>
                            <?php foreach ($available_roles as $role): ?>
                                <option value="<?= $role['role_id'] ?>" 
                                        <?= (isset($role_id) && $role_id == $role['role_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Statut</label>
                        <select id="status" name="status">
                            <option value="active" <?= (isset($status) && $status === 'active') ? 'selected' : '' ?>>Actif</option>
                            <option value="inactive" <?= (isset($status) && $status === 'inactive') ? 'selected' : '' ?>>Inactif</option>
                        </select>
                    </div>
                </div>

                <div class="password-requirements">
                    <h4>Exigences du mot de passe</h4>
                    <ul>
                        <li>Au moins 8 caractères</li>
                        <li>Au moins une lettre majuscule</li>
                        <li>Au moins une lettre minuscule</li>
                        <li>Au moins un chiffre</li>
                    </ul>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Mot de passe <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required 
                               minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le mot de passe <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               minlength="8">
                    </div>
                </div>

                <div class="form-actions">
                    <a href="users.php" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                this.setCustomValidity('');
            }
        });

        // Real-time password strength validation
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password)
            };
            
            // Visual feedback could be added here
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });

        // Form submission validation
        document.getElementById('userForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
                return false;
            }
            
            if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre');
                return false;
            }
        });
    </script>
</body>
</html>