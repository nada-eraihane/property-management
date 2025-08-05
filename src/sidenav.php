<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Management - Sidebar</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>

<body>

    <!-- Sidebar toggle button -->
    <div id="sidebar-toggle">☰</div>

    <!-- Main sidebar container -->
    <div class="sidebar" id="sidebar">
        <!-- Logo section -->
        <div class="logo-section">
            <img src="/property_management/resources/rectangle.png" alt="Company Logo" class="logo" id="company-logo">
        </div>

        <!-- Navigation links -->
        <div class="sidebar-links">
            <ul>
                <!-- Dashboard link -->
                <li><a href="dashboard.php"><span class="material-icons">dashboard</span>Tableau de bord</a></li>

                <!-- Requests management -->
                <li><a href="requests.php"><span class="material-icons">mail</span>Demandes</a></li>



                <!-- Announcements management dropdown -->
                <div class="dropdown">
                    <button onclick="toggleSubMenu(this)" class="drop-btn">
                        <span class="nav-item">
                            <span class="material-icons">campaign</span>
                            Annonces générales
                        </span>
                        <span class="material-icons">keyboard_arrow_down</span>
                    </button>
                    <ul class="sub-menu">
                        <li><a href="announcements_add.php">Ajouter annonce</a></li>
                        <li><a href="announcements_edit.php">Modifier annonce</a></li>
                        <li><a href="announcements_delete.php">Supprimer annonce</a></li>
                    </ul>
                </div>

                <!-- Property announcements dropdown -->
                <div class="dropdown">
                    <button onclick="toggleSubMenu(this)" class="drop-btn">
                        <span class="nav-item">
                            <span class="material-icons">home</span>
                            Annonces propriétés
                        </span>
                        <span class="material-icons">keyboard_arrow_down</span>
                    </button>
                    <ul class="sub-menu">
                        <li><a href="property_announcements_add.php">Ajouter annonce</a></li>
                        <li><a href="property_announcements_edit.php">Modifier annonce</a></li>
                        <li><a href="property_announcements_delete.php">Supprimer annonce</a></li>
                    </ul>
                </div>

                <!-- Media gallery -->
                <li><a href="media_gallery.php"><span class="material-icons">photo_library</span>Galerie média</a></li>
                
                <!-- User management dropdown -->
                <div class="dropdown">
                    <button onclick="toggleSubMenu(this)" class="drop-btn">
                        <span class="nav-item">
                            <span class="material-icons">people</span>
                            Gestion utilisateurs
                        </span>
                        <span class="material-icons">keyboard_arrow_down</span>
                    </button>
                    <ul class="sub-menu">
                        <li><a href="users_create.php">Créer utilisateur</a></li>
                        <li><a href="users.php">Liste d'utilisateur</a></li>
                        <li><a href="users_edit.php">Modifier utilisateur</a></li>
                        <li><a href="users_delete.php">Supprimer utilisateur</a></li>
                    </ul>
                </div>
                <!-- Settings -->
                <li><a href="settings.php"><span class="material-icons">settings</span>Paramètres</a></li>
            </ul>
        </div>

        <!-- Bottom section with theme toggle and logout -->
        <div class="sidebar-bottom">
            <!-- Theme toggle button -->
            <!-- <button class="sidebar-btn" style="border:none; cursor:pointer;">
                <span class="material-icons">wb_sunny</span>
                <p id="theme-toggle">Mode clair</p>
            </button> -->

            <!-- Home page link -->
            <a href="home.php" class="sidebar-btn">
                <span class="material-icons">home</span>
                Accueil
            </a>

            <!-- Logout button -->
            <a href="logout.php" class="sidebar-btn">
                <span class="material-icons">logout</span>
                Déconnexion
            </a>
        </div>
    </div>

    <script>
        // Function to toggle dropdown submenus
        function toggleSubMenu(button) {
            let subMenu = button.nextElementSibling;
            subMenu.classList.toggle("show");
            button.classList.toggle("rotate");
        }

        // Main initialization when DOM is loaded
        document.addEventListener("DOMContentLoaded", function () {
            const sidebar = document.getElementById("sidebar");
            const sidebarToggle = document.getElementById("sidebar-toggle");

            // Sidebar toggle functionality
            if (sidebarToggle) {
                sidebarToggle.addEventListener("click", function () {
                    if (sidebar.classList.contains("open")) {
                        // Close sidebar
                        sidebar.classList.remove("open");
                        sidebarToggle.innerHTML = "☰";
                        document.body.classList.remove("shifted");
                        sidebarToggle.style.left = "10px";
                    } else {
                        // Open sidebar
                        sidebar.classList.add("open");
                        sidebarToggle.innerHTML = "✖";
                        document.body.classList.add("shifted");
                        sidebarToggle.style.left = "290px";
                    }
                });
            }

            // // Theme toggle functionality
            // const themeToggle = document.getElementById("theme-toggle");
            // const currentTheme = localStorage.getItem("theme") || "dark";

            // // Set initial theme
            // document.documentElement.setAttribute("data-theme", currentTheme);
            // themeToggle.textContent = currentTheme === "dark" ? "Mode clair" : "Mode sombre";

            // // Theme toggle event listener
            // themeToggle.addEventListener("click", () => {
            //     let theme = document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
            //     document.documentElement.setAttribute("data-theme", theme);
            //     localStorage.setItem("theme", theme);
            //     themeToggle.textContent = theme === "dark" ? "Mode clair" : "Mode sombre";
            // });
        });
    </script>

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
            --footer-bg-color: #101928ff;
            --footer-txt-color: #ffffff;
            --current-theme: 'light';
        }

        /* Global reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            list-style: none;
            text-decoration: none;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 16px;
        }

        /* Body with smooth transition for sidebar */
        body {
            transition: margin-left 0.3s ease;
        }

        /* Sidebar toggle button */
        #sidebar-toggle {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 9999;
            background: var(--secondary-text);
            color: var(--footer-txt-color);
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 18px;
            border-radius: 5px;
            transition: left 0.3s ease;
        }

        /* Main sidebar container */
        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            width: 280px;
            height: 100vh;
            background: var(--secondary-text);
            padding-top: 20px;
            transition: left 0.3s ease;
            display: flex;
            flex-direction: column;
            z-index: 9998;
        }

        /* Sidebar open state */
        .sidebar.open {
            left: 0;
        }

        /* Body shift when sidebar is open */
        body.shifted {
            margin-left: 280px;
        }

        /* Logo section styling */
        .logo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 10px;
            padding: 0 20px;
        }

        /* Logo image styling */
        .logo {
            width: 200px;
            border-radius: 10px;
            margin-bottom: 10px;
            object-fit: contain;
            background: none;
            padding: 0;
        }


        /* Scrollable links container */
        .sidebar-links {
            flex: 1;
            overflow-y: auto;
            padding-bottom: 10px;
        }

        /* Navigation list items */
        .sidebar ul li {
            color: var(--accent-bg);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* Navigation links styling */
        .sidebar ul li a {
            color: var(--accent-bg);
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 15px 20px;
            transition: all 0.3s ease;
        }

        /* Icon spacing in navigation links */
        .sidebar ul li a .material-icons {
            margin-right: 15px;
            font-size: 22px;
        }

        /* Hover effects for navigation items */
        .sidebar ul li:hover,
        .sidebar ul li a:hover {
            background: var(--footer-bg-color);
            color: var(--highlight-color);
            font-weight: bold;
        }

        /* Dropdown button styling */
        .drop-btn {
            display: flex;
            align-items: center;
            background: var(--secondary-text);
            width: 100%;
            color: var(--accent-bg);
            cursor: pointer;
            border: none;
            font: inherit;
            text-align: left;
            justify-content: space-between;
            margin: 0;
            padding: 15px 20px;
            transition: all 0.3s ease;
        }

        /* Navigation item container in dropdown */
        .nav-item {
            display: flex;
            align-items: center;
        }

        /* Icon spacing in dropdown buttons */
        .nav-item .material-icons {
            margin-right: 15px;
            font-size: 20px;
        }

        /* Dropdown button hover effect */
        .drop-btn:hover {
            color:var(--highlight-color);
            font-size: bolder;
        }

        /* Submenu container */
        .sub-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 300ms ease-in-out;
            background: var(--footer-bg-color);
        }

        /* Submenu open state */
        .sub-menu.show {
            max-height: 250px;
        }

        /* Submenu items */
        .sub-menu li {
            display: block;
        }

        /* Submenu links */
        .sub-menu li a {
            padding: 12px 15px 12px 50px;
            font-size: 17px;
        }

        /* Submenu hover effect */
        .sub-menu li:hover {
            background: var(--secondary-text);
            cursor: pointer;
        }

        /* Dropdown arrow rotation */
        .rotate .material-icons {
            transform: rotate(180deg);
            transition: transform 200ms ease-in-out;
        }

        /* Bottom section of sidebar */
        .sidebar-bottom {
            width: 100%;
            padding: 15px;
            background: var(--secondary-text);
            display: flex;
            flex-direction: column;
            border: none;
        }

        /* Bottom section buttons */
        .sidebar-btn {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--surface-bg);
            background: var(--highlight-color);
            border-radius: 5px;
            text-align: left;
            margin-bottom: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        /* Icon spacing in bottom buttons */
        .sidebar-btn .material-icons {
            margin-right: 10px;
            font-size: 18px;
        }

        /* Bottom button hover effect */
        .sidebar-btn:hover {
            background: var(--footer-bg-color);
            transform: translateY(-2px);
        }

        /* Remove margin from last button */
        .sidebar-bottom .sidebar-btn:last-child {
            margin-bottom: 0;
        }

        /* Theme toggle text styling */
        #theme-toggle {
            margin: 0;
            font-size: 14px;
        }

        /* Light theme variables
        [data-theme="light"] .sidebar {
            background: #f8f9fa;
            color: #333;
        }

        [data-theme="light"] .sidebar h5 {
            color: #333;
        }

        [data-theme="light"] .sidebar ul li a {
            color: #333;
        }

        [data-theme="light"] .drop-btn {
            background: #f8f9fa;
            color: #333;
        }

        [data-theme="light"] .sub-menu {
            background: #e9ecef;
        }

        [data-theme="light"] .sidebar ul li:hover,
        [data-theme="light"] .sidebar ul li a:hover {
            background: #e9ecef;
        }

        [data-theme="light"] .drop-btn:hover {
            background: #e9ecef;
        }

        [data-theme="light"] .sub-menu li:hover {
            background: #dee2e6;
        }

        [data-theme="light"] .sidebar-bottom {
            background: #f8f9fa;
        }

        [data-theme="light"] .sidebar-btn {
            background: #6c757d;
            color: white;
        }

        [data-theme="light"] .sidebar-btn:hover {
            background: #5a6268;
        }

        [data-theme="light"] #sidebar-toggle {
            background: #6c757d;
        } */
    </style>

</body>

</html>