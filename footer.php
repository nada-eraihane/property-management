<html lang=" fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Plateforme de Vente de Propriétés</title>
  <link href="./src/output.css" rel="stylesheet" />
</head>

<body class="bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-300">

  <footer class="py-10">
    <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-8">

      <!-- À propos -->
      <div>
        <h2 class="text-xl font-semibold mb-2">Promotion Immobiliere MERZOUG ABDESLEM</h2>
        <p class="text-sm">
          Votre plateforme de confiance pour la vente de maisons semi-construites avec suivi et mises à jour en temps réel.
        </p>
      </div>

      <!-- Liens rapides -->
      <div>
        <h3 class="text-lg font-semibold mb-2">Liens rapides</h3>
        <ul class="space-y-1">
          <li><a href="/" class="hover:underline">Accueil</a></li>
          <li><a href="/properties" class="hover:underline">Propriétés</a></li>
          <li><a href="/about" class="hover:underline">À propos</a></li>
          <li><a href="/contact" class="hover:underline">Contact</a></li>
          <li><a href="/faq" class="hover:underline">FAQs</a></li>
        </ul>
      </div>

      <!-- Infos de contact -->
      <div>
        <h3 class="text-lg font-semibold mb-2">Contact</h3>
        <p class="text-sm">Residence Jolie Vue, Cherchall, Tipaza, Algerie</p>
        <p class="text-sm">Téléphone : +213 540713526 / 020913072</p>
        <p class="text-sm">Email : info@propertytrack.com</p>
        <p class="text-sm">Samedi - Jeudi : 9h00 - 16h30</p>
      </div>

      <!--  Réseaux sociaux -->
      <div>
        <h3 class="text-lg font-semibold mb-2">Restez informé</h3>
        <ul>
          <li><a href="#" aria-label="Facebook" class="hover:text-black dark:hover:text-white">
            Facebook
          </a></li>
          <li><a href="#" aria-label="LinkedIn" class="hover:text-black dark:hover:text-white" >
            LinkedIn
          </a></li>
        </ul>
      </div>

    </div>

    <!-- Ligne inférieure -->
    <div class="border-t border-gray-300 dark:border-gray-700 mt-8 pt-4 text-center text-sm">
      © <span id="year"></span> Promotion Immobiliere MERZOUG ABDESLEM. Tous droits réservés. |
      <a href="/privacy" class="hover:underline">Politique de confidentialité</a> |
      <a href="/terms" class="hover:underline">Conditions d'utilisation</a>
    </div>
  </footer>

  

  
  <!-- Script pour l'année dynamique et le mode sombre -->
  <script>
    document.getElementById('year').textContent = new Date().getFullYear();

    function toggleDarkMode() {
      document.documentElement.classList.toggle('dark');
    }
  </script>

</body>
</html>