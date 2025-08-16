<?php
session_start();
require_once 'db.php'; // DB connection
$mysqli = $conn;       // for use below

// Hardcoded logins for now
$hardcoded = [
    'admin' => 'password',
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        // Check hardcoded
        if (isset($hardcoded[$username]) && $hardcoded[$username] === $password) {
            $_SESSION['username'] = $username;
            $_SESSION['source'] = 'hardcoded';
            header("Location: dashboard.php");
            exit();
        } else {
            // Check database
            if ($mysqli->connect_error) {
                $error = 'Erreur de connexion à la base de données.';
            } else {
                $stmt = $mysqli->prepare("
                    SELECT u.password_hash, r.role_name, u.status
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    WHERE u.username = ?");
                
                if ($stmt) {
                    $stmt->bind_param('s', $username);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows === 1) {
                        $stmt->bind_result($hash, $role, $status);
                        $stmt->fetch();
                        
                        // Check if user is inactive
                        if ($status === 'inactive' || $status === 'inactif') {
                            $error = 'Votre compte est inactif. Veuillez contacter l\'administrateur.';
                        } else if (password_verify($password, $hash)
                            && ($role === 'Admin' || $role === 'Super Admin')) {
                            $_SESSION['username'] = $username;
                            $_SESSION['source'] = 'database';
                            $_SESSION['role'] = $role;
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $error = 'Nom d\'utilisateur ou mot de passe incorrect, ou rôle non autorisé.';
                        }
                    } else {
                        $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
                    }
                    $stmt->close();
                } else {
                    $error = 'Erreur lors de la préparation de la requête.';
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
    <title>Connexion Administrateur</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--secondary-bg);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(46, 65, 86, 0.15);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
            border: 1px solid var(--surface-bg);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: var(--secondary-text);
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            color: var(--accent-text);
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-text);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--accent-bg);
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            background-color: var( --primary-bg);
            color: var(--primary-text);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-bg);
            box-shadow: 0 0 0 3px rgba(86, 124, 141, 0.1);
            background-color: var(--primary-bg);
        }

        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--highlight-color) 0%, #8b1212 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(162, 20, 20, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: none;
            border: none;
            color: var(--accent-text);
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 6px;
        }

        .back-button:hover {
            background-color: var(--accent-bg);
            color: var(--primary-text);
            transform: translateX(-3px);
        }

        .arrow {
            font-size: 16px;
            transition: transform 0.3s ease;
        }

        .back-button:hover .arrow {
            transform: translateX(-2px);
        }

        .error-message {
            background: #fee;
            color: var(--highlight-color);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fed7d7;
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Connexion Administrateur</h1>
            <p>Veuillez saisir vos identifiants</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-button">Se connecter</button>
        </form>

        <br><br>
        <a href="home.php" class="back-button">
            <span class="arrow">←</span>
            <span>Retour à la page d'accueil</span>
        </a>
    </div>
</body>
</html>