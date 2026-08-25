<?php
session_start ();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>site de pronvenderie</title> 
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
 <body>
 <nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-0">
         <div class="container-fluid">
            <a class="navbar-brand  bg-white px-4 py-3" href="#">
                <img src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg" alt="logo" width="100px">
            </a>
        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse " id="menu">

           
            <ul class="navbar-nav mx-auto text-uppercase">

                <li class="nav-item">
                    <a class="nav-link active px-3 " href="index.php">Accueil</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active px-3" href="a propos.php">À propos</a>
                </li>

                <li class="nav-item dropdown">

                    <a class="nav-link active px-3" href="services.php">

                        Services
                    </a>

                </li>
                <li class="nav-item dropdown">

                    <a class="nav-link active px-3" href="produit.php">

                        produits
                    </a>

                </li>

                <li class="nav-item">
                    <a class="nav-link active px-3" href="galerie.php">Galerie</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active px-3" href="contact.php">Contact</a>
                </li>
                

            </ul>


            <?php if (isset($_SESSION["connecte"]) && $_SESSION["connecte"] === true): ?>

                <a href="deconexion.php" class="nav-deconnexion">
                    Déconnexion
                </a>

            <?php else: ?>

                <a href="connexion.php" class="nav-connexion">
                    Connexion
                </a>

                <a href="inscription_client.php" class="nav-inscription">
                    Inscription
                </a>

            <?php endif; ?>

        </nav>

                  
    </div>

</nav>
<div class="floating-whatsapp">
    <a href="https://wa.me/237676870980?text=Bonjour%20Doriane%20Agro%20Feed,%20je%20souhaite%20obtenir%20des%20informations." class="floating-whatsapp" target="_blank">

    <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp">

</a>
    </div>

    <section class="presentation">

    <div class="presentation-text">

        <h2>Bienvenue chez Doriane Agro Feed</h2>

        <p>
            Doriane Agro Feed est une provenderie située au Marché B,
            Première Rue à Bafoussam, spécialisée dans la vente
            d'aliments pour animaux, de matières premières,
            de vitamines et de compléments nutritionnels.

            Nous mettons à votre disposition des produits de qualité
            afin d'améliorer les performances de votre élevage.
        </p>

        <a href="a propos.html" class="btn-s">En savoir plus</a>

    </div>

</section>


        <section class="hero py-5">

<div class="container">

<div class="row align-items-center">



<div class="col-lg-6">

<span class="badge bg-success mb-4">
Leader en alimentation animale
</span>

<h1 class="display-3 fw-bold mb-4">
Nourrissez vos animaux avec une alimentation de qualité.
</h1>

<p class="text-secondary fs-5 mb-4">
Doriane Agro Feed accompagne les éleveurs avec des aliments
de qualité, des vitamines et des compléments nutritionnels
pour les volailles, bovins, porcs, lapins et poissons.
</p>

<a href="produit.php" class="btn btn-warning btn-lg me-3">
Commander
</a>

<a href="contact.php" class="btn btn-success btn-lg">
Nous contacter
</a>

</div>



<div class="col-lg-6">

<div class="hero-box">

<img src="img/yo7t-burY1KfM6Mfub0xCSXdfZLSganU5kjVdsFX40-rqNiId_ghGEm8-V4CVJKz1FVkGU57xlQIRpfgeBCoWk5t4XyclLdtalG3b_A4z2fA5N0b_KeISMpss3hAEsTCVNGQvE42vDl1bcwPxmZpwtUh7TyHaVaS-sqCglfnzVg.jpeg" class="hero-img" alt="Provenderie">

<div class="mini-card top">
🐔
<p>Volailles</p>
</div>

<div class="mini-card left">
🐄
<p>Bovins</p>
</div>

<div class="mini-card right">
🐖
<p>Porcs</p>
</div>

<div class="mini-card bottom-left">
🐇
<p>Lapins</p>
</div>

<div class="mini-card bottom-right">
🐟
<p>Poissons</p>
</div>

<div class="mini-card bottom">
🌽
<p>Aliments</p>
</div>

</div>

</div>

</div>

</div>

</section>
<section class="featured">

    <div class="title">

        <span>Nos Produits</span>

        <h2>Produits Vedettes</h2>

    </div>

    <div class="featured-container">

        <div class="featured-card">

            <img src="img/WhatsApp Image 2026-07-14 at 13.33.44 (1).jpeg" alt="Maïs">

            <h3>Maïs</h3>

            <a href="produit.html">Voir plus</a>

        </div>

        <div class="featured-card">

            <img src="img/WhatsApp Image 2026-07-14 at 13.33.44.jpeg" alt="Soja">

            <h3>Soja</h3>

            <a href="produit.html">Voir plus</a>

        </div>

        <div class="featured-card">

            <img src="img/WhatsApp Image 2026-07-14 at 14.22.07.jpeg" alt="Palmiste">

            <h3>Palmiste</h3>

            <a href="produit.html">Voir plus</a>

        </div>

        <div class="featured-card">

            <img src="img/WhatsApp Image 2026-07-14 at 13.33.43.jpeg" alt="Belgoforce">

            <h3>Belgoforce Concentré</h3>

            <a href="produit.html">Voir plus</a>

        </div>

    </div>

</section>
<footer class="bg-dark text-light pt-5 pb-3">

    <div class="container">

        <div class="row">

            <div class="col-lg-3 col-md-6 mb-4">

                <img src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg" alt="Logo" width="90" class="mb-3">

                <p>
                    Votre partenaire de confiance pour
                    l'alimentation et la nutrition animale.
                </p>

            </div>

            
            <div class="col-lg-2 col-md-6 mb-4">

                <h5 class="text-warning fw-bold">Navigation</h5>

                <ul class="nav flex-column">

                    <li class="nav-item">
                        <a href="index.php" class="nav-link text-light p-1">Accueil</a>
                    </li>

                    <li class="nav-item">
                        <a href="a propos.php" class="nav-link text-light p-1">À propos</a>
                    </li>

                    <li class="nav-item">
                        <a href="services.php" class="nav-link text-light p-1">Nos services</a>
                    </li>
                      <li class="nav-item">
                        <a href="produit.php" class="nav-link text-light p-1">Nos produits</a>
                    </li>
                     

                    <li class="nav-item">
                        <a href="galerie.php" class="nav-link text-light p-1">Galerie</a>
                    </li>

                    <li class="nav-item">
                        <a href="contact.php" class="nav-link text-light p-1">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a href="comande.php" class="nav-link text-light p-1">Comande</a>
                    </li>

                </ul>

            </div>

            <div class="col-lg-3 col-md-6 mb-4">

                <h5 class="text-warning fw-bold">Contact</h5>

                <p>📍 Marché B, Première Rue,<br>Bafoussam</p>

                <p>📞 +237 683 37 66 74</p>

                <p>✉ contact@dorianeagrofeed.com</p>

            </div>

    
            <div class="col-lg-2 col-md-6 mb-4">

                <h5 class="text-warning fw-bold">Horaires</h5>

                <p>🕒 Lundi - Samedi</p>

                <p>08h30 - 17h30</p>

            </div>

            
            <div class="col-lg-2 col-md-6 mb-4">

                <h5 class="text-warning fw-bold">Suivez-nous</h5>

                <a href="https://wa.me/237683376674"
                   target="_blank"
                   class="btn btn-success rounded-circle me-2">

                    <i class="bi bi-whatsapp"></i>

                </a>

                <a href="https://facebook.com"
                   target="_blank"
                   class="btn btn-primary rounded-circle">

                    <i class="bi bi-facebook"></i>

                </a>

            </div>

        </div>

        <hr class="border-secondary">

        <div class="text-center">

            <p class="mb-0">
                © 2026 <strong>Doriane Agro Feed</strong> | Tous droits réservés.
            </p>

        </div>

    </div>

</footer>
<script src="bootstrap-5.0.2-dist/js/bootstrap.min.js"></script>
</body>
</html>

