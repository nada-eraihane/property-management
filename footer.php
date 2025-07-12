<!DOCTYPE html>
<html lang=" fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Plateforme de Vente de Propriétés</title>
  
</head>
<style>
  * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      height: 100%;
    }

    body {
      background-color: var(--primary-bg);
      color: var(--primary-text);
      font-family: system-ui, -apple-system, sans-serif;
      line-height: 1.6;
      transition: background-color 0.3s ease, color 0.3s ease;
      display: flex;
      flex-direction: column;
      margin: 0 0 10px 0;
    }

    footer {
      margin-top: auto;
      padding: 2.5rem 0 0;
      background-color: var(--surface-bg);
      
    }

    .footer-container {
      max-width: 80rem;
      margin: 0 auto;
      padding: 0 1rem;
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
    }

    @media (min-width: 768px) {
      .footer-container {
        grid-template-columns: repeat(4, 1fr);
      }
    }

    .footer-section h2 {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--secondary-text);
    }

    .footer-section h3 {
      font-size: 1.125rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--secondary-text);
    }

    .footer-section p {
      font-size: 0.875rem;
      color: var(--accent-text);
      margin-bottom: 0.5rem;
    }

    .footer-links {
      list-style: none;
    }

    .footer-links li {
      margin-bottom: 0.25rem;
    }

    .footer-links a {
      color: var(--accent-text);
      text-decoration: none;
      font-size: 0.875rem;
      transition: color 0.3s ease;
    }

    .footer-links a:hover {
      color: var(--secondary-text);
      text-decoration: underline;
    }

    .social-links {
      list-style: none;
    }

    .social-links li {
      margin-bottom: 0.5rem;
    }

    .social-links a {
      color: var(--accent-text);
      text-decoration: none;
      transition: color 0.3s ease;
    }

    .social-links a:hover {
      color: var(--secondary-text);
    }

    .footer-bottom {
      border-top: 1px solid var(--accent-bg);
      margin-top: 2rem;
      padding: 1rem 0 0;
      text-align: center;
      font-size: 0.875rem;
      color: var(--accent-text);
    }

    .footer-bottom a {
      color: var(--accent-text);
      text-decoration: none;
      transition: color 0.3s ease;
    }

    .footer-bottom a:hover {
      color: var(--secondary-text);
      text-decoration: underline;
    }
</style>
<footer>
    <div class="footer-container">

      <!-- À propos -->
      <div class="footer-section">
        <h2>Promotion Immobiliere MERZOUG ABDESLEM</h2>
        <p>
          Votre plateforme de confiance pour la vente de maisons semi-construites avec suivi et mises à jour en temps réel.
        </p>
      </div>

      <!-- Liens rapides -->
      <div class="footer-section">
        <h3>Liens rapides</h3>
        <ul class="footer-links">
          <li><a href="/">Accueil</a></li>
          <li><a href="/properties">Propriétés</a></li>
          <li><a href="/about">À propos</a></li>
          <li><a href="/contact">Contact</a></li>
          <li><a href="/faq">FAQs</a></li>
        </ul>
      </div>

      <!-- Infos de contact -->
      <div class="footer-section">
        <h3>Contact</h3>
        <p>Residence Jolie Vue, Cherchall, Tipaza, Algerie</p>
        <p>Téléphone : +213 540713526 / 020913072</p>
        <p>Email : info@propertytrack.com</p>
        <p>Samedi - Jeudi : 9h00 - 16h30</p>
      </div>

      <!-- Réseaux sociaux -->
      <div class="footer-section">
        <h3>Restez informé</h3>
        <ul class="social-links">
          <li><a href="#" aria-label="Facebook">Facebook</a></li>
          <li><a href="#" aria-label="LinkedIn">LinkedIn</a></li>
        </ul>
      </div>

    </div>

    <!-- Ligne inférieure -->
    <div class="footer-bottom">
      © <span id="year"></span> Promotion Immobiliere MERZOUG ABDESLEM. Tous droits réservés. |
      <a href="/privacy">Politique de confidentialité</a> |
      <a href="/terms">Conditions d'utilisation</a>
    </div>
  </footer>
  <!-- Script pour l'année dynamique-->
  <script>
    document.getElementById('year').textContent = new Date().getFullYear();

    function toggleDarkMode() {
      document.documentElement.classList.toggle('dark');
    }
  </script>

</body>
</html>