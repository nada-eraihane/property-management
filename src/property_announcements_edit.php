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

// Get property ID from URL
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$property_id) {
    header('Location: properties.php');
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

// Get property data
$property = null;
$stmt = $mysqli->prepare("SELECT * FROM properties WHERE property_id = ? AND is_active = 1");
$stmt->bind_param('i', $property_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $property = $result->fetch_assoc();
} else {
    header('Location: properties.php');
    exit;
}
$stmt->close();

// Get existing images
$existing_images = [];
$stmt = $mysqli->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY sort_order, image_id");
$stmt->bind_param('i', $property_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $existing_images[] = $row;
}
$stmt->close();

// Get existing videos
$existing_videos = [];
$stmt = $mysqli->prepare("SELECT * FROM property_videos WHERE property_id = ? ORDER BY video_id");
$stmt->bind_param('i', $property_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $existing_videos[] = $row;
}
$stmt->close();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_property') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = 'Token de sécurité invalide. Veuillez actualiser la page.';
    } else {
        try {
            $mysqli->begin_transaction();
            
            $required_fields = ['property_code', 'description', 'address_line1', 'city', 'state', 'postal_code', 'bedrooms', 'bathrooms', 'construction_status'];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Le champ $field est requis");
                }
            }
            
            if (!preg_match('/^[A-Z]-\d{4}$/', $_POST['property_code'])) {
                throw new Exception('Format du code propriété invalide (ex: A-1234)');
            }
            
            // Check if property code exists for other properties
            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM properties WHERE property_code = ? AND property_id != ?");
            $stmt->bind_param('si', $_POST['property_code'], $property_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_row()[0];
            $stmt->close();
            
            if ($count > 0) {
                throw new Exception('Ce code de propriété existe déjà pour une autre propriété');
            }
            
            $address_line2 = !empty($_POST['address_line2']) ? $_POST['address_line2'] : null;
            $surface = !empty($_POST['surface']) ? (int)$_POST['surface'] : null;
            $price = !empty($_POST['price']) ? (float)$_POST['price'] : 0.00;
            $completion_percentage = (int)($_POST['completion_percentage'] ?? 0);
            $estimated_completion = !empty($_POST['estimated_completion']) ? $_POST['estimated_completion'] : null;
            $sale_status = $_POST['sale_status'] ?? 'available';
            $bedrooms = (int)$_POST['bedrooms'];
            $bathrooms = (float)$_POST['bathrooms'];
            $construction_status = $_POST['construction_status'];
            
            // Update property
            $stmt = $mysqli->prepare("
                UPDATE properties SET 
                    property_code = ?, description = ?, address_line1 = ?, address_line2 = ?, 
                    city = ?, state = ?, postal_code = ?, price = ?, bedrooms = ?, bathrooms = ?, 
                    surface = ?, construction_status = ?, completion_percentage = ?, 
                    estimated_completion = ?, sale_status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE property_id = ?
            ");
            
            $stmt->bind_param('sssssssdidssissi',
                $_POST['property_code'],
                $_POST['description'],
                $_POST['address_line1'],
                $address_line2,
                $_POST['city'],
                $_POST['state'],
                $_POST['postal_code'],
                $price,
                $bedrooms,
                $bathrooms,
                $surface,
                $construction_status,
                $completion_percentage,
                $estimated_completion,
                $sale_status,
                $property_id
            );
            
            $stmt->execute();
            $stmt->close();
            
            // Handle image deletion
            if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $image_id) {
                    $image_id = (int)$image_id;
                    
                    // Get image path before deletion
                    $stmt = $mysqli->prepare("SELECT file_path FROM property_images WHERE image_id = ? AND property_id = ?");
                    $stmt->bind_param('ii', $image_id, $property_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($row = $result->fetch_assoc()) {
                        $file_path = $row['file_path'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                        
                        // Delete from database
                        $stmt_delete = $mysqli->prepare("DELETE FROM property_images WHERE image_id = ? AND property_id = ?");
                        $stmt_delete->bind_param('ii', $image_id, $property_id);
                        $stmt_delete->execute();
                        $stmt_delete->close();
                    }
                    $stmt->close();
                }
            }
            
            // Handle video deletion
            if (isset($_POST['delete_videos']) && is_array($_POST['delete_videos'])) {
                foreach ($_POST['delete_videos'] as $video_id) {
                    $video_id = (int)$video_id;
                    
                    // Get video path before deletion
                    $stmt = $mysqli->prepare("SELECT file_path FROM property_videos WHERE video_id = ? AND property_id = ?");
                    $stmt->bind_param('ii', $video_id, $property_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($row = $result->fetch_assoc()) {
                        $file_path = $row['file_path'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                        
                        // Delete from database
                        $stmt_delete = $mysqli->prepare("DELETE FROM property_videos WHERE video_id = ? AND property_id = ?");
                        $stmt_delete->bind_param('ii', $video_id, $property_id);
                        $stmt_delete->execute();
                        $stmt_delete->close();
                    }
                    $stmt->close();
                }
            }
            
            // Handle new images
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/properties/' . $property_id . '/images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['images']['name'] as $key => $filename) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (!in_array($file_extension, $allowed_extensions)) {
                            continue;
                        }
                        
                        $new_filename = uniqid() . '.' . $file_extension;
                        $file_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $file_path)) {
                            // Get max sort order
                            $stmt = $mysqli->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM property_images WHERE property_id = ?");
                            $stmt->bind_param('i', $property_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $next_order = $result->fetch_assoc()['next_order'];
                            $stmt->close();
                            
                            $stmt = $mysqli->prepare("INSERT INTO property_images (property_id, file_name, file_path, alt_text, is_primary, sort_order, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            
                            $is_primary = false; // Don't auto-set primary for new images in edit
                            $stmt->bind_param('isssbii', $property_id, $new_filename, $file_path, $filename, $is_primary, $next_order, $current_user_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
            
            // Handle new videos
            if (!empty($_FILES['videos']['name'][0])) {
                $upload_dir = 'uploads/properties/' . $property_id . '/videos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['videos']['name'] as $key => $filename) {
                    if ($_FILES['videos']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_extensions = ['mp4', 'mov', 'avi', 'wmv'];
                        
                        if (!in_array($file_extension, $allowed_extensions)) {
                            continue;
                        }
                        
                        $new_filename = uniqid() . '.' . $file_extension;
                        $file_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['videos']['tmp_name'][$key], $file_path)) {
                            $stmt = $mysqli->prepare("INSERT INTO property_videos (property_id, file_name, file_path, title, description, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                            
                            $video_title = $filename;
                            $video_description = 'Vidéo de propriété';
                            $stmt->bind_param('issssi', $property_id, $new_filename, $file_path, $video_title, $video_description, $current_user_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
            
            $mysqli->commit();
            $success_message = "Propriété mise à jour avec succès ! Code: " . $_POST['property_code'];
            
            // Refresh property data and media after update
            $stmt = $mysqli->prepare("SELECT * FROM properties WHERE property_id = ?");
            $stmt->bind_param('i', $property_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $property = $result->fetch_assoc();
            $stmt->close();
            
            // Refresh existing images
            $existing_images = [];
            $stmt = $mysqli->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY sort_order, image_id");
            $stmt->bind_param('i', $property_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $existing_images[] = $row;
            }
            $stmt->close();
            
            // Refresh existing videos
            $existing_videos = [];
            $stmt = $mysqli->prepare("SELECT * FROM property_videos WHERE property_id = ? ORDER BY video_id");
            $stmt->bind_param('i', $property_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $existing_videos[] = $row;
            }
            $stmt->close();
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $error_message = $e->getMessage();
        }
    }
}

// Use form data if available, otherwise use property data
$form_data = $_POST ?? $property;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la Propriété - <?= htmlspecialchars($property['property_code']) ?></title>
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
            max-width: 1200px;
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

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-secondary {
            background: var(--accent-bg);
            color: var(--primary-text);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .content {
            padding: 40px;
        }

        .success-message, .error-message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message {
            background: #f0f9f0;
            color: var(--success-color);
            border: 1px solid #c3e6c3;
        }

        .error-message {
            background: #fee;
            color: var(--danger-color);
            border: 1px solid #fed7d7;
        }

        .form-container {
            background: var(--primary-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(46, 65, 86, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--secondary-text);
            font-size: 14px;
        }

        .required {
            color: var(--danger-color);
        }

        input, textarea, select {
            padding: 12px 15px;
            border: 2px solid var(--accent-bg);
            border-radius: 10px;
            font-size: 14px;
            background: var(--surface-bg);
            color: var(--primary-text);
            transition: all 0.3s ease;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--success-color);
            background: var(--primary-bg);
            box-shadow: 0 0 0 3px rgba(45, 125, 45, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .media-section {
            background: var(--surface-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .media-section h3 {
            color: var(--secondary-text);
            margin-bottom: 15px;
            font-size: 18px;
        }

        .existing-media {
            margin-bottom: 20px;
        }

        .existing-media h4 {
            color: var(--secondary-text);
            margin-bottom: 10px;
            font-size: 16px;
        }

        .existing-media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .existing-media-item {
            position: relative;
            background: var(--primary-bg);
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid var(--accent-bg);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .existing-media-item img, 
        .existing-media-item video {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .existing-media-item .file-info {
            padding: 8px;
            font-size: 11px;
            text-align: center;
            background: var(--surface-bg);
            color: var(--primary-text);
        }

        .delete-media-checkbox {
            position: absolute;
            top: 5px;
            left: 5px;
            width: 20px;
            height: 20px;
            cursor: pointer;
            z-index: 10;
        }

        .delete-media-label {
            position: absolute;
            top: 30px;
            left: 5px;
            background: var(--danger-color);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }

        .existing-media-item:hover .delete-media-label {
            opacity: 1;
        }

        .file-upload-area {
            border: 2px dashed var(--accent-bg);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: var(--primary-bg);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .file-upload-area:hover {
            border-color: var(--success-color);
            background: var(--surface-bg);
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .image-preview, .video-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .preview-item {
            position: relative;
            background: var(--primary-bg);
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid var(--accent-bg);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .preview-item img, .preview-item video {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }

        .preview-item .file-name {
            padding: 8px;
            font-size: 11px;
            text-align: center;
            background: var(--surface-bg);
            color: var(--primary-text);
            word-break: break-word;
        }

        .remove-file {
            position: absolute;
            top: 5px;
            right: 5px;
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .remove-file:hover {
            background: #b91c1c;
        }

        .file-count {
            margin-top: 10px;
            padding: 8px 12px;
            background: var(--accent-bg);
            border-radius: 6px;
            font-size: 13px;
            color: var(--secondary-text);
            font-weight: 600;
            display: none;
        }

        .file-count.show {
            display: block;
        }

        .file-count.success {
            background: rgba(45, 125, 45, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(45, 125, 45, 0.3);
        }

        .btn-container {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid var(--accent-bg);
        }

        .breadcrumb {
            background: var(--surface-bg);
            padding: 15px 30px;
            border-bottom: 1px solid var(--accent-bg);
        }

        .breadcrumb a {
            color: var(--accent-text);
            text-decoration: none;
            margin-right: 10px;
        }

        .breadcrumb a:hover {
            color: var(--primary-text);
        }

        .breadcrumb span {
            color: var(--primary-text);
            margin: 0 5px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .existing-media-grid,
            .image-preview, 
            .video-preview {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidenav.php'; ?>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="properties.php">Propriétés</a>
            <span>/</span>
            <span>Modifier <?= htmlspecialchars($property['property_code']) ?></span>
        </div>

        <div class="header">
            <div>
                <h1>Modifier la Propriété</h1>
                <p>Code: <?= htmlspecialchars($property['property_code']) ?></p>
            </div>
            <div>
                <a href="properties.php" class="btn btn-primary">Retour aux propriétés</a>
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

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="update_property">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="propertyCode">Code de la Propriété <span class="required">*</span></label>
                            <input type="text" id="propertyCode" name="property_code" required placeholder="Ex: A-1234" value="<?= htmlspecialchars($form_data['property_code'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="bedrooms">Chambres <span class="required">*</span></label>
                            <input type="number" id="bedrooms" name="bedrooms" min="1" max="20" required value="<?= htmlspecialchars($form_data['bedrooms'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="price">Prix (DA)</label>
                            <input type="number" id="price" name="price" min="0" step="0.01" placeholder="0.00" value="<?= htmlspecialchars($form_data['price'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="bathrooms">Salles de bain <span class="required">*</span></label>
                            <input type="number" id="bathrooms" name="bathrooms" min="0.5" max="10" step="0.5" required value="<?= htmlspecialchars($form_data['bathrooms'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="surface">Surface (m²)</label>
                            <input type="number" id="surface" name="surface" min="20" max="1000" value="<?= htmlspecialchars($form_data['surface'] ?? '') ?>">
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Description <span class="required">*</span></label>
                            <textarea id="description" name="description" required placeholder="Décrivez les caractéristiques..."><?= htmlspecialchars($form_data['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="addressLine1">Adresse ligne 1 <span class="required">*</span></label>
                            <input type="text" id="addressLine1" name="address_line1" required value="<?= htmlspecialchars($form_data['address_line1'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="addressLine2">Adresse ligne 2</label>
                            <input type="text" id="addressLine2" name="address_line2" value="<?= htmlspecialchars($form_data['address_line2'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="city">Ville <span class="required">*</span></label>
                            <input type="text" id="city" name="city" required value="<?= htmlspecialchars($form_data['city'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="state">État/Région <span class="required">*</span></label>
                            <input type="text" id="state" name="state" required value="<?= htmlspecialchars($form_data['state'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="postalCode">Code postal <span class="required">*</span></label>
                            <input type="text" id="postalCode" name="postal_code" required value="<?= htmlspecialchars($form_data['postal_code'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="constructionStatus">État de construction <span class="required">*</span></label>
                            <select id="constructionStatus" name="construction_status" required>
                                <option value="">Sélectionner l'état...</option>
                                <option value="foundation" <?= ($form_data['construction_status'] ?? '') === 'foundation' ? 'selected' : '' ?>>Fondation</option>
                                <option value="framing" <?= ($form_data['construction_status'] ?? '') === 'framing' ? 'selected' : '' ?>>Charpente</option>
                                <option value="roofing" <?= ($form_data['construction_status'] ?? '') === 'roofing' ? 'selected' : '' ?>>Toiture</option>
                                <option value="plumbing" <?= ($form_data['construction_status'] ?? '') === 'plumbing' ? 'selected' : '' ?>>Plomberie</option>
                                <option value="electrical" <?= ($form_data['construction_status'] ?? '') === 'electrical' ? 'selected' : '' ?>>Électricité</option>
                                <option value="finishing" <?= ($form_data['construction_status'] ?? '') === 'finishing' ? 'selected' : '' ?>>Finitions</option>
                                <option value="final_inspection" <?= ($form_data['construction_status'] ?? '') === 'final_inspection' ? 'selected' : '' ?>>Inspection finale</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="completionPercentage">Pourcentage d'achèvement</label>
                            <input type="number" id="completionPercentage" name="completion_percentage" min="0" max="100" value="<?= htmlspecialchars($form_data['completion_percentage'] ?? '0') ?>">
                        </div>

                        <div class="form-group">
                            <label for="estimatedCompletion">Achèvement estimé</label>
                            <input type="date" id="estimatedCompletion" name="estimated_completion" value="<?= htmlspecialchars($form_data['estimated_completion'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="saleStatus">Statut de vente</label>
                            <select id="saleStatus" name="sale_status">
                                <option value="available" <?= ($form_data['sale_status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Disponible</option>
                                <option value="under_contract" <?= ($form_data['sale_status'] ?? '') === 'under_contract' ? 'selected' : '' ?>>Sous contrat</option>
                                <option value="sold" <?= ($form_data['sale_status'] ?? '') === 'sold' ? 'selected' : '' ?>>Vendu</option>
                                <option value="on_hold" <?= ($form_data['sale_status'] ?? '') === 'on_hold' ? 'selected' : '' ?>>En attente</option>
                            </select>
                        </div>
                    </div>

                    <!-- Existing Images Section -->
                    <?php if (!empty($existing_images)): ?>
                    <div class="media-section">
                        <h3>Images existantes</h3>
                        <div class="existing-media">
                            <h4>Cochez les images à supprimer:</h4>
                            <div class="existing-media-grid">
                                <?php foreach ($existing_images as $image): ?>
                                <div class="existing-media-item">
                                    <img src="<?= htmlspecialchars($image['file_path']) ?>" alt="<?= htmlspecialchars($image['alt_text']) ?>">
                                    <div class="file-info">
                                        <?= htmlspecialchars($image['alt_text'] ?: $image['file_name']) ?>
                                        <?php if ($image['is_primary']): ?>
                                        <br><strong style="color: var(--success-color);">Image principale</strong>
                                        <?php endif; ?>
                                    </div>
                                    <input type="checkbox" name="delete_images[]" value="<?= $image['image_id'] ?>" class="delete-media-checkbox">
                                    <label class="delete-media-label">Supprimer</label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Existing Videos Section -->
                    <?php if (!empty($existing_videos)): ?>
                    <div class="media-section">
                        <h3>Vidéos existantes</h3>
                        <div class="existing-media">
                            <h4>Cochez les vidéos à supprimer:</h4>
                            <div class="existing-media-grid">
                                <?php foreach ($existing_videos as $video): ?>
                                <div class="existing-media-item">
                                    <video src="<?= htmlspecialchars($video['file_path']) ?>" muted></video>
                                    <div class="file-info">
                                        <?= htmlspecialchars($video['title'] ?: $video['file_name']) ?>
                                    </div>
                                    <input type="checkbox" name="delete_videos[]" value="<?= $video['video_id'] ?>" class="delete-media-checkbox">
                                    <label class="delete-media-label">Supprimer</label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="media-section">
                        <h3>Ajouter de nouvelles images</h3>
                        <div class="file-upload-area" onclick="document.getElementById('imageInput').click()">
                            <div style="font-size: 3rem; color: var(--accent-text); margin-bottom: 15px;">📷</div>
                            <p style="color: var(--secondary-text); font-weight: 600;">Cliquer pour sélectionner de nouvelles images</p>
                            <p style="font-size: 12px; color: var(--accent-text);">JPG, PNG, GIF (Max 5MB chacune) - Sélection multiple possible</p>
                        </div>
                        <input type="file" id="imageInput" name="images[]" multiple accept="image/*" style="display: none;" onchange="previewImages(this)">
                        <div id="imagePreview" class="image-preview"></div>
                        <div id="imageCount" class="file-count"></div>
                    </div>

                    <div class="media-section">
                        <h3>Ajouter de nouvelles vidéos</h3>
                        <div class="file-upload-area" onclick="document.getElementById('videoInput').click()">
                            <div style="font-size: 3rem; color: var(--accent-text); margin-bottom: 15px;">🎥</div>
                            <p style="color: var(--secondary-text); font-weight: 600;">Cliquer pour sélectionner de nouvelles vidéos</p>
                            <p style="font-size: 12px; color: var(--accent-text);">MP4, MOV, AVI (Max 50MB chacune) - Sélection multiple possible</p>
                        </div>
                        <input type="file" id="videoInput" name="videos[]" multiple accept="video/*" style="display: none;" onchange="previewVideos(this)">
                        <div id="videoPreview" class="video-preview"></div>
                        <div id="videoCount" class="file-count"></div>
                    </div>

                    <div class="btn-container">
                        <a href="properties.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-success">Mettre à jour la propriété</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let selectedImages = [];
        let selectedVideos = [];
        
        function previewImages(input) {
            const preview = document.getElementById('imagePreview');
            const counter = document.getElementById('imageCount');
            const newFiles = Array.from(input.files);
            
            selectedImages = selectedImages.concat(newFiles);
            
            preview.innerHTML = '';
            
            if (selectedImages.length === 0) {
                counter.classList.remove('show');
                return;
            }
            
            counter.innerHTML = `✓ ${selectedImages.length} nouvelle(s) image(s) sélectionnée(s)`;
            counter.classList.add('show', 'success');
            
            selectedImages.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" alt="${file.name}">
                            <div class="file-name">${file.name}</div>
                            <button type="button" class="remove-file" onclick="removeImageFile(${index})" title="Supprimer">×</button>
                        `;
                        
                        preview.appendChild(previewItem);
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
            
            updateImageInput();
        }
        
        function previewVideos(input) {
            const preview = document.getElementById('videoPreview');
            const counter = document.getElementById('videoCount');
            const newFiles = Array.from(input.files);
            
            selectedVideos = selectedVideos.concat(newFiles);
            
            preview.innerHTML = '';
            
            if (selectedVideos.length === 0) {
                counter.classList.remove('show');
                return;
            }
            
            counter.innerHTML = `✓ ${selectedVideos.length} nouvelle(s) vidéo(s) sélectionnée(s)`;
            counter.classList.add('show', 'success');
            
            selectedVideos.forEach((file, index) => {
                if (file.type.startsWith('video/')) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    
                    const videoURL = URL.createObjectURL(file);
                    
                    previewItem.innerHTML = `
                        <video src="${videoURL}" style="width: 100%; height: 100px; object-fit: cover;" muted></video>
                        <div class="file-name">${file.name}</div>
                        <button type="button" class="remove-file" onclick="removeVideoFile(${index})" title="Supprimer">×</button>
                    `;
                    
                    preview.appendChild(previewItem);
                }
            });
            
            updateVideoInput();
        }
        
        function removeImageFile(index) {
            selectedImages.splice(index, 1);
            updateImageInput();
            refreshImagePreviews();
        }
        
        function removeVideoFile(index) {
            selectedVideos.splice(index, 1);
            updateVideoInput();
            refreshVideoPreviews();
        }
        
        function updateImageInput() {
            const input = document.getElementById('imageInput');
            const dt = new DataTransfer();
            
            selectedImages.forEach(file => {
                dt.items.add(file);
            });
            
            input.files = dt.files;
        }
        
        function updateVideoInput() {
            const input = document.getElementById('videoInput');
            const dt = new DataTransfer();
            
            selectedVideos.forEach(file => {
                dt.items.add(file);
            });
            
            input.files = dt.files;
        }
        
        function refreshImagePreviews() {
            const preview = document.getElementById('imagePreview');
            const counter = document.getElementById('imageCount');
            
            preview.innerHTML = '';
            
            if (selectedImages.length === 0) {
                counter.classList.remove('show');
                return;
            }
            
            counter.innerHTML = `✓ ${selectedImages.length} nouvelle(s) image(s) sélectionnée(s)`;
            counter.classList.add('show', 'success');
            
            selectedImages.forEach((file, index) => {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="${file.name}">
                        <div class="file-name">${file.name}</div>
                        <button type="button" class="remove-file" onclick="removeImageFile(${index})" title="Supprimer">×</button>
                    `;
                    
                    preview.appendChild(previewItem);
                };
                
                reader.readAsDataURL(file);
            });
        }
        
        function refreshVideoPreviews() {
            const preview = document.getElementById('videoPreview');
            const counter = document.getElementById('videoCount');
            
            preview.innerHTML = '';
            
            if (selectedVideos.length === 0) {
                counter.classList.remove('show');
                return;
            }
            
            counter.innerHTML = `✓ ${selectedVideos.length} nouvelle(s) vidéo(s) sélectionnée(s)`;
            counter.classList.add('show', 'success');
            
            selectedVideos.forEach((file, index) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                
                const videoURL = URL.createObjectURL(file);
                
                previewItem.innerHTML = `
                    <video src="${videoURL}" style="width: 100%; height: 100px; object-fit: cover;" muted></video>
                    <div class="file-name">${file.name}</div>
                    <button type="button" class="remove-file" onclick="removeVideoFile(${index})" title="Supprimer">×</button>
                `;
                
                preview.appendChild(previewItem);
            });
        }
        
        // Form validation and feedback
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const constructionStatusSelect = document.getElementById('constructionStatus');
            
            form.addEventListener('submit', function(e) {
                const statusValue = constructionStatusSelect.value;
                console.log('Construction status value being submitted:', statusValue);
                
                if (!statusValue) {
                    alert('Veuillez sélectionner un état de construction!');
                    e.preventDefault();
                    return false;
                }
                
                // Show confirmation for deletions
                const deleteImages = document.querySelectorAll('input[name="delete_images[]"]:checked');
                const deleteVideos = document.querySelectorAll('input[name="delete_videos[]"]:checked');
                
                if (deleteImages.length > 0 || deleteVideos.length > 0) {
                    const totalDeletions = deleteImages.length + deleteVideos.length;
                    if (!confirm(`Êtes-vous sûr de vouloir supprimer ${totalDeletions} fichier(s)? Cette action est irréversible.`)) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
            
            // Auto-hide messages
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
            
            // Highlight selected files for deletion
            const deleteCheckboxes = document.querySelectorAll('.delete-media-checkbox');
            deleteCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const item = this.closest('.existing-media-item');
                    if (this.checked) {
                        item.style.border = '3px solid var(--danger-color)';
                        item.style.opacity = '0.7';
                    } else {
                        item.style.border = '2px solid var(--accent-bg)';
                        item.style.opacity = '1';
                    }
                });
            });
        });
    </script>
</body>
</html>