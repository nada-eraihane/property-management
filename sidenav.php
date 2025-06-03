<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>PropertyTrack</title>
</head>

<body>
  <header id="main-header">
    <div class="container">
      <!-- Logo - Now the main focal point -->
      <div class="logo-container">
        <img src="/property_management/resources/3.png" alt="PropertyTrack" class="logo">
      </div>

      <!-- Navigation Links -->
      <nav id="navMenu" class="nav-links">
        <a href="home.php">Accueil</a>
        <a href="contact.php">Contact</a>
        <a href="about.php">À propos</a>
        <a href="sales.php">À Vendre</a>
        <a href="whatsnew.php">Nouveautés</a>
        <a href="login.php">Connexion</a>
      </nav>

      <!-- Dark/Light Toggle - Compact with icon only -->
      <button class="theme-toggle" title="Toggle theme" id="themeToggle">
        <span id="themeIcon">🌙</span>
      </button>

      <!-- Hamburger button (for small screens) -->
      <button id="navToggle" class="hamburger-btn">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </button>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="mobile-menu">
      <a href="home.php">Accueil</a>
      <a href="contact.php">Contact</a>
      <a href="about.php">À propos</a>
      <a href="sales.php">À Vendre</a>
      <a href="whatsnew.php">Nouveautés</a>
      <a href="login.php">Connexion</a>
    </div>
  </header>

  <script>
    // Theme Toggle Functionality
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const body = document.body;

    // Check for saved theme preference or default to light mode
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
      const currentTheme = body.getAttribute('data-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      setTheme(newTheme);
      localStorage.setItem('theme', newTheme);
    });

    function setTheme(theme) {
      body.setAttribute('data-theme', theme);
      themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
    }

    // Mobile Menu Functionality
    const navToggle = document.getElementById('navToggle');
    const mobileMenu = document.getElementById('mobileMenu');

    navToggle.addEventListener('click', () => {
      mobileMenu.classList.toggle('show');
      navToggle.classList.toggle('active');
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
      if (!navToggle.contains(e.target) && !mobileMenu.contains(e.target)) {
        mobileMenu.classList.remove('show');
        navToggle.classList.remove('active');
      }
    });
  </script>

  <style>
    /* CSS Custom Properties for Theme Variables */
    :root {
      /* Light theme colors */
      --primary-bg: #ffffff;
      --secondary-bg: #f8f9fa;
      --surface-bg: #ffffff;
      --accent-bg: #e9ecef;
      --primary-text: #212529;
      --accent-text: #495057;
      --highlight-color: #007bff;
    }

    [data-theme="dark"] {
      /* Dark theme colors */
      --primary-bg: #1a1a1a;
      --secondary-bg: #2d2d2d;
      --surface-bg: #333333;
      --accent-bg: #404040;
      --primary-text: #ffffff;
      --accent-text: #e0e0e0;
      --highlight-color: #4dabf7;
    }

    /* Base body styles using theme variables */
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
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Header styles using theme variables */
    #main-header {
      background-color: var(--secondary-bg);
      color: var(--primary-text);
      padding: 15px 0;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
      transition: all 0.3s ease;
    }

    /* Dark mode shadow adjustment */
    [data-theme="dark"] #main-header {
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    /* Container */
    .container {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: relative;
      min-height: 70px;
    }

    /* Logo - Made the focal point */
    .logo-container {
      flex-shrink: 0;
      z-index: 10;
    }

    .logo {
      height: 120px;
      padding: 10px;
      object-fit: contain;
      transition: transform 0.3s ease, filter 0.3s ease;
    }

    .logo:hover {
      transform: scale(1.05);
      filter: brightness(1.1);
    }

    /* Navigation Links - Centered */
    .nav-links {
      display: flex;
      gap: 30px;
      align-items: center;
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
    }

    .nav-links a {
      text-decoration: none;
      color: var(--accent-text);
      font-weight: 500;
      padding: 10px 15px;
      border-radius: 8px;
      transition: all 0.3s ease;
      position: relative;
      font-size: 16px;
    }

    .nav-links a:hover {
      color: var(--highlight-color);
      background-color: var(--accent-bg);
      transform: translateY(-2px);
    }

    .nav-links a:active {
      transform: translateY(0);
    }

    /* Compact Theme Toggle Button */
    .theme-toggle {
      background-color: var(--surface-bg);
      border: 2px solid var(--accent-bg);
      border-radius: 50%;
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 18px;
      flex-shrink: 0;
    }

    .theme-toggle:hover {
      background-color: var(--accent-bg);
      transform: scale(1.1) rotate(15deg);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .theme-toggle:active {
      transform: scale(0.95);
    }

    /* Enhanced Hamburger Button */
    .hamburger-btn {
      background: none;
      border: none;
      cursor: pointer;
      display: none;
      flex-direction: column;
      justify-content: space-around;
      width: 30px;
      height: 30px;
      padding: 0;
      position: relative;
      z-index: 1001;
    }

    .hamburger-line {
      width: 100%;
      height: 3px;
      background-color: var(--primary-text);
      border-radius: 2px;
      transition: all 0.3s ease;
      transform-origin: center;
    }

    .hamburger-btn.active .hamburger-line:nth-child(1) {
      transform: rotate(45deg) translate(6px, 6px);
    }

    .hamburger-btn.active .hamburger-line:nth-child(2) {
      opacity: 0;
    }

    .hamburger-btn.active .hamburger-line:nth-child(3) {
      transform: rotate(-45deg) translate(6px, -6px);
    }

    /* Mobile Menu */
    .mobile-menu {
      display: none;
      position: fixed;
      top: 100%;
      left: 0;
      width: 100%;
      background-color: var(--surface-bg);
      border-top: 1px solid var(--accent-bg);
      padding: 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
      z-index: 999;
      opacity: 0;
      transform: translateY(-20px);
      transition: all 0.3s ease;
    }

    [data-theme="dark"] .mobile-menu {
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
    }

    .mobile-menu.show {
      display: flex;
      flex-direction: column;
      opacity: 1;
      transform: translateY(0);
    }

    .mobile-menu a {
      text-decoration: none;
      color: var(--accent-text);
      padding: 15px 20px;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s ease;
      margin: 3px 0;
      text-align: center;
      font-size: 18px;
    }

    .mobile-menu a:hover {
      color: var(--highlight-color);
      background-color: var(--accent-bg);
      transform: translateX(5px);
    }

    /* Responsive Design */
    @media (max-width: 992px) {
      .nav-links {
        gap: 20px;
      }

      .nav-links a {
        padding: 8px 12px;
        font-size: 15px;
      }
    }

    @media (max-width: 768px) {
      .nav-links {
        display: none;
      }

      .hamburger-btn {
        display: flex;
      }

      .logo {
        height: 80px;
      }

      .container {
        padding: 0 15px;
      }

      #main-header {
        padding: 10px 0;
      }

      .mobile-menu {
        top: calc(100% + 0px);
      }
    }

    @media (max-width: 480px) {
      .logo {
        height: 60px;
      }

      .theme-toggle {
        width: 38px;
        height: 38px;
        font-size: 16px;
      }

      .container {
        width: 95%;
      }
    }

    /* Focus states for accessibility */
    .nav-links a:focus,
    .mobile-menu a:focus,
    .hamburger-btn:focus,
    .theme-toggle:focus {
      outline: 2px solid var(--highlight-color);
      outline-offset: 2px;
    }

    /* Smooth transitions for theme changes */
    * {
      transition: background-color 0.3s ease, 
                  color 0.3s ease, 
                  border-color 0.3s ease,
                  box-shadow 0.3s ease;
    }
  </style>
</body>

</html>