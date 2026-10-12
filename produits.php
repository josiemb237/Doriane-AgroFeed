<?php

require_once "connexions.php";


/* =========================================================
   RÉCUPÉRATION DES PRODUITS
========================================================= */

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

$produits = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/* =========================================================
   STATISTIQUES
========================================================= */

$totalProduits = count($produits);

$totalStock = 0;

$chiffreAffaires = 0;

$ruptureStock = 0;


/* =========================================================
   CALCUL DU TOTAL DU STOCK
   ET DES RUPTURES DE STOCK
========================================================= */

foreach ($produits as $produit) {

    $stock = (int) $produit["stock"];

    /* TOTAL DU STOCK */

    $totalStock += $stock;


    /* RUPTURE DE STOCK */

    if ($stock <= 0) {

        $ruptureStock++;

    }

}


/* =========================================================
   CALCUL DU CHIFFRE D'AFFAIRES
========================================================= */

try {

    $stmt = $pdo->query("
        SELECT
            COALESCE(SUM(montant), 0)
            AS total

        FROM vente
    ");

    $resultat = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    $chiffreAffaires = (float) (
        $resultat["total"] ?? 0
    );

} catch (PDOException $e) {

    $chiffreAffaires = 0;

}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Gestion des Produits
</title>


<!-- BOOTSTRAP -->

<link
    rel="stylesheet"
    href="bootstrap-5.0.2-dist/css/bootstrap.min.css"
>


<!-- BOOTSTRAP ICONS -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


<!-- CSS -->

<link
    rel="stylesheet"
    href="dashbord.css"
>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">

    <div class="logo">

        <img
            src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg"
            alt="Logo Doriane Agrofeed"
        >

        <h2>
            PROVENDERIE
        </h2>

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

            <a
                href="connexion.php"
                class="logout"
            >

                <i class="bi bi-box-arrow-right"></i>

                Déconnexion

            </a>

        </li>


    </ul>

</div>


<!-- =====================================================
     CONTENU PRINCIPAL
===================================================== -->

<div class="main">


<header>

    <div>

        <h1>
            Gestion des Produits
        </h1>

        <p>
            Liste complète des produits disponibles.
        </p>

    </div>

</header>


<!-- =====================================================
     CARTES STATISTIQUES
===================================================== -->

<section class="cards">


    <!-- TOTAL PRODUITS -->

    <div class="card bg-primary">

        <i class="bi bi-box-seam"></i>

        <h2>

            <?= number_format(
                $totalProduits,
                0,
                ",",
                " "
            ) ?>

        </h2>

        <p>
            Total produits
        </p>

    </div>


    <!-- TOTAL STOCK -->

    <div class="card bg-warning">

        <i class="bi bi-boxes"></i>

        <h2 class="text-white">

            <?= number_format(
                $totalStock,
                0,
                ",",
                " "
            ) ?>

        </h2>

        <p>
            Total stock
        </p>

    </div>


    <!-- CHIFFRE D'AFFAIRES -->

    <div class="card bg-success">

        <i class="bi bi-cash-stack"></i>

        <h2>

            <?= number_format(
                $chiffreAffaires,
                0,
                ",",
                " "
            ) ?>

        </h2>

        <p>
            Chiffre d'affaires
        </p>

    </div>


    <!-- RUPTURE DE STOCK -->

    <div class="card bg-danger">

        <i class="bi bi-exclamation-triangle-fill"></i>

        <h2>

            <?= number_format(
                $ruptureStock,
                0,
                ",",
                " "
            ) ?>

        </h2>

        <p>
            Rupture de stock
        </p>

    </div>


</section>


<!-- =====================================================
     TABLE DES PRODUITS
===================================================== -->

<section class="table-section">


    <a
        href="ajouter_produit.php"
        class="btn-ajouter"
    >

        <i class="bi bi-plus-circle"></i>

        Ajouter un produit

    </a>


    <div class="table-header">

        <h2>
            Liste des Produits
        </h2>


        <input
            type="text"
            id="recherche"
            placeholder="Rechercher un produit..."
        >

    </div>


    <table id="tableProduits">


        <thead>

            <tr>

                <th>
                    N°
                </th>

                <th>
                    Image
                </th>

                <th>
                    Produit
                </th>

                <th>
                    Catégorie
                </th>

                <th>
                    Prix
                </th>

                <th>
                    Stock
                </th>

                <th>
                    Actions
                </th>

            </tr>

        </thead>


        <tbody>


        <?php if (!empty($produits)): ?>


            <?php foreach ($produits as $produit): ?>


                <tr>


                    <td>

                        <?= (int) $produit["id_produit"] ?>

                    </td>


                    <!-- IMAGE -->

                    <td>


                        <?php if (!empty($produit["image"])): ?>


                            <img
                                src="../img/<?= htmlspecialchars($produit["image"]) ?>"
                                width="60"
                                height="60"
                                style="object-fit: cover;"
                                alt="<?= htmlspecialchars($produit["nom_produit"]) ?>"
                            >


                        <?php else: ?>


                            <span>
                                Aucune image
                            </span>


                        <?php endif; ?>


                    </td>


                    <!-- PRODUIT -->

                    <td>

                        <?= htmlspecialchars(
                            $produit["nom_produit"]
                        ) ?>

                    </td>


                    <!-- CATÉGORIE -->

                    <td>

                        <?= htmlspecialchars(
                            $produit["nom_categorie"]
                        ) ?>

                    </td>


                    <!-- PRIX -->

                    <td>

                        <?= number_format(
                            (float) $produit["prix"],
                            0,
                            ",",
                            " "
                        ) ?>

                        FCFA

                    </td>


                    <!-- STOCK -->

                    <td>

                        <?= (int) $produit["stock"] ?>

                    </td>


                    <!-- ACTIONS -->

                    <td>


                        <a
                            href="modifier_produit.php?id=<?= (int) $produit["id_produit"] ?>"
                            class="btn btn-warning btn-sm"
                        >

                            <i class="bi bi-pencil-square"></i>

                        </a>


                        <a
                            href="supprimer_produit.php?id=<?= (int) $produit["id_produit"] ?>"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Voulez-vous vraiment supprimer ce produit ?');"
                        >

                            <i class="bi bi-trash"></i>

                        </a>


                    </td>


                </tr>


            <?php endforeach; ?>


        <?php else: ?>


            <tr>

                <td
                    colspan="7"
                    class="text-center"
                >

                    Aucun produit trouvé.

                </td>

            </tr>


        <?php endif; ?>


        </tbody>


    </table>


</section>


</div>


<!-- =====================================================
     RECHERCHE PRODUIT
===================================================== -->

<script>

const recherche =
    document.getElementById(
        "recherche"
    );


recherche.addEventListener(
    "keyup",
    function () {


        let valeur =
            this.value.toLowerCase();


        let lignes =
            document.querySelectorAll(
                "#tableProduits tbody tr"
            );


        lignes.forEach(
            function (ligne) {


                ligne.style.display =
                    ligne.textContent
                    .toLowerCase()
                    .includes(valeur)
                    ? ""
                    : "none";


            }
        );


    }
);

</script>


</body>

</html>