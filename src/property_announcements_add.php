<!-- works fine except that it deosnt save the status in the database -->
<!-- not sure about the green color, but i does make the difference between the red for users managemnt pages, maybe a different shade or one of the blues -->

<?php
require_once 'session_check.php';
include 'sidenav.php';
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

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_property') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = 'Token de sécurité invalide. Veuillez actualiser la page.';
    } else {
        try {
            $mysqli->begin_transaction();
            
            // Debug: Log what we're receiving
            error_log("Construction Status received: '" . $_POST['construction_status'] . "'");
            
            $required_fields = ['property_code', 'description', 'address_line1', 'city', 'state', 'postal_code', 'bedrooms', 'bathrooms', 'construction_status'];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Le champ $field est requis");
                }
            }
            
            if (!preg_match('/^[A-Z]-\d{4}$/', $_POST['property_code'])) {
                throw new Exception('Format du code propriété invalide (ex: A-1234)');
            }
            
            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM properties WHERE property_code = ?");
            $stmt->bind_param('s', $_POST['property_code']);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_row()[0];
            $stmt->close();
            
            if ($count > 0) {
                throw new Exception('Ce code de propriété existe déjà');
            }
            
            $address_line2 = !empty($_POST['address_line2']) ? $_POST['address_line2'] : null;
            $surface = !empty($_POST['surface']) ? (int)$_POST['surface'] : null;
            $price = !empty($_POST['price']) ? (float)$_POST['price'] : 0.00;
            $completion_percentage = (int)($_POST['completion_percentage'] ?? 0);
            $estimated_completion = !empty($_POST['estimated_completion']) ? $_POST['estimated_completion'] : null;
            $sale_status = $_POST['sale_status'] ?? 'available';
            $bedrooms = (int)$_POST['bedrooms'];
            $bathrooms = (float)$_POST['bathrooms'];
            $construction_status = $_POST['construction_status']; // Make sure this is captured
            
            // Debug: Log the prepared values
            error_log("About to insert - Construction Status: '$construction_status'");
            
            $stmt = $mysqli->prepare("
                INSERT INTO properties (
                    property_code, description, address_line1, address_line2, city, state, postal_code,
                    price, bedrooms, bathrooms, surface, construction_status, completion_percentage,
                    estimated_completion, sale_status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param('sssssssdidsisssi',
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
                $current_user_id
            );
            
            $stmt->execute();
            $property_id = $mysqli->insert_id;
            $stmt->close();
            
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
                            $stmt = $mysqli->prepare("INSERT INTO property_images (property_id, file_name, file_path, alt_text, is_primary, sort_order, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            
                            $is_primary = ($key === 0);
                            $sort_order = $key + 1;
                            $stmt->bind_param('isssbii', $property_id, $new_filename, $file_path, $filename, $is_primary, $sort_order, $current_user_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
            
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
            $success_message = "Propriété créée avec succès ! Code: " . $_POST['property_code'];
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $error_message = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une Nouvelle Propriété</title>
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

        .file-upload-section {
            background: var(--surface-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .file-upload-section h3 {
            color: var(--secondary-text);
            margin-bottom: 15px;
            font-size: 18px;
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

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .image-preview, .video-preview {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Créer une Nouvelle Propriété</h1>
                <p>Ajouter une nouvelle propriété au système de gestion</p>
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
                    <input type="hidden" name="action" value="create_property">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="propertyCode">Code de la Propriété <span class="required">*</span></label>
                            <input type="text" id="propertyCode" name="property_code" required placeholder="Ex: A-1234" value="<?= htmlspecialchars($_POST['property_code'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="bedrooms">Chambres <span class="required">*</span></label>
                            <input type="number" id="bedrooms" name="bedrooms" min="1" max="20" required value="<?= htmlspecialchars($_POST['bedrooms'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="price">Prix (DA)</label>
                            <input type="number" id="price" name="price" min="0" step="0.01" placeholder="0.00" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="bathrooms">Salles de bain <span class="required">*</span></label>
                            <input type="number" id="bathrooms" name="bathrooms" min="0.5" max="10" step="0.5" required value="<?= htmlspecialchars($_POST['bathrooms'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="surface">Surface (m²)</label>
                            <input type="number" id="surface" name="surface" min="20" max="1000" value="<?= htmlspecialchars($_POST['surface'] ?? '') ?>">
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Description <span class="required">*</span></label>
                            <textarea id="description" name="description" required placeholder="Décrivez les caractéristiques..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="addressLine1">Adresse ligne 1 <span class="required">*</span></label>
                            <input type="text" id="addressLine1" name="address_line1" required value="<?= htmlspecialchars($_POST['address_line1'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="addressLine2">Adresse ligne 2</label>
                            <input type="text" id="addressLine2" name="address_line2" value="<?= htmlspecialchars($_POST['address_line2'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="city">Ville <span class="required">*</span></label>
                            <input type="text" id="city" name="city" required value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="state">État/Région <span class="required">*</span></label>
                            <input type="text" id="state" name="state" required value="<?= htmlspecialchars($_POST['state'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="postalCode">Code postal <span class="required">*</span></label>
                            <input type="text" id="postalCode" name="postal_code" required value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="constructionStatus">État de construction <span class="required">*</span></label>
                            <select id="constructionStatus" name="construction_status" required>
                                <option value="">Sélectionner l'état...</option>
                                <option value="foundation" <?= isset($_POST['construction_status']) && $_POST['construction_status'] === 'foundation' ? 'selected' : '' ?>>Fondation</option>
                                <option value="framing" <?= isset($_POST['construction_status']) && $_POST['construction_status'] === 'framing' ? 'selected' : '' ?>>Charpente</option>
                                <option value="roofing" <?= isset($_POST['construction_status']) && $_POST['construction_status'] === 'roofing' ? 'selected' : '' ?>>Toiture</option>
                                <option value="plumbing" <?= isset($_POST['construction_status']) && $_POST['construction_status'] === 'plumbing' ? 'selected' : '' ?>>Plomberie</option>
                                <option value="electrical" <?= isset($_POST['construction_status']) && $_POST['construction_status'] === 'electrical' ? 'selected' : '' ?>>Électricité</option>
                                <option value="finishing" <?= isset($_POST['construction_status']) && $_POST['construction_status'] === 'finishing' ? 'selected' : '' ?>>Finitions</option>
                                <option value="final_inspection" <?= isset($_POST['construction_status']) && $_POST['construction_status'] === 'final_inspection' ? 'selected' : '' ?>>Inspection finale</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="completionPercentage">Pourcentage d'achèvement</label>
                            <input type="number" id="completionPercentage" name="completion_percentage" min="0" max="100" value="<?= htmlspecialchars($_POST['completion_percentage'] ?? '0') ?>">
                        </div>

                        <div class="form-group">
                            <label for="estimatedCompletion">Achèvement estimé</label>
                            <input type="date" id="estimatedCompletion" name="estimated_completion" value="<?= htmlspecialchars($_POST['estimated_completion'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="saleStatus">Statut de vente</label>
                            <select id="saleStatus" name="sale_status">
                                <option value="available" <?= ($_POST['sale_status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Disponible</option>
                                <option value="under_contract" <?= ($_POST['sale_status'] ?? '') === 'under_contract' ? 'selected' : '' ?>>Sous contrat</option>
                                <option value="sold" <?= ($_POST['sale_status'] ?? '') === 'sold' ? 'selected' : '' ?>>Vendu</option>
                                <option value="on_hold" <?= ($_POST['sale_status'] ?? '') === 'on_hold' ? 'selected' : '' ?>>En attente</option>
                            </select>
                        </div>
                    </div>

                    <div class="file-upload-section">
                        <h3>Images de la propriété</h3>
                        <div class="file-upload-area" onclick="document.getElementById('imageInput').click()">
                            <div style="font-size: 3rem; color: var(--accent-text); margin-bottom: 15px;">📷</div>
                            <p style="color: var(--secondary-text); font-weight: 600;">Cliquer pour sélectionner les images</p>
                            <p style="font-size: 12px; color: var(--accent-text);">JPG, PNG, GIF (Max 5MB chacune) - Sélection multiple possible</p>
                        </div>
                        <input type="file" id="imageInput" name="images[]" multiple accept="image/*" style="display: none;" onchange="previewImages(this)">
                        <div id="imagePreview" class="image-preview"></div>
                        <div id="imageCount" class="file-count"></div>
                    </div>

                    <div class="file-upload-section">
                        <h3>Vidéos de la propriété</h3>
                        <div class="file-upload-area" onclick="document.getElementById('videoInput').click()">
                            <div style="font-size: 3rem; color: var(--accent-text); margin-bottom: 15px;">🎥</div>
                            <p style="color: var(--secondary-text); font-weight: 600;">Cliquer pour sélectionner les vidéos</p>
                            <p style="font-size: 12px; color: var(--accent-text);">MP4, MOV, AVI (Max 50MB chacune) - Sélection multiple possible</p>
                        </div>
                        <input type="file" id="videoInput" name="videos[]" multiple accept="video/*" style="display: none;" onchange="previewVideos(this)">
                        <div id="videoPreview" class="video-preview"></div>
                        <div id="videoCount" class="file-count"></div>
                    </div>

                    <div class="btn-container">
                        <button type="button" class="btn btn-secondary" onclick="window.history.back()">Annuler</button>
                        <button type="submit" class="btn btn-success">Créer la propriété</button>
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
            
            // Add new files to existing selection instead of replacing
            selectedImages = selectedImages.concat(newFiles);
            
            // Clear and rebuild preview
            preview.innerHTML = '';
            
            if (selectedImages.length === 0) {
                counter.classList.remove('show');
                return;
            }
            
            counter.innerHTML = `✓ ${selectedImages.length} image(s) sélectionnée(s)`;
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
            
            // Update the actual input with all selected files
            updateImageInput();
        }
        
        function previewVideos(input) {
            const preview = document.getElementById('videoPreview');
            const counter = document.getElementById('videoCount');
            const newFiles = Array.from(input.files);
            
            // Add new files to existing selection instead of replacing
            selectedVideos = selectedVideos.concat(newFiles);
            
            // Clear and rebuild preview
            preview.innerHTML = '';
            
            if (selectedVideos.length === 0) {
                counter.classList.remove('show');
                return;
            }
            
            counter.innerHTML = `✓ ${selectedVideos.length} vidéo(s) sélectionnée(s)`;
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
            
            // Update the actual input with all selected files
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
            
            counter.innerHTML = `✓ ${selectedImages.length} image(s) sélectionnée(s)`;
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
            
            counter.innerHTML = `✓ ${selectedVideos.length} vidéo(s) sélectionnée(s)`;
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
        
        // Debug form submission to check construction status
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
            });
            
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