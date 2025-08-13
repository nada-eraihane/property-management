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



// Build query based on user role
if ($current_user_role === 'Super Admin') {
    // Super Admin can see all users
    $users_query = "
        SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.phone, 
               u.status, u.created_at, r.role_name,
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
               u.status, u.created_at, r.role_name,
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
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

        .btn-secondary {
            background: var(--accent-bg);
            color: var(--primary-text);
        }

        .btn-secondary:hover {
            background: var(--accent-text);
            color: white;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-danger {
            background: var(--highlight-color);
            color: white;
        }

        .btn-danger:hover {
            background: #8b1212;
        }

        .content {
            padding: 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .password-field {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .password-hidden {
            font-family: monospace;
            color: var(--accent-text);
            font-size: 12px;
        }

        .password-revealed {
            font-family: monospace;
            color: var(--secondary-text);
            font-size: 10px;
            background: var(--surface-bg);
            padding: 4px 8px;
            border-radius: 4px;
            max-width: 200px;
            word-break: break-all;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>Gestion des Utilisateurs</h1>
                <p>
                    <?php if ($current_user_role === 'Super Admin'): ?>
                        Vue complète de tous les utilisateurs du système
                    <?php else: ?>
                        Vue des comptes clients uniquement
                    <?php endif; ?>
                </p>
            </div>
            <div class="header-actions">
                <a href="add_user.php" class="btn btn-primary">
                    + Ajouter un utilisateur
                </a>
            </div>
        </div>

        <div class="content">
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total des utilisateurs</h3>
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
                <?php if ($current_user_role === 'Super Admin'): ?>
                <div class="stat-card">
                    <h3>Administrateurs</h3>
                    <div class="number"><?= count(array_filter($users, fn($u) => in_array($u['role_name'], ['Admin', 'Super Admin']))) ?></div>
                </div>
                <?php endif; ?>
            </div>

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
                                <th>Créé par</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php foreach ($users as $user): ?>
                                <tr data-user-id="<?= $user['user_id'] ?>" 
                                    data-status="<?= $user['status'] ?>" 
                                    data-role="<?= $user['role_name'] ?>"
                                    data-search="<?= strtolower($user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['email'] . ' ' . $user['username'] . ' ' . ($user['phone'] ?? '')) ?>">
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
                                    <td><?= $user['created_by_username'] ? htmlspecialchars($user['created_by_username']) : 'Système' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
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
    </script>
</body>
</html>