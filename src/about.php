<?php
// Include your navbar here
include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À Propos de Nous - Gestion Immobilière Premium</title>
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
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: var(--primary-text);
            background-color: var(--primary-bg);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--secondary-bg) 0%, var(--accent-bg) 100%);
            padding: 80px 0;
            text-align: center;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: bold;
            color: var(--secondary-text);
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: var(--accent-text);
            max-width: 600px;
            margin: 0 auto;
            font-weight: 300;
        }

        .content-section {
            padding: 80px 0;
        }

        .section-title {
            font-size: 2.5rem;
            color: var(--secondary-text);
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background-color: var(--highlight-color);
            margin: 20px auto;
            border-radius: 2px;
        }

        .story-content {
            background-color: var(--surface-bg);
            padding: 60px;
            border-radius: 15px;
            margin-bottom: 60px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .story-text {
            font-size: 1.1rem;
            color: var(--primary-text);
            margin-bottom: 30px;
            text-align: justify;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-bottom: 80px;
        }

        .value-card {
            background-color: var(--primary-bg);
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid var(--highlight-color);
        }

        .value-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .value-icon {
            width: 80px;
            height: 80px;
            background-color: var(--accent-bg);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--secondary-text);
        }

        .value-title {
            font-size: 1.5rem;
            color: var(--secondary-text);
            margin-bottom: 15px;
            font-weight: bold;
        }

        .value-description {
            color: var(--accent-text);
            font-size: 1rem;
            line-height: 1.6;
        }

        .team-section {
            background-color: var(--secondary-bg);
            padding: 80px 0;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }

        .team-member {
            background-color: var(--primary-bg);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-5px);
        }

        .member-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--accent-bg);
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--secondary-text);
        }

        .member-name {
            font-size: 1.3rem;
            color: var(--secondary-text);
            margin-bottom: 8px;
            font-weight: bold;
        }

        .member-role {
            color: var(--highlight-color);
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .member-bio {
            color: var(--accent-text);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .stats-section {
            padding: 80px 0;
            background-color: var(--primary-bg);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-item {
            padding: 30px;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: bold;
            color: var(--highlight-color);
            display: block;
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--accent-text);
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--secondary-text) 0%, var(--accent-text) 100%);
            color: var(--footer-txt-color);
            padding: 80px 0;
            text-align: center;
        }

        .cta-title {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .cta-description {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }

        .cta-button {
            display: inline-block;
            background-color: var(--highlight-color);
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cta-button:hover {
            background-color: #8b1212;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(162, 20, 20, 0.3);
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .story-content {
                padding: 40px 30px;
            }
            
            .value-card, .team-member {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Section Hero -->
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">Votre Partenaire en Gestion Immobilière</h1>
            <p class="hero-subtitle">Experts en gestion locative, administration de copropriété et services immobiliers depuis plus de 15 ans. Votre tranquillité d'esprit est notre priorité.</p>
        </div>
    </section>

    <!-- Section Notre Histoire -->
    <section class="content-section">
        <div class="container">
            <h2 class="section-title">Notre Histoire</h2>
            <div class="story-content">
                <p class="story-text">
                    Fondée en 2008 par des professionnels passionnés de l'immobilier, notre société de gestion immobilière a débuté avec une vision claire : simplifier la gestion locative pour les propriétaires tout en offrant un service de qualité aux locataires. De nos premiers appartements gérés à notre portefeuille actuel de plus de 2000 biens, nous avons su grandir en gardant nos valeurs humaines.
                </p>
                <p class="story-text">
                    Notre expertise s'est développée au fil des années pour couvrir tous les aspects de la gestion immobilière : gestion locative traditionnelle, administration de copropriétés, gestion de patrimoine immobilier, et conseil en investissement. Nous accompagnons aujourd'hui des propriétaires particuliers, des investisseurs institutionnels et des syndics dans la valorisation et l'optimisation de leurs biens immobiliers.
                </p>
                <p class="story-text">
                    Aujourd'hui, notre équipe de spécialistes certifiés gère un patrimoine immobilier diversifié : appartements, maisons individuelles, bureaux, locaux commerciaux et résidences étudiantes. Notre approche personnalisée et notre connaissance approfondie du marché local nous permettent d'offrir des solutions sur mesure adaptées à chaque situation immobilière.
                </p>
            </div>
        </div>
    </section>

    <!-- Section Nos Services -->
    <section class="content-section" style="background-color: var(--surface-bg);">
        <div class="container">
            <h2 class="section-title">Nos Services Immobiliers</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">🏠</div>
                    <h3 class="value-title">Gestion Locative</h3>
                    <p class="value-description">Gestion complète de vos biens locatifs : recherche de locataires, état des lieux, encaissement des loyers, et suivi administratif.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">🏢</div>
                    <h3 class="value-title">Syndic de Copropriété</h3>
                    <p class="value-description">Administration professionnelle de votre copropriété : assemblées générales, gestion financière, travaux et entretien.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">📊</div>
                    <h3 class="value-title">Conseil en Investissement</h3>
                    <p class="value-description">Expertise et accompagnement dans vos projets d'investissement immobilier pour optimiser votre rentabilité.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">🔧</div>
                    <h3 class="value-title">Maintenance & Travaux</h3>
                    <p class="value-description">Coordination des interventions, suivi des travaux et maintenance préventive pour préserver la valeur de vos biens.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Équipe -->
    <section class="team-section">
        <div class="container">
            <h2 class="section-title">Notre Équipe d'Experts</h2>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-photo">👤</div>
                    <h3 class="member-name">Antoine Moreau</h3>
                    <p class="member-role">Directeur & Gérant</p>
                    <p class="member-bio">Expert immobilier certifié avec 20 ans d'expérience. Spécialisé en gestion de patrimoine et investissement locatif.</p>
                </div>
                <div class="team-member">
                    <div class="member-photo">👤</div>
                    <h3 class="member-name">Isabelle Dubois</h3>
                    <p class="member-role">Responsable Gestion Locative</p>
                    <p class="member-bio">Gestionnaire locative certifiée, elle supervise un portefeuille de plus de 800 biens et assure la satisfaction de nos propriétaires.</p>
                </div>
                <div class="team-member">
                    <div class="member-photo">👤</div>
                    <h3 class="member-name">Thomas Lefevre</h3>
                    <p class="member-role">Syndic Professionnel</p>
                    <p class="member-bio">Syndic diplômé spécialisé dans l'administration de copropriétés, gestion de 150+ copropriétés dans la région.</p>
                </div>
                <div class="team-member">
                    <div class="member-photo">👤</div>
                    <h3 class="member-name">Caroline Petit</h3>
                    <p class="member-role">Conseillère Clientèle</p>
                    <p class="member-bio">Première interlocutrice de nos clients, elle assure un suivi personnalisé et répond à tous vos besoins immobiliers.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Statistiques -->
    <section class="stats-section">
        <div class="container">
            <h2 class="section-title">Nos Chiffres Clés</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number">2000+</span>
                    <span class="stat-label">Biens Gérés</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">150+</span>
                    <span class="stat-label">Copropriétés</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">95%</span>
                    <span class="stat-label">Taux de Satisfaction</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">15+</span>
                    <span class="stat-label">Années d'Expérience</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Appel à l'Action -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Confiez-nous Votre Patrimoine Immobilier</h2>
            <p class="cta-description">Rejoignez plus de 1500 propriétaires qui nous font confiance pour la gestion de leurs biens immobiliers.</p>
            <a href="#contact" class="cta-button">Demander un Devis</a>
        </div>
    </section>
</body>
</html>

<?php
// Include your footer here
include 'footer.php';
?>