<?php

require_once "connexions.php";

/* =========================================================
   FONCTIONS
========================================================= */

function formatFCFA($montant)
{
    return number_format(
        (float)$montant,
        0,
        ",",
        " "
    ) . " FCFA";
}


/* =========================================================
   STATISTIQUES VENTES
========================================================= */

/* Total des ventes */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM vente
");

$totalVentes = (int)$stmt->fetchColumn();


/* Chiffre d'affaires */

$stmt = $pdo->query("
    SELECT COALESCE(SUM(montant), 0)
    FROM vente
");

$chiffreAffaires = (float)$stmt->fetchColumn();


/* Ventes aujourd'hui */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM vente
    WHERE DATE(date_vente) = CURRENT_DATE()
");

$ventesAujourdHui = (int)$stmt->fetchColumn();


/* Chiffre d'affaires aujourd'hui */

$stmt = $pdo->query("
    SELECT COALESCE(SUM(montant), 0)
    FROM vente
    WHERE DATE(date_vente) = CURRENT_DATE()
");

$caAujourdHui = (float)$stmt->fetchColumn();


/* Ventes du mois */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM vente
    WHERE MONTH(date_vente) = MONTH(CURRENT_DATE())
    AND YEAR(date_vente) = YEAR(CURRENT_DATE())
");

$ventesMois = (int)$stmt->fetchColumn();


/* Chiffre d'affaires du mois */

$stmt = $pdo->query("
    SELECT COALESCE(SUM(montant), 0)
    FROM vente
    WHERE MONTH(date_vente) = MONTH(CURRENT_DATE())
    AND YEAR(date_vente) = YEAR(CURRENT_DATE())
");

$caMois = (float)$stmt->fetchColumn();


/* =========================================================
   STATISTIQUES COMMANDES
========================================================= */

/* Total commandes */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM commande
");

$totalCommandes = (int)$stmt->fetchColumn();


/* Commandes en attente */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM commande
    WHERE statut = 'En attente'
");

$commandesAttente = (int)$stmt->fetchColumn();


/* Commandes livrées */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM commande
    WHERE statut = 'Livrée'
");

$commandesLivrees = (int)$stmt->fetchColumn();


/* Commandes annulées */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM commande
    WHERE statut = 'Annulée'
");

$commandesAnnulees = (int)$stmt->fetchColumn();


/* =========================================================
   STATISTIQUES PRODUITS
========================================================= */

/* Total produits */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM produit
");

$totalProduits = (int)$stmt->fetchColumn();


/* Produits disponibles */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM produit
    WHERE stock > 0
");

$produitsDisponibles = (int)$stmt->fetchColumn();


/* Produits stock faible */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM produit
    WHERE stock > 0
    AND stock <= 10
");

$stockFaible = (int)$stmt->fetchColumn();


/* Produits en rupture */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM produit
    WHERE stock = 0
");

$produitsRupture = (int)$stmt->fetchColumn();


/* =========================================================
   STATISTIQUES CLIENTS
========================================================= */

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM utilisateurs
");

$totalClients = (int)$stmt->fetchColumn();


/* =========================================================
   ÉVOLUTION DES VENTES
   6 DERNIERS MOIS
========================================================= */

$ventesParMois = [];

$stmt = $pdo->query("
    SELECT
        DATE_FORMAT(date_vente, '%Y-%m') AS mois,
        COUNT(*) AS nombre_ventes,
        COALESCE(SUM(montant), 0) AS chiffre_affaires
    FROM vente
    WHERE date_vente >= DATE_SUB(
        DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'),
        INTERVAL 5 MONTH
    )
    GROUP BY DATE_FORMAT(date_vente, '%Y-%m')
    ORDER BY mois ASC
");

$resultatsMois = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultatsMois as $ligne) {

    $ventesParMois[$ligne["mois"]] = [
        "ventes" => (int)$ligne["nombre_ventes"],
        "ca" => (float)$ligne["chiffre_affaires"]
    ];
}


/* Noms des mois en français */

$nomsMois = [
    "01" => "Jan",
    "02" => "Fév",
    "03" => "Mar",
    "04" => "Avr",
    "05" => "Mai",
    "06" => "Juin",
    "07" => "Juil",
    "08" => "Août",
    "09" => "Sep",
    "10" => "Oct",
    "11" => "Nov",
    "12" => "Déc"
];


/* Préparer les 6 derniers mois */

$graphiqueMois = [];

for ($i = 5; $i >= 0; $i--) {

    $date = new DateTime();

    $date->modify("-$i months");

    $cle = $date->format("Y-m");

    $numeroMois = $date->format("m");

    $graphiqueMois[] = [
        "mois" => $nomsMois[$numeroMois],
        "ventes" => $ventesParMois[$cle]["ventes"] ?? 0,
        "ca" => $ventesParMois[$cle]["ca"] ?? 0
    ];
}


/* Trouver la valeur maximale */

$maximumVentes = 0;

foreach ($graphiqueMois as $mois) {

    if ($mois["ventes"] > $maximumVentes) {
        $maximumVentes = $mois["ventes"];
    }
}


/* =========================================================
   DERNIÈRES COMMANDES
========================================================= */

$stmt = $pdo->query("
    SELECT
        c.id_commande,
        c.statut,
        u.nom,
        u.prenom,
        c.date_commande
    FROM commande c
    INNER JOIN utilisateurs u
        ON c.id_utilisateur = u.id_utilisateur
    ORDER BY c.id_commande DESC
    LIMIT 6
");

$dernieresCommandes = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   PRODUITS EN STOCK FAIBLE
========================================================= */

$stmt = $pdo->query("
    SELECT
        id_produit,
        nom_produit,
        stock
    FROM produit
    WHERE stock <= 10
    ORDER BY stock ASC
    LIMIT 6
");

$produitsFaibles = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   PRODUITS LES PLUS VENDUS
========================================================= */

$stmt = $pdo->query("
    SELECT
        p.nom_produit,
        SUM(lc.quantite) AS quantite_vendue
    FROM ligne_commande lc

    INNER JOIN produit p
        ON lc.id_produit = p.id_produit

    GROUP BY p.id_produit, p.nom_produit

    ORDER BY quantite_vendue DESC

    LIMIT 5
");

$produitsPopulaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    Dashboard - Doriane AgroFeed
</title>


<link
    rel="stylesheet"
    href="bootstrap-5.0.2-dist/css/bootstrap.min.css"
>

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>

<link
    rel="stylesheet"
    href="dashboards.css"
>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="logo">

        <img
            src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg"
            alt="Logo Doriane AgroFeed"
        >

        <div>

            <h2>
                DORIANE
            </h2>

            <span>
                AGROFEED
            </span>

        </div>

    </div>


    <ul class="menu">


        <li class="active">

            <a href="dashboard.php">

                <i class="bi bi-speedometer2"></i>

                <span>
                    Tableau de bord
                </span>

            </a>

        </li>


        <li>

            <a href="gestion_site.php">

                <i class="bi bi-gear-fill"></i>

                <span>
                    Gestion du site
                </span>

            </a>

        </li>


        <li>

            <a href="clients.php">

                <i class="bi bi-people-fill"></i>

                <span>
                    Clients
                </span>

            </a>

        </li>


        <li>

            <a href="commandes.php">

                <i class="bi bi-cart-fill"></i>

                <span>
                    Commandes
                </span>

            </a>

        </li>


        <li>

            <a href="produits.php">

                <i class="bi bi-box-seam-fill"></i>

                <span>
                    Produits
                </span>

            </a>

        </li>


        <li>

            <a href="ventes.php">

                <i class="bi bi-cash-stack"></i>

                <span>
                    Ventes
                </span>

            </a>

        </li>


        <li>

            <a href="../index.php">

                <i class="bi bi-house-fill"></i>

                <span>
                    Aller au site
                </span>

            </a>

        </li>


        <li class="logout">

            <a href="admin.php">

                <i class="bi bi-box-arrow-right"></i>

                <span>
                    Déconnexion
                </span>

            </a>

        </li>


    </ul>

</aside>



<!-- =====================================================
     CONTENU PRINCIPAL
===================================================== -->

<main class="dashboard">


    <!-- HEADER -->

    <header class="dashboard-header">

        <div>

            <div class="title-line">

                <span class="title-icon">

                    <i class="bi bi-speedometer2"></i>

                </span>

                <div>

                    <h1>
                        Tableau de bord
                    </h1>

                    <p>
                        Vue générale de votre activité
                    </p>

                </div>

            </div>

        </div>


        <div class="header-date">

            <i class="bi bi-calendar3"></i>

            <?= date("d/m/Y") ?>

        </div>

    </header>



    <!-- =================================================
         STATISTIQUES PRINCIPALES
    ================================================== -->

    <section class="main-stats">


        <!-- CHIFFRE AFFAIRES -->

        <div class="main-card card-green">

            <div class="card-top">

                <div class="card-icon">

                    <i class="bi bi-cash-stack"></i>

                </div>

                <span class="card-label">
                    Chiffre d'affaires
                </span>

            </div>

            <h2>
                <?= formatFCFA($chiffreAffaires) ?>
            </h2>

            <p>
                <i class="bi bi-arrow-up"></i>

                <?= formatFCFA($caMois) ?>

                ce mois
            </p>

        </div>



        <!-- VENTES -->

        <div class="main-card card-blue">

            <div class="card-top">

                <div class="card-icon">

                    <i class="bi bi-receipt"></i>

                </div>

                <span class="card-label">
                    Total des ventes
                </span>

            </div>

            <h2>
                <?= $totalVentes ?>
            </h2>

            <p>

                <i class="bi bi-calendar-check"></i>

                <?= $ventesMois ?>

                ce mois

            </p>

        </div>



        <!-- COMMANDES -->

        <div class="main-card card-orange">

            <div class="card-top">

                <div class="card-icon">

                    <i class="bi bi-cart-check"></i>

                </div>

                <span class="card-label">
                    Commandes
                </span>

            </div>

            <h2>
                <?= $totalCommandes ?>
            </h2>

            <p>

                <i class="bi bi-hourglass-split"></i>

                <?= $commandesAttente ?>

                en attente

            </p>

        </div>



        <!-- CLIENTS -->

        <div class="main-card card-purple">

            <div class="card-top">

                <div class="card-icon">

                    <i class="bi bi-people-fill"></i>

                </div>

                <span class="card-label">
                    Clients
                </span>

            </div>

            <h2>
                <?= $totalClients ?>
            </h2>

            <p>

                <i class="bi bi-person-check"></i>

                clients enregistrés

            </p>

        </div>


    </section>



    <!-- =================================================
         DEUXIÈME LIGNE
    ================================================== -->

    <section class="small-stats">


        <div class="small-card">

            <div class="small-icon blue-icon">

                <i class="bi bi-cart-check-fill"></i>

            </div>

            <div>

                <span>
                    Ventes aujourd'hui
                </span>

                <strong>
                    <?= $ventesAujourdHui ?>
                </strong>

                <small>
                    <?= formatFCFA($caAujourdHui) ?>
                </small>

            </div>

        </div>


        <div class="small-card">

            <div class="small-icon green-icon">

                <i class="bi bi-check-circle-fill"></i>

            </div>

            <div>

                <span>
                    Commandes livrées
                </span>

                <strong>
                    <?= $commandesLivrees ?>
                </strong>

                <small>
                    Commandes terminées
                </small>

            </div>

        </div>


        <div class="small-card">

            <div class="small-icon orange-icon">

                <i class="bi bi-clock-fill"></i>

            </div>

            <div>

                <span>
                    En attente
                </span>

                <strong>
                    <?= $commandesAttente ?>
                </strong>

                <small>
                    À traiter
                </small>

            </div>

        </div>


        <div class="small-card">

            <div class="small-icon red-icon">

                <i class="bi bi-exclamation-triangle-fill"></i>

            </div>

            <div>

                <span>
                    Stock faible
                </span>

                <strong>
                    <?= $stockFaible ?>
                </strong>

                <small>
                    Produits à surveiller
                </small>

            </div>

        </div>


        <div class="small-card">

            <div class="small-icon dark-icon">

                <i class="bi bi-x-circle-fill"></i>

            </div>

            <div>

                <span>
                    Ruptures
                </span>

                <strong>
                    <?= $produitsRupture ?>
                </strong>

                <small>
                    Produits indisponibles
                </small>

            </div>

        </div>


    </section>



    <!-- =================================================
         GRAPHIQUE + ÉTAT STOCK
    ================================================== -->

    <section class="dashboard-grid">


        <!-- ÉVOLUTION DES VENTES -->

        <div class="dashboard-box graph-box">


            <div class="box-header">

                <div>

                    <h2>

                        <i class="bi bi-bar-chart-fill"></i>

                        Évolution des ventes

                    </h2>

                    <p>
                        Nombre de ventes sur les 6 derniers mois
                    </p>

                </div>


                <span class="box-badge">
                    6 mois
                </span>

            </div>


            <div class="graph">

                <?php foreach ($graphiqueMois as $mois): ?>

                    <?php

                    if ($maximumVentes > 0) {

                        $hauteur =
                            ($mois["ventes"] / $maximumVentes) * 100;

                    } else {

                        $hauteur = 0;

                    }

                    ?>

                    <div class="bar-column">

                        <div class="bar-value">

                            <?= $mois["ventes"] ?>

                        </div>


                        <div class="bar-area">

                            <div
                                class="bar"
                                style="height: <?= $hauteur ?>%;"
                            ></div>

                        </div>


                        <span class="bar-label">

                            <?= $mois["mois"] ?>

                        </span>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>



        <!-- ÉTAT DU STOCK -->

        <div class="dashboard-box stock-overview">


            <div class="box-header">

                <div>

                    <h2>

                        <i class="bi bi-box-seam-fill"></i>

                        État du stock

                    </h2>

                    <p>
                        Situation actuelle des produits
                    </p>

                </div>

                <a href="produits.php">
                    Voir
                </a>

            </div>


            <div class="stock-total">

                <strong>
                    <?= $totalProduits ?>
                </strong>

                <span>
                    Produits au total
                </span>

            </div>


            <div class="stock-progress">

                <?php

                $pourcentageDisponible = 0;

                if ($totalProduits > 0) {

                    $pourcentageDisponible =
                        ($produitsDisponibles / $totalProduits) * 100;
                }

                ?>

                <div
                    class="progress-fill"
                    style="width: <?= $pourcentageDisponible ?>%;"
                ></div>

            </div>


            <div class="stock-details">


                <div>

                    <span class="dot green"></span>

                    <span>
                        Disponibles
                    </span>

                    <strong>
                        <?= $produitsDisponibles ?>
                    </strong>

                </div>


                <div>

                    <span class="dot orange"></span>

                    <span>
                        Stock faible
                    </span>

                    <strong>
                        <?= $stockFaible ?>
                    </strong>

                </div>


                <div>

                    <span class="dot red"></span>

                    <span>
                        Rupture
                    </span>

                    <strong>
                        <?= $produitsRupture ?>
                    </strong>

                </div>


            </div>

        </div>


    </section>



    <!-- =================================================
         DERNIÈRES COMMANDES
    ================================================== -->

    <section class="dashboard-grid bottom-grid">


        <div class="dashboard-box">


            <div class="box-header">

                <div>

                    <h2>

                        <i class="bi bi-clock-history"></i>

                        Dernières commandes

                    </h2>

                    <p>
                        Les dernières commandes enregistrées
                    </p>

                </div>


                <a href="commandes.php">
                    Voir tout
                </a>

            </div>


            <?php if (empty($dernieresCommandes)): ?>

                <div class="empty">

                    <i class="bi bi-cart-x"></i>

                    <p>
                        Aucune commande enregistrée.
                    </p>

                </div>

            <?php else: ?>


                <div class="orders-list">


                    <?php foreach ($dernieresCommandes as $commande): ?>

                        <?php

                        $statutClasse = "other";

                        if ($commande["statut"] === "Livrée") {

                            $statutClasse = "delivered";

                        } elseif ($commande["statut"] === "En attente") {

                            $statutClasse = "pending";

                        } elseif ($commande["statut"] === "Annulée") {

                            $statutClasse = "cancelled";

                        }

                        ?>


                        <div class="order-item">


                            <div class="order-icon">

                                <i class="bi bi-person-fill"></i>

                            </div>


                            <div class="order-info">

                                <strong>

                                    <?= htmlspecialchars(
                                        $commande["nom"]
                                        . " "
                                        . $commande["prenom"]
                                    ) ?>

                                </strong>

                                <span>

                                    Commande #

                                    <?= $commande["id_commande"] ?>

                                </span>

                            </div>


                            <div class="order-date">

                                <?= date(
                                    "d/m/Y",
                                    strtotime(
                                        $commande["date_commande"]
                                    )
                                ) ?>

                            </div>


                            <span
                                class="order-status <?= $statutClasse ?>"
                            >

                                <?= htmlspecialchars(
                                    $commande["statut"]
                                ) ?>

                            </span>


                            <a
                                href="commandes.php"
                                class="view-order"
                                title="Voir"
                            >

                                <i class="bi bi-eye-fill"></i>

                            </a>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </div>



        <!-- =================================================
             PRODUITS POPULAIRES
        ================================================== -->

        <div class="dashboard-box">


            <div class="box-header">

                <div>

                    <h2>

                        <i class="bi bi-trophy-fill"></i>

                        Produits les plus vendus

                    </h2>

                    <p>
                        Produits les plus demandés
                    </p>

                </div>

                <a href="produits.php">
                    Produits
                </a>

            </div>


            <?php if (empty($produitsPopulaires)): ?>

                <div class="empty">

                    <i class="bi bi-box"></i>

                    <p>
                        Aucune vente disponible.
                    </p>

                </div>

            <?php else: ?>


                <div class="popular-list">


                    <?php

                    $rang = 1;

                    foreach ($produitsPopulaires as $produit):

                    ?>


                        <div class="popular-item">


                            <span class="rank">

                                <?= $rang ?>

                            </span>


                            <div class="popular-icon">

                                <i class="bi bi-box-seam"></i>

                            </div>


                            <div class="popular-info">

                                <strong>

                                    <?= htmlspecialchars(
                                        $produit["nom_produit"]
                                    ) ?>

                                </strong>

                                <span>
                                    Produit
                                </span>

                            </div>


                            <strong class="quantity">

                                <?= (int)$produit["quantite_vendue"] ?>

                                vendu(s)

                            </strong>


                        </div>


                    <?php

                    $rang++;

                    endforeach;

                    ?>


                </div>


            <?php endif; ?>


        </div>


    </section>



    <!-- =================================================
         ALERTES STOCK
    ================================================== -->

    <section class="dashboard-box alerts-box">


        <div class="box-header">

            <div>

                <h2>

                    <i class="bi bi-bell-fill"></i>

                    Alertes et notifications

                </h2>

                <p>
                    Éléments nécessitant votre attention
                </p>

            </div>

        </div>


        <div class="alerts">


            <?php if ($commandesAttente > 0): ?>

                <div class="alert-item warning">

                    <i class="bi bi-clock-fill"></i>

                    <div>

                        <strong>
                            Commandes en attente
                        </strong>

                        <span>

                            <?= $commandesAttente ?>

                            commande(s) doivent être traitées.

                        </span>

                    </div>

                    <a href="commandes.php">
                        Voir
                    </a>

                </div>

            <?php endif; ?>


            <?php if ($stockFaible > 0): ?>

                <div class="alert-item orange-alert">

                    <i class="bi bi-exclamation-triangle-fill"></i>

                    <div>

                        <strong>
                            Stock faible
                        </strong>

                        <span>

                            <?= $stockFaible ?>

                            produit(s) ont un stock faible.

                        </span>

                    </div>

                    <a href="produits.php">
                        Voir
                    </a>

                </div>

            <?php endif; ?>


            <?php if ($produitsRupture > 0): ?>

                <div class="alert-item danger">

                    <i class="bi bi-x-circle-fill"></i>

                    <div>

                        <strong>
                            Produits en rupture
                        </strong>

                        <span>

                            <?= $produitsRupture ?>

                            produit(s) ne sont plus disponibles.

                        </span>

                    </div>

                    <a href="produits.php">
                        Voir
                    </a>

                </div>

            <?php endif; ?>


            <?php if (
                $commandesAttente == 0 &&
                $stockFaible == 0 &&
                $produitsRupture == 0
            ): ?>

                <div class="no-alert">

                    <i class="bi bi-check-circle-fill"></i>

                    <div>

                        <strong>
                            Tout est en ordre
                        </strong>

                        <span>
                            Aucune alerte importante actuellement.
                        </span>

                    </div>

                </div>

            <?php endif; ?>


        </div>

    </section>


</main>


</body>

</html>