<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>services</title>
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
            
        </div>

    </div>

</nav>

<section class="services" id="services">

    <div class="title">

        <span>Nos Services</span>

        <h2>Ce que nous proposons</h2>

        <p>
            Nous mettons à votre disposition des produits de qualité
            et des services adaptés à tous les éleveurs.
        </p>

    </div>

    <div class="service-container">

        <div class="service-box">
            <img src="img/WhatsApp Image 2026-07-14 at 13.33.40.jpeg" alt="poule">

            <h3> Aliments pour volailles</h3>

            <p>
                Des aliments adaptés aux poussins, poulets de chair et poules pondeuses.
            </p>
            

        </div>

        <div class="service-box">
<img src="img/WhatsApp Image 2026-07-14 at 13.33.41.jpeg" alt="lapins">
            <h3>Aliments pour lapins</h3>

            <p>
                Une alimentation équilibrée pour assurer la croissance la bonne croissance et une santé des lapins.
            </p>

        </div>

        <div class="service-box">
<img src="img/WhatsApp Image 2026-07-14 at 13.33.40 (1).jpeg" alt="porc">
            <h3> Aliments pour porcs</h3>

            <p>
                Des produits riches en protéines pour toutes les étapes d'élevage.
            </p>

        </div>

        <div class="service-box">
<img src="img/WhatsApp Image 2026-07-14 at 13.33.39.jpeg" alt="livraison">
            <h3>Livraison</h3>

            <p>
                Livraison rapide de vos commandes selon votre localisation.
            </p>

        </div>

    </div>

</section>

             <a href="https://wa.me/237676870980" target="_blank" class="whatsapp-btn">
           
             
        </div>

    </div>
   
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
                        <a href="index.html" class="nav-link text-light p-1">Accueil</a>
                    </li>

                    <li class="nav-item">
                        <a href="a propos.html" class="nav-link text-light p-1">À propos</a>
                    </li>

                    <li class="nav-item">
                        <a href="services.html" class="nav-link text-light p-1">Nos services</a>
                    </li>

                    <li class="nav-item">
                        <a href="galerie.html" class="nav-link text-light p-1">Galerie</a>
                    </li>

                    <li class="nav-item">
                        <a href="contact.html" class="nav-link text-light p-1">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a href="comande.html" class="nav-link text-light p-1">Comande</a>
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


