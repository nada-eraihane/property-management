<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee;
            color: #c53030;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fed7d7;
            display: none;
        }

        .success-message {
            background: #f0fff4;
            color: #2d5a2d;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #9ae6b4;
            display: none;
        }

        .admin-dashboard {
            display: none;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e1e5e9;
        }

        .dashboard-header h2 {
            color: #333;
            font-size: 24px;
        }

        .logout-button {
            padding: 8px 16px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .logout-button:hover {
            background: #c82333;
        }

        .dashboard-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .dashboard-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }

        .dashboard-card h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .dashboard-card p {
            color: #666;
            margin-bottom: 15px;
        }

        .card-button {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .card-button:hover {
            background: #5a67d8;
        }

        .loading {
            display: none;
            text-align: center;
            color: #666;
        }

        @media (max-width: 480px) {
            .login-container, .admin-dashboard {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container" id="loginContainer">
        <div class="login-header">
            <h1>Connexion Administrateur</h1>
            <p>Veuillez saisir vos identifiants</p>
        </div>
        
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-button">Se connecter</button>
        </form>
        
        <div class="loading" id="loading">Vérification en cours...</div>
    </div>

    <div class="admin-dashboard" id="adminDashboard">
        <div class="dashboard-header">
            <h2>Tableau de Bord Administrateur</h2>
            <button class="logout-button" onclick="logout()">Se déconnecter</button>
        </div>
        
        <div class="dashboard-content">
            <div class="dashboard-card">
                <h3>Gestion des Utilisateurs</h3>
                <p>Gérer les comptes utilisateurs et les permissions</p>
                <button class="card-button" onclick="manageUsers()">Gérer</button>
            </div>
            
            <div class="dashboard-card">
                <h3>Gestion des Propriétés</h3>
                <p>Ajouter, modifier et supprimer des propriétés</p>
                <button class="card-button" onclick="manageProperties()">Gérer</button>
            </div>
            
            <div class="dashboard-card">
                <h3>Rapports</h3>
                <p>Consulter les rapports et statistiques</p>
                <button class="card-button" onclick="viewReports()">Voir</button>
            </div>
            
            <div class="dashboard-card">
                <h3>Paramètres Système</h3>
                <p>Configuration et paramètres généraux</p>
                <button class="card-button" onclick="systemSettings()">Configurer</button>
            </div>
        </div>
    </div>

    <script>
        // Hardcoded credentials for testing
        const hardcodedCredentials = {
            'admin': 'admin123',
            'superadmin': 'super2024!',
            'gestionnaire': 'gestion456'
        };

        // Session management
        let currentSession = null;
        let sessionTimeout = null;

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            checkExistingSession();
            setupSessionTimeout();
        });

        // Check if user is already logged in
        function checkExistingSession() {
            const sessionData = getSessionData();
            if (sessionData && sessionData.username && sessionData.loginTime) {
                const now = new Date().getTime();
                const sessionAge = now - sessionData.loginTime;
                const maxSessionAge = 30 * 60 * 1000; // 30 minutes
                
                if (sessionAge < maxSessionAge) {
                    showDashboard(sessionData.username);
                } else {
                    clearSession();
                }
            }
        }

        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                showError('Veuillez remplir tous les champs');
                return;
            }
            
            showLoading(true);
            
            try {
                // Simulate API delay
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                // Check hardcoded credentials first
                const isHardcodedValid = hardcodedCredentials[username] === password;
                
                if (isHardcodedValid) {
                    handleSuccessfulLogin(username, 'hardcoded');
                } else {
                    // Try database authentication
                    const dbResult = await authenticateWithDatabase(username, password);
                    if (dbResult.success) {
                        handleSuccessfulLogin(username, 'database', dbResult.userData);
                    } else {
                        showError('Nom d\'utilisateur ou mot de passe incorrect');
                    }
                }
            } catch (error) {
                showError('Erreur de connexion. Veuillez réessayer.');
                console.error('Login error:', error);
            } finally {
                showLoading(false);
            }
        });

        // Simulate database authentication
        async function authenticateWithDatabase(username, password) {
            // In a real application, this would make an API call to your backend
            // For demonstration, we'll simulate the process
            
            try {
                // Simulate API call
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username, password })
                });
                
                if (response.ok) {
                    const userData = await response.json();
                    return { success: true, userData };
                } else {
                    return { success: false };
                }
            } catch (error) {
                // If database is not available, return false
                console.log('Database authentication not available, using hardcoded only');
                return { success: false };
            }
        }

        // Handle successful login
        function handleSuccessfulLogin(username, source, userData = null) {
            const sessionData = {
                username: username,
                loginTime: new Date().getTime(),
                source: source,
                userData: userData
            };
            
            setSessionData(sessionData);
            showSuccess('Connexion réussie! Redirection...');
            
            setTimeout(() => {
                showDashboard(username);
            }, 1500);
        }

        // Show dashboard
        function showDashboard(username) {
            document.getElementById('loginContainer').style.display = 'none';
            document.getElementById('adminDashboard').style.display = 'block';
            
            // Update dashboard with user info
            const header = document.querySelector('.dashboard-header h2');
            header.textContent = `Tableau de Bord - Bienvenue ${username}`;
            
            currentSession = username;
            resetSessionTimeout();
        }

        // Session management functions
        function setSessionData(data) {
            // In a real application, you would use secure session storage
            // For demo purposes, we'll use sessionStorage (not localStorage as per instructions)
            const sessionKey = 'adminSession_' + Math.random().toString(36).substr(2, 9);
            
            // Store session data in memory for this demo
            currentSession = data;
        }

        function getSessionData() {
            return currentSession;
        }

        function clearSession() {
            currentSession = null;
            if (sessionTimeout) {
                clearTimeout(sessionTimeout);
            }
        }

        // Setup session timeout
        function setupSessionTimeout() {
            resetSessionTimeout();
        }

        function resetSessionTimeout() {
            if (sessionTimeout) {
                clearTimeout(sessionTimeout);
            }
            
            // Auto-logout after 30 minutes of inactivity
            sessionTimeout = setTimeout(() => {
                logout();
                showError('Session expirée. Veuillez vous reconnecter.');
            }, 30 * 60 * 1000);
        }

        // Logout function
        function logout() {
            clearSession();
            document.getElementById('adminDashboard').style.display = 'none';
            document.getElementById('loginContainer').style.display = 'block';
            document.getElementById('loginForm').reset();
            hideMessages();
        }

        // Dashboard functionality
        function manageUsers() {
            if (!isAuthenticated()) return;
            alert('Fonctionnalité de gestion des utilisateurs - À implémenter');
        }

        function manageProperties() {
            if (!isAuthenticated()) return;
            alert('Fonctionnalité de gestion des propriétés - À implémenter');
        }

        function viewReports() {
            if (!isAuthenticated()) return;
            alert('Fonctionnalité de rapports - À implémenter');
        }

        function systemSettings() {
            if (!isAuthenticated()) return;
            alert('Paramètres système - À implémenter');
        }

        // Authentication check
        function isAuthenticated() {
            const sessionData = getSessionData();
            if (!sessionData) {
                logout();
                showError('Session expirée. Veuillez vous reconnecter.');
                return false;
            }
            
            resetSessionTimeout();
            return true;
        }

        // UI Helper functions
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            hideSuccess();
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            hideError();
        }

        function hideError() {
            document.getElementById('errorMessage').style.display = 'none';
        }

        function hideSuccess() {
            document.getElementById('successMessage').style.display = 'none';
        }

        function hideMessages() {
            hideError();
            hideSuccess();
        }

        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
            document.getElementById('loginForm').style.display = show ? 'none' : 'block';
        }

        // Security: Prevent access to protected functions without authentication
        window.addEventListener('beforeunload', function() {
            // Clear sensitive data on page unload
            clearSession();
        });

        // Activity tracking to reset timeout
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
            document.addEventListener(event, function() {
                if (currentSession) {
                    resetSessionTimeout();
                }
            });
        });
    </script>
</body>
</html>