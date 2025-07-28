<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>navbar</title>

  <link rel="stylesheet" href="theme-toggle.css" />
</head>

<body>
  <header id="main-header">
    <div class="container">
      <!-- Logo -->
      <div class="logo-container">
        <img src="/property_management/resources/rectangle.png" alt="PropertyTrack" class="logo" />
      </div>

      <!-- Navigation Links -->
      <nav id="navMenu" class="nav-links">
        <a href="home.php">Accueil</a>
        <a href="contact.php">Contact</a>
        <a href="about.php">À propos</a>
        <a href="sales.php">À Vendre</a>
        <a href="whatsnew.php">Nouveautés</a>
        <a href="login_select.php">Connexion</a>
      </nav>

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
  <!-- Theme toggle button -->
  <!-- Theme toggle button -->
  <!-- Theme toggle button -->



  <script>

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
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: var(--primary-bg);
      color: var(--primary-text);
      min-height: 100px;
    }

    #main-header {
      background-color: var(--secondary-bg);
      font-size: 18px;
      color: var(--primary-text);
      padding: 0;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      position: relative;
      top: 0;
      z-index: 1000;
    }

    .container {
      width: 90%;
      /* max-width: 1200px; */
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
    }

    .logo-container {
      flex-shrink: 0;
    }

    .logo {
      height: 140px;
      padding: 10px 0 10px 0;
      object-fit: contain;
    }

    .nav-links {
      display: flex;
      gap: 30px;
      align-items: center;
    }

    .nav-links a {
      text-decoration: none;
      color: var(--accent-text);
      font-weight: 500;
      padding: 10px 15px;
      border-radius: 8px;
      transition: all 0.3s ease;
      position: relative;
    }

    .nav-links a:hover {
      color: var(--highlight-color);
      transform: translateY(-1px);
      font-weight: 510;
    }

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
      position: absolute;
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
        min-height: 70px;
      }

      #main-header {
        position: relative;
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
    }
  </style>
</body>

</html>