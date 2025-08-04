<?php
session_start();

// Get logout message and clear it
$logout_message = $_SESSION['logout_message'] ?? 'Déconnexion effectuée.';
$logged_out_user = $_SESSION['logged_out_user'] ?? '';
unset($_SESSION['logout_message']);
unset($_SESSION['logged_out_user']);

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Administration</title>
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
            --success-color: #27a844;
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

        .logout-container {
            background: var(--secondary-bg);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(46, 65, 86, 0.15);
            width: 100%;
            max-width: 500px;
            backdrop-filter: blur(10px);
            border: 1px solid var(--surface-bg);
            text-align: center;
        }

        .logout-header {
            margin-bottom: 30px;
        }

        .logout-header h1 {
            color: var(--secondary-text);
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .logout-icon {
            font-size: 64px;
            color: var(--success-color);
            margin-bottom: 20px;
        }

        .success-message {
            background: var(--surface-bg);
            color: var(--secondary-text);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid var(--accent-bg);
            font-size: 16px;
        }

        .user-info {
            color: var(--accent-text);
            font-size: 14px;
            margin-bottom: 30px;
            font-style: italic;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn {
            padding: 14px 24px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
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
            border: 2px solid var(--accent-bg);
        }

        .btn-secondary:hover {
            background: var(--primary-bg);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(200, 217, 230, 0.4);
        }

        .security-note {
            background: #f8f9fa;
            border: 1px solid var(--accent-bg);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            color: var(--accent-text);
        }

        .security-note strong {
            color: var(--primary-text);
        }

        @media (min-width: 480px) {
            .action-buttons {
                flex-direction: row;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-header">
            <div class="logout-icon">✓</div>
            <h1>Déconnexion Réussie</h1>
        </div>

        <div class="success-message">
            <?= htmlspecialchars($logout_message) ?>
        </div>

        <?php if ($logged_out_user): ?>
        <div class="user-info">
            Session fermée pour: <?= htmlspecialchars($logged_out_user) ?>
        </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="admin_login.php" class="btn btn-primary">
                Se reconnecter
            </a>
            <a href="home.php" class="btn btn-secondary">
                Page d'accueil
            </a>
        </div>

        <div class="security-note">
            <strong>Note de sécurité:</strong> Votre session a été complètement fermée. 
            Pour des raisons de sécurité, fermez complètement votre navigateur si vous 
            utilisez un ordinateur partagé.
        </div>
    </div>

    <script>
        // Clear browser history to prevent back button issues
        if (window.history && window.history.pushState) {
            window.history.replaceState(null, null, window.location.href);
            window.history.pushState(null, null, window.location.href);
            
            window.onpopstate = function () {
                window.history.go(1);
            };
        }

        // Clear any cached data
        if ('caches' in window) {
            caches.keys().then(function(names) {
                names.forEach(function(name) {
                    caches.delete(name);
                });
            });
        }

        // Auto-redirect after 30 seconds of inactivity
        let redirectTimer = setTimeout(function() {
            if (confirm('Redirection automatique vers la page d\'accueil dans 5 secondes...')) {
                window.location.href = 'home.php';
            }
        }, 30000);

        // Reset timer on any user activity
        document.addEventListener('click', function() {
            clearTimeout(redirectTimer);
        });
    </script>
</body>
</html>