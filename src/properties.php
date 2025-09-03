<?php
require_once 'session_check.php';
require_once 'db.php';
$mysqli = $conn;

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$current_user_role = null;
$current_username = $_SESSION['username'];
$current_user_id = null;

$stmt = $mysqli->prepare("SELECT u.user_id, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.username = ?");
$stmt->bind_param('s', $current_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $current_user_role = $row['role_name'];
    $current_user_id = $row['user_id'];
}
$stmt->close();

if (!in_array($current_user_role, ['Admin', 'Super Admin'])) {
    die('Access denied. Admin privileges required.');
}

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$construction_filter = isset($_GET['construction_status']) ? $_GET['construction_status'] : '';
$sale_filter = isset($_GET['sale_status']) ? $_GET['sale_status'] : '';
$city_filter = isset($_GET['city']) ? trim($_GET['city']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : '';

// Build WHERE clause
$where_conditions = ['p.is_active = 1'];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(p.property_code LIKE ? OR p.description LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($construction_filter)) {
    $where_conditions[] = "p.construction_status = ?";
    $params[] = $construction_filter;
    $types .= 's';
}

if (!empty($sale_filter)) {
    $where_conditions[] = "p.sale_status = ?";
    $params[] = $sale_filter;
    $types .= 's';
}

if (!empty($city_filter)) {
    $where_conditions[] = "p.city LIKE ?";
    $params[] = '%' . $city_filter . '%';
    $types .= 's';
}

if (!empty($min_price)) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
    $types .= 'd';
}

if (!empty($max_price)) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM properties p WHERE $where_clause";
$count_stmt = $mysqli->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_records / $per_page);
$count_stmt->close();

// Get properties with primary images
$sql = "SELECT p.*, 
               pi.file_path as primary_image,
               u.username as created_by_username
        FROM properties p 
        LEFT JOIN property_images pi ON p.property_id = pi.property_id AND pi.is_primary = 1
        LEFT JOIN users u ON p.created_by = u.user_id
        WHERE $where_clause 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN sale_status = 'available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN sale_status = 'sold' THEN 1 ELSE 0 END) as sold,
    SUM(CASE WHEN construction_status IN ('foundation', 'framing', 'roofing', 'plumbing', 'electrical') THEN 1 ELSE 0 END) as under_construction
FROM properties WHERE is_active = 1";

$stats_result = $mysqli->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get unique cities for filter dropdown
$cities_sql = "SELECT DISTINCT city FROM properties WHERE is_active = 1 ORDER BY city";
$cities_result = $mysqli->query($cities_sql);
$cities = $cities_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Propriétés</title>
    <style>
        :root {
            --primary-bg: #ffffff;
            --secondary-bg: #fff7ea;
            --accent-bg: #c8d9e6;
            --surface-bg: #f5efeb;
            --primary-text: #2e4156;
            --secondary-text: #1b2639;
            --accent-text: #567c8d;
            --highlight-color: #a21414;
            --success-color: #2d7d2d;
            --warning-color: #d97706;
            --danger-color: #dc2626;
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
            background: linear-gradient(135deg, var(--success-color) 0%, #166534 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #1e5d1e;
        }

        .btn-secondary {
            background: var(--accent-bg);
            color: var(--primary-text);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
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
            background: var(--primary-bg);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(46, 65, 86, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--success-color);
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--secondary-text);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
        }

        .filters-section {
            background: var(--primary-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(46, 65, 86, 0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--secondary-text);
            font-size: 14px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 2px solid var(--accent-bg);
            border-radius: 8px;
            font-size: 14px;
            background: var(--surface-bg);
            color: var(--primary-text);
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--success-color);
            background: var(--primary-bg);
        }

        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .property-card {
            background: var(--primary-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(46, 65, 86, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(46, 65, 86, 0.2);
        }

        .property-image {
            height: 200px;
            background: var(--accent-bg);
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .property-image.no-image {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-text);
            font-size: 3rem;
        }

        .property-status {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-available {
            background: var(--success-color);
            color: white;
        }

        .status-under_contract {
            background: var(--warning-color);
            color: white;
        }

        .status-sold {
            background: var(--danger-color);
            color: white;
        }

        .status-on_hold {
            background: var(--accent-text);
            color: white;
        }

        .property-details {
            padding: 20px;
        }

        .property-code {
            font-size: 18px;
            font-weight: 700;
            color: var(--secondary-text);
            margin-bottom: 10px;
        }

        .property-description {
            color: var(--primary-text);
            margin-bottom: 15px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .property-price {
            font-size: 20px;
            font-weight: 700;
            color: var(--success-color);
            margin-bottom: 15px;
        }

        .property-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: var(--accent-text);
        }

        .property-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .construction-progress {
            margin-bottom: 15px;
        }

        .progress-label {
            font-size: 12px;
            color: var(--secondary-text);
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .progress-bar {
            height: 8px;
            background: var(--accent-bg);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--success-color);
            transition: width 0.3s ease;
        }

        .property-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--accent-bg);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pagination a {
            background: var(--primary-bg);
            color: var(--primary-text);
            border: 2px solid var(--accent-bg);
        }

        .pagination a:hover {
            background: var(--success-color);
            color: white;
            border-color: var(--success-color);
        }

        .pagination .current {
            background: var(--success-color);
            color: white;
            border: 2px solid var(--success-color);
        }

        .no-properties {
            text-align: center;
            padding: 60px 20px;
            color: var(--accent-text);
        }

        .no-properties h3 {
            font-size: 24px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .properties-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidenav.php'; ?>
    
    <div class="container">
        <div class="header">
            <div>
                <h1>Gestion des Propriétés</h1>
                <p>Gérer toutes les propriétés du système</p>
            </div>
            <div>
                <a href="property_create.php" class="btn btn-primary">Ajouter une propriété</a>
            </div>
        </div>

        <div class="content">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Propriétés</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['available'] ?></div>
                    <div class="stat-label">Disponibles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['sold'] ?></div>
                    <div class="stat-label">Vendues</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['under_construction'] ?></div>
                    <div class="stat-label">En Construction</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" action="">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="search">Rechercher</label>
                            <input type="text" id="search" name="search" placeholder="Code ou description..." value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <div class="filter-group">
                            <label for="construction_status">État de construction</label>
                            <select name="construction_status" id="construction_status">
                                <option value="">Tous les états</option>
                                <option value="foundation" <?= $construction_filter === 'foundation' ? 'selected' : '' ?>>Fondation</option>
                                <option value="framing" <?= $construction_filter === 'framing' ? 'selected' : '' ?>>Charpente</option>
                                <option value="roofing" <?= $construction_filter === 'roofing' ? 'selected' : '' ?>>Toiture</option>
                                <option value="plumbing" <?= $construction_filter === 'plumbing' ? 'selected' : '' ?>>Plomberie</option>
                                <option value="electrical" <?= $construction_filter === 'electrical' ? 'selected' : '' ?>>Électricité</option>
                                <option value="finishing" <?= $construction_filter === 'finishing' ? 'selected' : '' ?>>Finitions</option>
                                <option value="final_inspection" <?= $construction_filter === 'final_inspection' ? 'selected' : '' ?>>Inspection finale</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="sale_status">Statut de vente</label>
                            <select name="sale_status" id="sale_status">
                                <option value="">Tous les statuts</option>
                                <option value="available" <?= $sale_filter === 'available' ? 'selected' : '' ?>>Disponible</option>
                                <option value="under_contract" <?= $sale_filter === 'under_contract' ? 'selected' : '' ?>>Sous contrat</option>
                                <option value="sold" <?= $sale_filter === 'sold' ? 'selected' : '' ?>>Vendu</option>
                                <option value="on_hold" <?= $sale_filter === 'on_hold' ? 'selected' : '' ?>>En attente</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="city">Ville</label>
                            <select name="city" id="city">
                                <option value="">Toutes les villes</option>
                                <?php foreach ($cities as $city): ?>
                                <option value="<?= htmlspecialchars($city['city']) ?>" <?= $city_filter === $city['city'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($city['city']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="min_price">Prix min (DA)</label>
                            <input type="number" id="min_price" name="min_price" placeholder="0" value="<?= htmlspecialchars($min_price) ?>">
                        </div>

                        <div class="filter-group">
                            <label for="max_price">Prix max (DA)</label>
                            <input type="number" id="max_price" name="max_price" placeholder="1000000" value="<?= htmlspecialchars($max_price) ?>">
                        </div>

                        <div class="filter-group">
                            <button type="submit" class="btn btn-success">Filtrer</button>
                        </div>

                        <div class="filter-group">
                            <a href="properties.php" class="btn btn-secondary">Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Properties Grid -->
            <?php if (!empty($properties)): ?>
            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                <div class="property-card">
                    <div class="property-image <?= !$property['primary_image'] ? 'no-image' : '' ?>" 
                         <?= $property['primary_image'] ? 'style="background-image: url(\'' . htmlspecialchars($property['primary_image']) . '\')"' : '' ?>>
                        
                        <?php if (!$property['primary_image']): ?>
                            🏠
                        <?php endif; ?>
                        
                        <div class="property-status status-<?= $property['sale_status'] ?>">
                            <?php
                            $status_labels = [
                                'available' => 'Disponible',
                                'under_contract' => 'Sous contrat',
                                'sold' => 'Vendu',
                                'on_hold' => 'En attente'
                            ];
                            echo $status_labels[$property['sale_status']] ?? $property['sale_status'];
                            ?>
                        </div>
                    </div>

                    <div class="property-details">
                        <div class="property-code"><?= htmlspecialchars($property['property_code']) ?></div>
                        <div class="property-description"><?= htmlspecialchars($property['description']) ?></div>
                        
                        <?php if ($property['price'] > 0): ?>
                        <div class="property-price"><?= number_format($property['price'], 0, ',', ' ') ?> DA</div>
                        <?php endif; ?>

                        <div class="property-meta">
                            <span>🛏️ <?= $property['bedrooms'] ?> chambres</span>
                            <span>🚿 <?= $property['bathrooms'] ?> SDB</span>
                            <?php if ($property['surface']): ?>
                            <span>📐 <?= $property['surface'] ?>m²</span>
                            <?php endif; ?>
                        </div>

                        <div class="construction-progress">
                            <div class="progress-label">
                                <span><?php
                                    $construction_labels = [
                                        'foundation' => 'Fondation',
                                        'framing' => 'Charpente', 
                                        'roofing' => 'Toiture',
                                        'plumbing' => 'Plomberie',
                                        'electrical' => 'Électricité',
                                        'finishing' => 'Finitions',
                                        'final_inspection' => 'Inspection finale'
                                    ];
                                    echo $construction_labels[$property['construction_status']] ?? $property['construction_status'];
                                ?></span>
                                <span><?= $property['completion_percentage'] ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $property['completion_percentage'] ?>%"></div>
                            </div>
                        </div>

                        <div class="property-meta">
                            <span>📍 <?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars($property['state']) ?></span>
                            <span>👤 <?= htmlspecialchars($property['created_by_username']) ?></span>
                        </div>

                        <div class="property-actions">
                            <a href="property_announcements_edit.php?id=<?= $property['property_id'] ?>" class="btn btn-success btn-sm">
                                ✏️ Modifier
                            </a>
                            <?php if ($current_user_role === 'Super Admin'): ?>
                            <a href="property_delete.php?id=<?= $property['property_id'] ?>" class="btn btn-danger btn-sm" 
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette propriété?')">
                                🗑️ Supprimer
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">← Précédent</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Suivant →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="no-properties">
                <h3>Aucune propriété trouvée</h3>
                <p>Aucune propriété ne correspond à vos critères de recherche.</p>
                <a href="property_create.php" class="btn btn-success" style="margin-top: 20px;">Créer une nouvelle propriété</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>