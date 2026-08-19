
<?php

require_once "connexions.php";

$sql = "
    SELECT
        p.id_produit,
        p.nom_produit,
        p.description,
        p.prix,
        p.stock,
        p.image,
        c.nom_categorie

    FROM produit p

    INNER JOIN categorie c
        ON p.id_categorie = c.id_categorie

    ORDER BY p.id_produit ASC
";

$stmt = $pdo->query($sql);

$produits =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Nos produits - Doriane AgroFeed</title>

<link
rel="stylesheet"
href="produit.css">

<link
rel="stylesheet"
href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
                 <li class="nav-item">
                    <a class="nav-link active px-3" href="comande.php">Comande</a>
                </li>

            </ul>

           

        </div>

    </div>

</nav>

<section class="produits-header">

    <h1>
        Nos produits
    </h1>

    <p>
        Découvrez nos produits de qualité
        pour l'alimentation animale.
    </p>

</section>

<section class="produits-container">


<?php if (count($produits) > 0): ?>


<?php foreach ($produits as $produit): ?>


<article class="produit-card">
<div class="image-container">

<?php if (!empty($produit["image"])): ?>

    <img
        src="../img/<?= htmlspecialchars($produit["image"]) ?>"
        alt="<?= htmlspecialchars($produit["nom_produit"]) ?>"
    >

<?php else: ?>

    <img
        src="../img/default.jpg"
        alt="Produit"
    >

<?php endif; ?>


</div>


<div class="produit-info">


<h2>

<?= htmlspecialchars(
$produit["nom_produit"]
) ?>

</h2>


<p class="description">

<?= htmlspecialchars(
$produit["description"]
) ?>

</p>


<p class="prix">

<?= number_format(
$produit["prix"],
0,
",",
" "
) ?>

FCFA

</p>


<p class="stock">

<i class="bi bi-box-seam"></i>

<?php if ($produit["stock"] > 0): ?>

Stock disponible :
<?= $produit["stock"] ?>

<?php else: ?>

<span style="color:red;">
Rupture de stock
</span>

<?php endif; ?>

</p>


<div class="actions">


<?php if ($produit["stock"] > 0): ?>

<a
href="ajouter_commande.php?produit=<?= $produit["id_produit"] ?>"
class="btn-commander">

<i class="bi bi-cart-fill"></i>

Commander

</a>

<?php else: ?>

<button
class="btn btn-secondary"
disabled>

Rupture de stock

</button>

<?php endif; ?>


<details>

<summary class="btn-details">

<i class="bi bi-info-circle"></i>

Détails

</summary>


<div class="details-content">


<p>

<strong>
Nom :
</strong>

<?= htmlspecialchars(
$produit["nom_produit"]
) ?>

</p>


<p>

<strong>
Catégorie :
</strong>

<?= htmlspecialchars(
$produit["nom_categorie"]
) ?>

</p>


<p>

<strong>
Description :
</strong>

<?= htmlspecialchars(
$produit["description"]
) ?>

</p>


<p>

<strong>
Prix :
</strong>

<?= number_format(
$produit["prix"],
0,
",",
" "
) ?>

FCFA

</p>


<p>

<strong>
Stock :
</strong>

<?= $produit["stock"] ?>

</p>


</div>

</details>
</div>

</div>

</div>

</article>


<?php endforeach; ?>


<?php else: ?>


<p>
Aucun produit disponible.
</p>


<?php endif; ?>


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

       