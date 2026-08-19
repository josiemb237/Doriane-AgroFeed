<?php

require_once "connexions.php";


if (
    isset($_GET["action"])
    &&
    $_GET["action"] === "statut"
    &&
    isset($_GET["id"])
    &&
    isset($_GET["valeur"])
) {

    $idCommande =
        intval($_GET["id"]);

    $statut =
        $_GET["valeur"];


    $statutsAutorises = [
        "En attente",
        "Confirmée",
        "En préparation",
        "Livrée",
        "Annulée"
    ];


    if (
        $idCommande > 0
        &&
        in_array(
            $statut,
            $statutsAutorises,
            true
        )
    ) {

        $stmt = $pdo->prepare("
            UPDATE commande
            SET statut = ?
            WHERE id_commande = ?
        ");

        $stmt->execute([
            $statut,
            $idCommande
        ]);
    }


    header(
        "Location: commandes.php"
    );

    exit;
}
if (
    isset($_GET["action"])
    &&
    $_GET["action"] === "supprimer"
    &&
    isset($_GET["id"])
) {

    $idCommande =
        intval($_GET["id"]);


    if ($idCommande > 0) {

        try {

            $pdo->beginTransaction();


            /* -----------------------------------------
               Récupérer les lignes pour remettre le stock
            ----------------------------------------- */

            $stmtLignes = $pdo->prepare("
                SELECT
                    id_produit,
                    quantite
                FROM ligne_commande
                WHERE id_commande = ?
            ");

            $stmtLignes->execute([
                $idCommande
            ]);

            $lignes =
                $stmtLignes->fetchAll(
                    PDO::FETCH_ASSOC
                );


            /* -----------------------------------------
               Remettre les produits dans le stock
            ----------------------------------------- */

            $stmtStock = $pdo->prepare("
                UPDATE produit
                SET stock = stock + ?
                WHERE id_produit = ?
            ");


            foreach ($lignes as $ligne) {

                $stmtStock->execute([
                    $ligne["quantite"],
                    $ligne["id_produit"]
                ]);
            }
            $stmtDeleteLignes =
                $pdo->prepare("
                    DELETE FROM ligne_commande
                    WHERE id_commande = ?
                ");

            $stmtDeleteLignes->execute([
                $idCommande
            ]);
            $stmtDeleteCommande =
                $pdo->prepare("
                    DELETE FROM commande
                    WHERE id_commande = ?
                ");

            $stmtDeleteCommande->execute([
                $idCommande
            ]);


            $pdo->commit();


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            die(
                "Erreur lors de la suppression : "
                . htmlspecialchars(
                    $e->getMessage()
                )
            );
        }
    }


    header(
        "Location: commandes.php"
    );

    exit;
}
$sql = "

    SELECT

        c.id_commande,

        c.date_commande,

        c.statut,

        u.nom,

        u.prenom,

        u.telephone,

        COALESCE(
            SUM(
                lc.quantite *
                lc.prix_unitaire
            ),
            0
        ) AS total,

        COALESCE(
            SUM(lc.quantite),
            0
        ) AS nombre_articles

    FROM commande c

    INNER JOIN utilisateurs u

        ON c.id_utilisateur =
           u.id_utilisateur

    LEFT JOIN ligne_commande lc

        ON c.id_commande =
           lc.id_commande

    GROUP BY

        c.id_commande,
        c.date_commande,
        c.statut,
        u.nom,
        u.prenom,
        u.telephone

    ORDER BY
        c.id_commande DESC

";


$stmt =
    $pdo->query($sql);

$commandes =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

$totalCommandes =
    count($commandes);

$enAttente = 0;

$confirmees = 0;

$preparation = 0;

$livrees = 0;

$chiffreAffaires = 0;


foreach ($commandes as $commande) {

    if (
        $commande["statut"]
        === "En attente"
    ) {

        $enAttente++;
    }


    if (
        $commande["statut"]
        === "Confirmée"
    ) {

        $confirmees++;
    }


    if (
        $commande["statut"]
        === "En préparation"
    ) {

        $preparation++;
    }


    if (
        $commande["statut"]
        === "Livrée"
    ) {

        $livrees++;

        $chiffreAffaires +=
            (float) $commande["total"];
    }
}

function getProduitsCommande(
    $pdo,
    $idCommande
) {

    $sql = "

        SELECT

            p.nom_produit,

            lc.quantite,

            lc.prix_unitaire,

            lc.sous_total

        FROM ligne_commande lc

        INNER JOIN produit p

            ON lc.id_produit =
               p.id_produit

        WHERE
            lc.id_commande = ?

        ORDER BY
            p.nom_produit ASC

    ";


    $stmt =
        $pdo->prepare($sql);

    $stmt->execute([
        $idCommande
    ]);


    return $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );
}

function classeStatut($statut)
{

    switch ($statut) {

        case "Livrée":
            return "statut-livree";

        case "Confirmée":
            return "statut-confirmee";

        case "En préparation":
            return "statut-preparation";

        case "Annulée":
            return "statut-annulee";

        default:
            return "statut-attente";
    }
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Gestion des commandes</title>


<link rel="stylesheet"
      href="bootstrap-5.0.2-dist/css/bootstrap.min.css">


<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<link rel="stylesheet"
      href="commandes.css">

</head>


<body>
<?php if (isset($_GET["success"]) && $_GET["success"] == "1"): ?>

<div class="alert alert-success alert-dismissible fade show m-3">

    <i class="bi bi-check-circle-fill"></i>

    <strong>Commande enregistrée avec succès !</strong>

    <?php if (isset($_GET["id_commande"])): ?>

        <br>

        Numéro de commande :
        <strong>
            #<?= htmlspecialchars($_GET["id_commande"]) ?>
        </strong>

    <?php endif; ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>

</div>

<?php endif; ?>

<div class="dashboard">

<aside class="sidebar">

    <div class="logo">

        <img
            src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg"
            alt="Logo">

        <h2>
            DORIANE AGROFEED
        </h2>

    </div>


    <ul class="menu">

        <li>

            <a href="dashboard.php">

                <i class="bi bi-speedometer2"></i>

                Tableau de bord

            </a>

        </li>


        <li>

            <a href="gestion_site.php">

                <i class="bi bi-gear-fill"></i>

                Gestion du site

            </a>

        </li>


        <li>

            <a href="clients.php">

                <i class="bi bi-people-fill"></i>

                Clients

            </a>

        </li>


        <li class="active">

            <a href="commandes.php">

                <i class="bi bi-cart-fill"></i>

                Commandes

            </a>

        </li>


        <li>

            <a href="produits.php">

                <i class="bi bi-box-seam-fill"></i>

                Produits

            </a>

        </li>


        <li>

            <a href="ventes.php">

                <i class="bi bi-graph-up-arrow"></i>

                Ventes

            </a>

        </li>


        <li>

            <a href="index.php">

                <i class="bi bi-globe"></i>

                Aller au site

            </a>

        </li>


        <li>

            <a
                href="connexion.php"
                class="logout">

                <i class="bi bi-box-arrow-right"></i>

                Déconnexion

            </a>

        </li>

    </ul>

</aside>



<main class="main-content">

<div class="page-header">

    <div>

        <span class="small-title">
            ADMINISTRATION
        </span>

        <h1>

            <i class="bi bi-cart-check-fill"></i>

            Gestion des commandes

        </h1>

        <p>
            Gérez les commandes et suivez leur évolution.
        </p>

    </div>
</div>


<div class="stats-grid">


    <div class="stat-card bg-success">

        <div class="stat-icon blue">

            <i class="bi bi-cart-fill"></i>

        </div>

        <div>

            <span>
                Total commandes
            </span>

            <strong>
                <?= $totalCommandes ?>
            </strong>

        </div>

    </div>



    <div class="stat-card bg-secondary">

        <div class="stat-icon orange">

            <i class="bi bi-clock-fill"></i>

        </div>

        <div>

            <span>
                En attente
            </span>

            <strong>
                <?= $enAttente ?>
            </strong>

        </div>

    </div>



    <div class="stat-card bg-danger">

        <div class="stat-icon purple">

            <i class="bi bi-box-seam-fill"></i>

        </div>

        <div>

            <span>
                En préparation
            </span>

            <strong>
                <?= $preparation ?>
            </strong>

        </div>

    </div>



    <div class="stat-card bg-primary">

        <div class="stat-icon green">

            <i class="bi bi-check-circle-fill"></i>

        </div>

        <div>

            <span>
                Livrées
            </span>

            <strong>
                <?= $livrees ?>
            </strong>

        </div>

    </div>



    <div class="stat-card bg-warning">

        <div class="stat-icon money">

            <i class="bi bi-cash-stack"></i>

        </div>

        <div>

            <span>
                Chiffre d'affaires
            </span>

            <strong>

                <?= number_format(
                    $chiffreAffaires,
                    0,
                    ",",
                    " "
                ) ?>

                FCFA

            </strong>

        </div>

    </div>

</div>


<div class="orders-card">


    <div class="orders-header">

        <div>

            <h2>
                Liste des commandes
            </h2>

            <p>
                <?= $totalCommandes ?>
                commande(s) enregistrée(s)
            </p>

        </div>

    </div>



    <?php if (empty($commandes)): ?>

        <div class="empty-state">

            <i class="bi bi-cart-x"></i>

            <h3>
                Aucune commande
            </h3>

            <p>
                Aucune commande n'a encore été enregistrée.
            </p>

            <a
                href="ajouter_commande.php"
                class="btn btn-success">

                <i class="bi bi-plus-circle"></i>

                Ajouter une commande

            </a>

        </div>


    <?php else: ?>


        <div class="table-responsive">

            <table class="table orders-table">

                <thead>

                    <tr>

                        <th>
                            N°
                        </th>

                        <th>
                            Client
                        </th>

                        <th>
                            Produits
                        </th>

                        <th>
                            Qté
                        </th>

                        <th>
                            Total
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Statut
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach (
                    $commandes
                    as
                    $commande
                ): ?>


                    <?php

                    $produitsCommande =
                        getProduitsCommande(
                            $pdo,
                            $commande["id_commande"]
                        );

                    ?>


                    <tr>
                        <td>

                            <span class="order-number">

                                #
                                <?= $commande[
                                    "id_commande"
                                ] ?>

                            </span>

                        </td>

                        <td>

                            <div class="client">

                                <div class="client-avatar">

                                    <i class="bi bi-person-fill"></i>

                                </div>

                                <div>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $commande["nom"]
                                            . " "
                                            . $commande["prenom"]
                                        ) ?>

                                    </strong>


                                    <?php if (
                                        !empty(
                                            $commande[
                                                "telephone"
                                            ]
                                        )
                                    ): ?>

                                        <small>

                                            <i class="bi bi-telephone"></i>

                                            <?= htmlspecialchars(
                                                $commande[
                                                    "telephone"
                                                ]
                                            ) ?>

                                        </small>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </td>

                        <td>

                            <div class="products-list">

                                <?php foreach (
                                    $produitsCommande
                                    as
                                    $produit
                                ): ?>

                                    <div class="product-line">

                                        <span>

                                            <?= htmlspecialchars(
                                                $produit[
                                                    "nom_produit"
                                                ]
                                            ) ?>

                                        </span>

                                        

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </td>

                        <td>

                            <span class="quantity-badge">

                                <?= $commande[
                                    "nombre_articles"
                                ] ?>

                            </span>

                        </td>

                        <td>

                            <strong class="order-total">

                                <?= number_format(
                                    $commande["total"],
                                    0,
                                    ",",
                                    " "
                                ) ?>

                                FCFA

                            </strong>

                        </td>

                        <td>

                            <div class="date">

                                <i class="bi bi-calendar3"></i>

                                <?= date(
                                    "d/m/Y",
                                    strtotime(
                                        $commande[
                                            "date_commande"
                                        ]
                                    )
                                ) ?>

                                <small>

                                    <?= date(
                                        "H:i",
                                        strtotime(
                                            $commande[
                                                "date_commande"
                                            ]
                                        )
                                    ) ?>

                                </small>

                            </div>

                        </td>



                        <td>

                            <div class="dropdown">

                                <button
                                    class="status-button <?= classeStatut($commande["statut"]) ?>"
                                    data-bs-toggle="dropdown">

                                    <?= htmlspecialchars(
                                        $commande["statut"]
                                    ) ?>

                                    <i class="bi bi-chevron-down"></i>

                                </button>


                                <ul class="dropdown-menu">

                                    <li>

                                        <a
                                            class="dropdown-item"
                                            href="commandes.php?action=statut&id=<?= $commande["id_commande"] ?>&valeur=En%20attente">

                                            <i class="bi bi-clock"></i>

                                            En attente

                                        </a>

                                    </li>


                                    <li>

                                        <a
                                            class="dropdown-item"
                                            href="commandes.php?action=statut&id=<?= $commande["id_commande"] ?>&valeur=Confirm%C3%A9e">

                                            <i class="bi bi-check"></i>

                                            Confirmée

                                        </a>

                                    </li>


                                    <li>

                                        <a
                                            class="dropdown-item"
                                            href="commandes.php?action=statut&id=<?= $commande["id_commande"] ?>&valeur=En%20pr%C3%A9paration">

                                            <i class="bi bi-box-seam"></i>

                                            En préparation

                                        </a>

                                    </li>


                                    <li>

                                        <a
                                            class="dropdown-item"
                                            href="commandes.php?action=statut&id=<?= $commande["id_commande"] ?>&valeur=Livr%C3%A9e">

                                            <i class="bi bi-check-circle"></i>

                                            Livrée

                                        </a>

                                    </li>


                                    <li>

                                        <a
                                            class="dropdown-item text-danger"
                                            href="commandes.php?action=statut&id=<?= $commande["id_commande"] ?>&valeur=Annul%C3%A9e">

                                            <i class="bi bi-x-circle"></i>

                                            Annulée

                                        </a>

                                    </li>

                                </ul>

                            </div>

                        </td>


                        

                        <td>

                            <div class="actions-buttons">
                                <a
                                    href="commandes.php?action=supprimer&id=<?= $commande["id_commande"] ?>"
                                    class="action-btn delete"
                                    title="Supprimer"
                                    onclick="return confirmerSuppression();">

                                    <i class="bi bi-trash-fill"></i>

                                </a>

                            </div>

                        </td>

                    </tr>


                <?php endforeach; ?>


                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

</main>

</div>


<script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>


<script>

function confirmerSuppression() {

    return confirm(
        "Voulez-vous vraiment supprimer cette commande ?\n\nLe stock des produits sera remis automatiquement."
    );

}

</script>


</body>

</html>

