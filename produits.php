<?php

require_once "connexions.php";

/* Récupération des produits */
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
    ORDER BY p.id_produit DESC
";

$stmt = $pdo->query($sql);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* Statistiques */
$totalProduits = count($produits);

$disponibles = 0;
$stockFaible = 0;
$rupture = 0;

foreach ($produits as $produit) {

    if ($produit['stock'] > 10) {
        $disponibles++;
    }

    if ($produit['stock'] > 0 && $produit['stock'] <= 10) {
        $stockFaible++;
    }

    if ($produit['stock'] == 0) {
        $rupture++;
    }
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Gestion des Produits</title>

<link rel="stylesheet"
      href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet" href="dashbord.css">
     

</head>

<body>

<!-- ================= SIDEBAR ================= -->

<div class="sidebar">

    <div class="logo">

        <img src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg">

        <h2>PROVENDERIE</h2>

    </div>

    <ul>

        <li>
            <a href="dashboard.php">
                <i class="bi bi-speedometer2"></i>
                Tableau de bord
            </a>
        </li>
        <li>
            <a href="clients.php">
                <i class="bi bi-people-fill"></i>
                Clients
            </a>
        </li>

        <li>
            <a href="commandes.php">
                <i class="bi bi-cart-fill"></i>
                Commandes
            </a>
        </li>

        <li class="active">
            <a href="produits.php">
                <i class="bi bi-box-seam"></i>
                Produits
            </a>
        </li>

        <li>
            <a href="ventes.php">
                <i class="bi bi-cash-stack"></i>
                Ventes
            </a>
        </li>

        <li>
            <a href="index.php">
                <i class="bi bi-house-door-fill"></i>
                Aller au site
            </a>
        </li>

        <li>
            <a href="connexion.php" class="logout">
                <i class="bi bi-box-arrow-right"></i>
                Déconnexion
            </a>
        </li>

    </ul>

</div>


<div class="main">

<header>

    <div>

        <h1>Gestion des Produits</h1>

        <p>
            Liste complète des produits disponibles.
        </p>

    </div>

</header>

<section class="cards">

    <div class="card bg-primary">

        <i class="bi bi-box-seam"></i>

        <h2>
            <?= $totalProduits ?>
        </h2>

        <p>Produits</p>

    </div>


    <div class="card bg-warning">

        <i class="bi bi-check-circle-fill"></i>

        <h2 class="text-white">
            <?= $disponibles ?>
        </h2>

        <p>Disponibles</p>

    </div>


    <div class="card bg-danger">

        <i class="bi bi-exclamation-circle-fill"></i>

        <h2>
            <?= $stockFaible ?>
        </h2>

        <p>Stock faible</p>

    </div>


    <div class="card bg-success">

        <i class="bi bi-x-circle-fill"></i>

        <h2>
            <?= $rupture ?>
        </h2>

        <p>Rupture</p>

    </div>

</section>

<section class="table-section">


<a href="ajouter_produit.php"
   class="btn-ajouter">

    <i class="bi bi-plus-circle"></i>

    Ajouter un produit

</a>


<div class="table-header">

    <h2>Liste des Produits</h2>

    <input
        type="text"
        id="recherche"
        placeholder="Rechercher un produit..."
    >

</div>


<table id="tableProduits">

<thead>

<tr>

    <th>N°</th>

    <th>Image</th>

    <th>Produit</th>

    <th>Catégorie</th>

    <th>Prix</th>

    <th>Stock</th>

    <th>Actions</th>

</tr>

</thead>


<tbody>

<?php foreach ($produits as $produit): ?>

<tr>

<td>
    <?= $produit['id_produit'] ?>
</td>


<td>

<?php if (!empty($produit['image'])): ?>

<img
    src="../img/<?= htmlspecialchars($produit['image']) ?>"
    width="60"
    height="60"
    style="object-fit: cover;"
>

<?php else: ?>

<span>Aucune image</span>

<?php endif; ?>

</td>


<td>

<?= htmlspecialchars($produit['nom_produit']) ?>

</td>


<td>

<?= htmlspecialchars($produit['nom_categorie']) ?>

</td>


<td>

<?= number_format(
    $produit['prix'],
    0,
    ',',
    ' '
) ?>

FCFA

</td>


<td>

<?= $produit['stock'] ?>

</td>


<td>

<a
    href="modifier_produit.php?id=<?= $produit['id_produit'] ?>"
    class="btn btn-warning btn-sm"
>

<i class="bi bi-pencil-square"></i>

</a>


<a
    href="supprimer_produit.php?id=<?= $produit['id_produit'] ?>"
    class="btn btn-danger btn-sm"
    onclick="return confirm('Voulez-vous vraiment supprimer ce produit ?');"
>

<i class="bi bi-trash"></i>

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</section>

</div>


<script>

const recherche =
document.getElementById("recherche");

recherche.addEventListener("keyup", function () {

    let valeur =
        this.value.toLowerCase();

    let lignes =
        document.querySelectorAll(
            "#tableProduits tbody tr"
        );

    lignes.forEach(function (ligne) {

        ligne.style.display =
            ligne.textContent
            .toLowerCase()
            .includes(valeur)
            ? ""
            : "none";

    });

});

</script>

</body>

</html>