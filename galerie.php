<?php
session_start ();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>galerie</title>
     <link rel="stylesheet" href="galerie.css">
     <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<style>
    
.nav-auth {
    display: flex;
    align-items: center;
    gap: 10px;

    margin-left: 15px;
}


/* Style commun */

.nav-auth a {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 6px;

    padding: 9px 15px;

    border: 2px solid;

    border-radius: 8px;

    text-decoration: none;

    font-size: 14px;

    font-weight: bold;

    transition: all 0.3s ease;

    white-space: nowrap;
}


/* =========================================================
   CONNEXION
   Couleur : bleu cyan
========================================================= */

.nav-connexion {
    color: #0dcaf0;

    border-color: #0dcaf0;

    background: transparent;
}

.nav-connexion:hover {
    background-color: #0dcaf0;

    color: white;

    transform: translateY(-2px);

    box-shadow: 0 5px 12px rgba(13, 202, 240, 0.25);
}


/* =========================================================
   INSCRIPTION
   Couleur : vert
========================================================= */

.nav-inscription {
    color: #198754;

    border-color: #198754;

    background: transparent;
}

.nav-inscription:hover {
    background-color: #198754;

    color: white;

    transform: translateY(-2px);

    box-shadow: 0 5px 12px rgba(25, 135, 84, 0.25);
}


/* =========================================================
   DECONNEXION
   Couleur : orange
========================================================= */

.nav-deconnexion {
    color: #fd7e14;

    border-color: #fd7e14;

    background: transparent;
}

.nav-deconnexion:hover {
    background-color: #fd7e14;

    color: white;

    transform: translateY(-2px);

    box-shadow: 0 5px 12px rgba(253, 126, 20, 0.25);
}
@media (max-width: 700px) {

    .nav-auth {
        flex-direction: column;
        align-items: stretch;

        width: 100%;

        margin: 10px 0 0;
    }

    .nav-auth a {
        width: 100%;
    }
}

    </style>
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
              <div class="nav-auth">

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
            </div>

        </div>

    </div>

</nav>
<section class="gallery-banner">

    <div class="over">

    <div class="banner-content">

        <h2 id="gallery-title">
            Galerie Doriane Agro Feed
        </h2>

        <p>
            Découvrez nos produits de qualité pour accompagner le développement de votre élevage.
        </p>

    </div>
</div>
</section>
<section class="galerie">

    <div class="galerie-container">

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 13.33.44 (1).jpeg" alt="">
            <h3>Maïs</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 13.33.41 (1).jpeg" alt="">
            <h3>Arachides</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 13.33.44.jpeg" alt="">
            <h3>Soja</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 13.33.43.jpeg" alt="">
            <h3>Belgoforce Concentré</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 14.22.07 (1).jpeg" alt="">
            <h3>Coton</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 13.33.45.jpeg" alt="">
            <h3>Son</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 14.22.07.jpeg" alt="">
            <h3>Palmiste</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 14.18.09 (1).jpeg" alt="">
            <h3>Vitamines</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 14.18.07.jpeg" alt="">
            <h3>Fer</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 13.33.40.jpeg" alt="">
            <h3>Aliment Volailles</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 13.33.40 (1).jpeg" alt="">
            <h3>Aliment Porcs</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 13.33.41.jpeg" alt="">
            <h3>Aliment Lapins</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 13.33.43 (1).jpeg" alt="">
            <h3>Concentré</h3>
        </div>

        <div class="galerie-card">
            <img src="img/yo7t-burY1KfM6Mfub0xCSXdfZLSganU5kjVdsFX40-rqNiId_ghGEm8-V4CVJKz1FVkGU57xlQIRpfgeBCoWk5t4XyclLdtalG3b_A4z2fA5N0b_KeISMpss3hAEsTCVNGQvE42vDl1bcwPxmZpwtUh7TyHaVaS-sqCglfnzVg.jpeg" alt="">
            <h3>Compléments Minéraux</h3>
        </div>

        <div class="galerie-card">
            <img src="img/OIP.jpg" alt="">
            <h3>Compléments Nutritionnels</h3>
        </div>

        <div class="galerie-card">
            <img src="img/InShot_20260716_141025195.jpg" alt="">
            <h3>Minéraux</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-14 at 14.18.08 (1).jpeg" alt="">
            <h3>Produits Vétérinaires</h3>
        </div>
      <div class="galerie-card">
            <img src="img/73Ic65u2IvRTTQrUCxxYYaT5ig4lt_FGZbZFeJK6hJiviaQ7WbEwIAHEYrgzlTvKQvINpyIhvMufxAphzsGZpXVVVfuNKf11c5eyATiccsuyisYPTK9t40jKIii757B4kkhQ1W6GcG62KVkOZA2q4ugJE3uf4WGbZaAIhMriVj4.jpeg" alt="">
            <h3>Élevage Moderne</h3>
        </div>

        <div class="galerie-card">
            <img src="img/5l6uBYVds32fq69mHmG1xzSnDtwwZIV_4IeWfwQ1un6lPgvW6R2GiG2rfiX2UEE7a82sAKzgXsZZwJqJQFFWsz6QG8QcG5-rsifleW3-suZIbYrCwTZB9oZRzrs7R-p9mLDwfbFPiInop_u17MYVk-Gz96n12VHP71e6D8P49c0.jpeg" alt="">
            <h3>Nutrition Animale</h3>
        </div>

        <div class="galerie-card">
            <img src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg" alt="">
            <h3>Doriane Agro Feed</h3>
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

