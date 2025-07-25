<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Sélection du Type d'Utilisateur</title>
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
            background-color: var(--primary-bg);
            color: var(--primary-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header with back button */
        .header {
            padding: 20px;
            background-color: var(--secondary-bg);
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

        /* Main content container */
        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }

        /* Title section */
        .title-section {
            text-align: center;
            margin-bottom: 50px;
        }

        .main-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary-text);
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            font-size: 1.1rem;
            color: var(--accent-text);
            font-weight: 400;
        }

        /* Button container */
        .button-container {
            display: flex;
            flex-direction: column;
            gap: 25px;
            width: 100%;
            max-width: 400px;
        }

        /* Main login buttons */
        .login-button {
            padding: 20px 30px;
            font-size: 1.2rem;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .login-button:hover::before {
            left: 100%;
        }

        /* Admin button styling */
        .admin-button {
            background-color: var(--highlight-color);
            color: white;
        }

        .admin-button:hover {
            background-color: #8b1212;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(162, 20, 20, 0.3);
        }

        /* User button styling */
        .user-button {
            background-color: var(--accent-bg);
            color: var(--secondary-text);
        }

        .user-button:hover {
            background-color: var(--accent-text);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(86, 124, 141, 0.3);
        }

        /* Button icons */
        .button-icon {
            font-size: 1.4rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-title {
                font-size: 2rem;
            }

            .subtitle {
                font-size: 1rem;
            }

            .login-button {
                padding: 18px 25px;
                font-size: 1.1rem;
            }

            .button-container {
                max-width: 350px;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 30px 15px;
            }

            .main-title {
                font-size: 1.8rem;
            }

            .login-button {
                padding: 16px 20px;
                font-size: 1rem;
            }

            .button-container {
                max-width: 300px;
                gap: 20px;
            }
        }

        /* Subtle animation for page load */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .main-container {
            animation: fadeInUp 0.6s ease-out;
        }

        .header {
            animation: fadeInUp 0.4s ease-out;
        }
    </style>
</head>
<body>
    <!-- Header with back button -->
    <header class="header">
        <a href="home.php" class="back-button" onclick="goBackHome()">
            <span class="arrow">←</span>
            <span>Retour à la page d'accueil</span>
        </a>
    </header>

    <!-- Main content -->
    <main class="main-container">
        <div class="title-section">
            <h1 class="main-title">Connexion</h1>
            <p class="subtitle">Choisissez votre type de compte</p>
        </div>

        <div class="button-container">
            <!-- Admin login button -->
            <a href="admin_login.php" class="login-button admin-button" onclick="goToAdminLogin()">
                <span class="button-icon">🔐</span>
                <span>Connexion Administrateur</span>
            </a>

            <!-- User login button -->
            <a href="user_login.php" class="login-button user-button" onclick="goToUserLogin()">
                <span class="button-icon">👤</span>
                <span>Connexion Utilisateur</span>
            </a>
        </div>
    </main>

    <script>
        // Function to handle admin login navigation
        function goToAdminLogin() {
            // Replace with your actual admin login page URL
            console.log('Navigating to admin login page');
            // window.location.href = '/admin-login';
            alert('Redirection vers la page de connexion administrateur');
        }

        // Function to handle user login navigation
        function goToUserLogin() {
            // Replace with your actual user login page URL
            console.log('Navigating to user login page');
            // window.location.href = '/user-login';
            alert('Redirection vers la page de connexion utilisateur');
        }

        // Function to handle back to home navigation
        function goBackHome() {
            // Replace with your actual home page URL
            console.log('Navigating back to home page');
            // window.location.href = '/';
            alert('Retour à la page d\'accueil');
        }

        // Add smooth hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.login-button');
            
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>