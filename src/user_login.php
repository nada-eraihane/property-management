<!-- to do:
make sure that when the login they are redirected to the customer dashboard
make sure to start a session for customers
make sure if the profile is set to inactif they should be denied access
make sure that it checks if they are customers 
add a stop session page
logout and end session -->

<?php
session_start();
require_once 'db.php'; // DB connection
$mysqli = $conn;       // for use below

// Hardcoded customer logins for testing (optional - you can remove this section)
$hardcoded_customers = [
    'testcustomer' => 'customer123',
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        // Check hardcoded customers (optional - remove if not needed)
        if (isset($hardcoded_customers[$username]) && $hardcoded_customers[$username] === $password) {
            $_SESSION['username'] = $username;
            $_SESSION['source'] = 'hardcoded';
            $_SESSION['role'] = 'Customer';
            header("Location: customer_dashboard.php");
            exit();
        } else {
            // Check database
            if ($mysqli->connect_error) {
                $error = 'Erreur de connexion à la base de données.';
            } else {
                $stmt = $mysqli->prepare("
                    SELECT u.password_hash, r.role_name, u.status, u.user_id
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    WHERE u.username = ?");
                
                if ($stmt) {
                    $stmt->bind_param('s', $username);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows === 1) {
                        $stmt->bind_result($hash, $role, $status, $user_id);
                        $stmt->fetch();
                        
                        // Check if user is inactive
                        if ($status === 'inactive' || $status === 'inactif') {
                            $error = 'Votre compte est inactif. Veuillez contacter le support client.';
                        } else if (password_verify($password, $hash)
                            && ($role === 'Customer' || $role === 'Client')) {
                            $_SESSION['username'] = $username;
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['source'] = 'database';
                            $_SESSION['role'] = $role;
                            header("Location: customer_dashboard.php");
                            exit();
                        } else if ($role !== 'Customer' && $role !== 'Client') {
                            $error = 'Accès non autorisé. Cette page est réservée aux clients.';
                        } else {
                            $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
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
    <title>Connexion Client</title>
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
            background: var(--primary-bg);
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

        .customer-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--highlight-color) 0%, #8b1212 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
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
            background-color: var(--primary-bg);
            color: var(--primary-text);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--highlight-color);
            box-shadow: 0 0 0 3px rgba(162, 20, 20, 0.1);
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

        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--accent-bg);
        }

        .register-link a {
            color: var(--highlight-color);
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="customer-icon">👤</div>
            <h1>Espace Client</h1>
            <p>Connectez-vous à votre compte</p>
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

        <div class="register-link">
            <p>Pas encore de compte ? <a href="register.php">Créer un compte</a></p>
        </div>

        <br>
        <a href="home.php" class="back-button">
            <span class="arrow">←</span>
            <span>Retour à la page d'accueil</span>
        </a>
    </div>
</body>
</html>