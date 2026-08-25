<?php

require_once "connexions.php";

/* =========================================================
   TRAITEMENT DES ACTIONS
========================================================= */

if (
    isset($_GET["action"]) &&
    isset($_GET["id"])
) {

    $action = $_GET["action"];
    $idCommande = (int) $_GET["id"];


    /* =====================================================
       CONFIRMER UNE COMMANDE
    ===================================================== */

    if (
        $action === "statut" &&
        isset($_GET["valeur"]) &&
        $_GET["valeur"] === "Confirmée"
    ) {

        if ($idCommande <= 0) {
            header("Location: commandes.php");
            exit;
        }


        try {

            $pdo->beginTransaction();


            /* ---------------------------------------------
               Récupérer la commande
            --------------------------------------------- */

            $stmtCommande = $pdo->prepare("
                SELECT
                    id_commande,
                    statut
                FROM commande
                WHERE id_commande = ?
                FOR UPDATE
            ");

            $stmtCommande->execute([
                $idCommande
            ]);

            $commande = $stmtCommande->fetch(
                PDO::FETCH_ASSOC
            );


            if (!$commande) {

                throw new Exception(
                    "Commande introuvable."
                );
            }


            /* ---------------------------------------------
               Vérifier si elle est déjà confirmée
            --------------------------------------------- */

            if ($commande["statut"] === "Confirmée") {

                $pdo->commit();

                header(
                    "Location: commandes.php"
                );

                exit;
            }


            /* ---------------------------------------------
               Récupérer les produits
            --------------------------------------------- */

            $stmtLignes = $pdo->prepare("
                SELECT
                    lc.id_produit,
                    lc.quantite,
                    lc.prix_unitaire,
                    lc.sous_total,
                    p.nom_produit,
                    p.stock
                FROM ligne_commande lc

                INNER JOIN produit p
                    ON lc.id_produit = p.id_produit

                WHERE lc.id_commande = ?

                FOR UPDATE
            ");

            $stmtLignes->execute([
                $idCommande
            ]);

            $lignes = $stmtLignes->fetchAll(
                PDO::FETCH_ASSOC
            );


            if (empty($lignes)) {

                throw new Exception(
                    "Cette commande ne contient aucun produit."
                );
            }


            /* ---------------------------------------------
               Vérifier le stock
            --------------------------------------------- */

            foreach ($lignes as $ligne) {

                $quantite = (int) $ligne["quantite"];
                $stock = (int) $ligne["stock"];


                if ($quantite <= 0) {

                    throw new Exception(
                        "Quantité invalide pour le produit : "
                        . $ligne["nom_produit"]
                    );
                }


                if ($quantite > $stock) {

                    throw new Exception(
                        "Stock insuffisant pour : "
                        . $ligne["nom_produit"]
                        . ". Stock disponible : "
                        . $stock
                    );
                }
            }


            /* ---------------------------------------------
               Calcul du montant total
            --------------------------------------------- */

            $montantTotal = 0;

            foreach ($lignes as $ligne) {

                $montantTotal +=
                    (float) $ligne["sous_total"];
            }


            /* ---------------------------------------------
               Diminuer le stock
            --------------------------------------------- */

            $stmtStock = $pdo->prepare("
                UPDATE produit
                SET stock = stock - ?
                WHERE id_produit = ?
            ");


            foreach ($lignes as $ligne) {

                $stmtStock->execute([
                    (int) $ligne["quantite"],
                    (int) $ligne["id_produit"]
                ]);
            }


            /* ---------------------------------------------
               Changer le statut
            --------------------------------------------- */

            $stmtStatut = $pdo->prepare("
                UPDATE commande
                SET statut = 'Confirmée'
                WHERE id_commande = ?
            ");

            $stmtStatut->execute([
                $idCommande
            ]);


            /* ---------------------------------------------
               Vérifier si une vente existe déjà
            --------------------------------------------- */

            $stmtVenteExiste = $pdo->prepare("
                SELECT id_vente
                FROM vente
                WHERE id_commande = ?
                LIMIT 1
            ");

            $stmtVenteExiste->execute([
                $idCommande
            ]);

            $venteExiste = $stmtVenteExiste->fetch(
                PDO::FETCH_ASSOC
            );


            /* ---------------------------------------------
               Ajouter la vente
            --------------------------------------------- */

            if (!$venteExiste) {

                $stmtVente = $pdo->prepare("
                    INSERT INTO vente
                    (
                        id_commande,
                        montant
                    )
                    VALUES (?, ?)
                ");

                $stmtVente->execute([
                    $idCommande,
                    $montantTotal
                ]);
            }


            /* ---------------------------------------------
               Validation transaction
            --------------------------------------------- */

            $pdo->commit();


            header(
                "Location: commandes.php?success=confirmation"
            );

            exit;


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }


            header(
                "Location: commandes.php?error="
                . urlencode($e->getMessage())
            );

            exit;
        }
    }


    /* =====================================================
       REMETTRE UNE COMMANDE EN ATTENTE
    ===================================================== */

    if (
        $action === "statut" &&
        isset($_GET["valeur"]) &&
        $_GET["valeur"] === "En attente"
    ) {

        if ($idCommande > 0) {

            try {

                $pdo->beginTransaction();


                /* -----------------------------------------
                   Vérifier la commande
                ----------------------------------------- */

                $stmt = $pdo->prepare("
                    SELECT
                        statut
                    FROM commande
                    WHERE id_commande = ?
                    FOR UPDATE
                ");

                $stmt->execute([
                    $idCommande
                ]);

                $commande = $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


                if (!$commande) {

                    throw new Exception(
                        "Commande introuvable."
                    );
                }


                /* -----------------------------------------
                   Si elle était confirmée,
                   remettre le stock
                ----------------------------------------- */

                if ($commande["statut"] === "Confirmée") {


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

                    $lignes = $stmtLignes->fetchAll(
                        PDO::FETCH_ASSOC
                    );


                    $stmtStock = $pdo->prepare("
                        UPDATE produit
                        SET stock = stock + ?
                        WHERE id_produit = ?
                    ");


                    foreach ($lignes as $ligne) {

                        $stmtStock->execute([
                            (int) $ligne["quantite"],
                            (int) $ligne["id_produit"]
                        ]);
                    }


                    /* Supprimer la vente */

                    $stmtVente = $pdo->prepare("
                        DELETE FROM vente
                        WHERE id_commande = ?
                    ");

                    $stmtVente->execute([
                        $idCommande
                    ]);
                }


                /* -----------------------------------------
                   Remettre en attente
                ----------------------------------------- */

                $stmtUpdate = $pdo->prepare("
                    UPDATE commande
                    SET statut = 'En attente'
                    WHERE id_commande = ?
                ");

                $stmtUpdate->execute([
                    $idCommande
                ]);


                $pdo->commit();


            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                header(
                    "Location: commandes.php?error="
                    . urlencode($e->getMessage())
                );

                exit;
            }
        }


        header(
            "Location: commandes.php"
        );

        exit;
    }


    /* =====================================================
       SUPPRIMER UNE COMMANDE
    ===================================================== */

    if ($action === "supprimer") {

        if ($idCommande > 0) {

            try {

                $pdo->beginTransaction();


                /* -----------------------------------------
                   Vérifier le statut
                ----------------------------------------- */

                $stmtCommande = $pdo->prepare("
                    SELECT
                        statut
                    FROM commande
                    WHERE id_commande = ?
                    FOR UPDATE
                ");

                $stmtCommande->execute([
                    $idCommande
                ]);

                $commande = $stmtCommande->fetch(
                    PDO::FETCH_ASSOC
                );


                if (!$commande) {

                    throw new Exception(
                        "Commande introuvable."
                    );
                }


                /* -----------------------------------------
                   Si confirmée :
                   remettre le stock
                ----------------------------------------- */

                if ($commande["statut"] === "Confirmée") {


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

                    $lignes = $stmtLignes->fetchAll(
                        PDO::FETCH_ASSOC
                    );


                    $stmtStock = $pdo->prepare("
                        UPDATE produit
                        SET stock = stock + ?
                        WHERE id_produit = ?
                    ");


                    foreach ($lignes as $ligne) {

                        $stmtStock->execute([
                            (int) $ligne["quantite"],
                            (int) $ligne["id_produit"]
                        ]);
                    }


                    /* Supprimer la vente */

                    $stmtVente = $pdo->prepare("
                        DELETE FROM vente
                        WHERE id_commande = ?
                    ");

                    $stmtVente->execute([
                        $idCommande
                    ]);
                }


                /* -----------------------------------------
                   Supprimer les lignes
                ----------------------------------------- */

                $stmtLignes = $pdo->prepare("
                    DELETE FROM ligne_commande
                    WHERE id_commande = ?
                ");

                $stmtLignes->execute([
                    $idCommande
                ]);


                /* -----------------------------------------
                   Supprimer la commande
                ----------------------------------------- */

                $stmtDelete = $pdo->prepare("
                    DELETE FROM commande
                    WHERE id_commande = ?
                ");

                $stmtDelete->execute([
                    $idCommande
                ]);


                $pdo->commit();


            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }


                header(
                    "Location: commandes.php?error="
                    . urlencode(
                        $e->getMessage()
                    )
                );

                exit;
            }
        }


        header(
            "Location: commandes.php?success=suppression"
        );

        exit;
    }
}


/* =========================================================
   RÉCUPÉRER LES COMMANDES
========================================================= */

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


$stmt = $pdo->query($sql);

$commandes = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/* =========================================================
   STATISTIQUES
========================================================= */

$totalCommandes = count($commandes);

$enAttente = 0;

$confirmees = 0;

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

        $chiffreAffaires +=
            (float) $commande["total"];
    }
}


/* =========================================================
   PRODUITS D'UNE COMMANDE
========================================================= */

function getProduitsCommande(
    $pdo,
    $idCommande
) {

    $stmt = $pdo->prepare("

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

    ");


    $stmt->execute([
        $idCommande
    ]);


    return $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );
}


/* =========================================================
   CLASSE STATUT
========================================================= */

function classeStatut($statut)
{

    if ($statut === "Confirmée") {

        return "statut-confirmee";
    }

    return "statut-attente";
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
    Gestion des commandes
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
    href="commandes.css"
>

</head>


<body>


<!-- =====================================================
     MESSAGE
===================================================== -->

<?php if (
    isset($_GET["success"])
): ?>

<div class="alert alert-success alert-dismissible fade show m-3">

    <i class="bi bi-check-circle-fill"></i>

    <?php if (
        $_GET["success"] === "confirmation"
    ): ?>

        <strong>
            Commande confirmée avec succès !
        </strong>

        <br>

        La vente a été enregistrée et le stock a été mis à jour.

    <?php elseif (
        $_GET["success"] === "suppression"
    ): ?>

        <strong>
            Commande supprimée avec succès !
        </strong>

    <?php endif; ?>


    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>

</div>

<?php endif; ?>


<?php if (
    isset($_GET["error"])
): ?>

<div class="alert alert-danger alert-dismissible fade show m-3">

    <i class="bi bi-exclamation-triangle-fill"></i>

    <?= htmlspecialchars(
        $_GET["error"]
    ) ?>


    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>

</div>

<?php endif; ?>


<!-- =====================================================
     DASHBOARD
===================================================== -->

<div class="dashboard">


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="logo">

        <img
            src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg"
            alt="Logo"
        >

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
                class="logout"
            >

                <i class="bi bi-box-arrow-right"></i>

                Déconnexion

            </a>

        </li>


    </ul>

</aside>



<!-- =====================================================
     CONTENU
===================================================== -->

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
                Gérez les commandes et confirmez les ventes.
            </p>

        </div>


        <!-- BOUTON AJOUTER -->

        <a
            href="ajouter_commande.php"
            class="btn btn-success btn-ajouter"
        >

            <i class="bi bi-plus-circle-fill"></i>

            Ajouter une commande

        </a>

    </div>



    <!-- =================================================
         STATISTIQUES
    ================================================= -->

    <div class="stats-grid">


        <!-- TOTAL -->

        <div class="stat-card bg-success">

            <div class="stat-icon">

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


        <!-- EN ATTENTE -->

        <div class="stat-card bg-warning">

            <div class="stat-icon">

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


        <!-- CONFIRMÉES -->

        <div class="stat-card bg-primary">

            <div class="stat-icon">

                <i class="bi bi-check-circle-fill"></i>

            </div>


            <div>

                <span>
                    Confirmées
                </span>

                <strong>
                    <?= $confirmees ?>
                </strong>

            </div>

        </div>


        <!-- CHIFFRE D'AFFAIRES -->

        <div class="stat-card bg-danger">

            <div class="stat-icon primary">

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



    <!-- =================================================
         LISTE DES COMMANDES
    ================================================= -->
    
    <div class="orders-card">
      

        <div class="orders-header">
                <div class="search-box mb-3">
    <div class="input-group">
        <span class="input-group-text">
            <i class="bi bi-search"></i>
        </span>

        <input
            type="text"
            id="rechercheCommande"
            class="form-control"
            placeholder="Rechercher une commande, un client, un produit..."
            autocomplete="off"
        >

        <button
            type="button"
            class="btn btn-outline-secondary"
            id="btnEffacerRecherche"
            title="Effacer"
        >
            <i class="bi bi-x-lg"></i>
          </button>
         </div>
       </div>

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



        <?php if (
            empty($commandes)
        ): ?>


            <div class="empty-state">

                <i class="bi bi-cart-x"></i>


                <h3>
                    Aucune commande
                </h3>


                <p>
                    Aucune commande n'a encore été enregistrée.
                </p>


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
                                $commande[
                                    "id_commande"
                                ]
                            );

                        ?>


                        <tr>


                            <!-- NUMÉRO -->

                            <td>

                                <span class="order-number">

                                    #

                                    <?= (int)
                                        $commande[
                                            "id_commande"
                                        ] ?>

                                </span>

                            </td>


                            <!-- CLIENT -->

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


                            <!-- PRODUITS -->

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


                                            <small>

                                                x
                                                <?= (int)
                                                    $produit[
                                                        "quantite"
                                                    ] ?>

                                            </small>

                                        </div>


                                    <?php endforeach; ?>


                                </div>

                            </td>


                            <!-- QUANTITÉ -->

                            <td>

                                <span class="quantity-badge">

                                    <?= (int)
                                        $commande[
                                            "nombre_articles"
                                        ] ?>

                                </span>

                            </td>


                            <!-- TOTAL -->

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


                            <!-- DATE -->

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


                            <!-- STATUT -->

                            <td>


                                <div class="dropdown">


                                    <button
                                        class="status-button <?= classeStatut($commande["statut"]) ?>"
                                        data-bs-toggle="dropdown"
                                    >

                                        <?= htmlspecialchars(
                                            $commande[
                                                "statut"
                                            ]
                                        ) ?>


                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <ul class="dropdown-menu">


                                        <!-- EN ATTENTE -->

                                        <li>

                                            <a
                                                class="dropdown-item"
                                                href="commandes.php?action=statut&id=<?= (int)$commande["id_commande"] ?>&valeur=En%20attente"
                                            >

                                                <i class="bi bi-clock"></i>

                                                En attente

                                            </a>

                                        </li>


                                        <!-- CONFIRMÉE -->

                                        <li>

                                            <a
                                                class="dropdown-item"
                                                href="commandes.php?action=statut&id=<?= (int)$commande["id_commande"] ?>&valeur=Confirm%C3%A9e"
                                            >

                                                <i class="bi bi-check-circle-fill text-success"></i>

                                                Confirmer

                                            </a>

                                        </li>


                                    </ul>

                                </div>


                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <div class="actions-buttons">


                                    <a
                                        href="commandes.php?action=supprimer&id=<?= (int)$commande["id_commande"] ?>"
                                        class="action-btn delete"
                                        title="Supprimer"
                                        onclick="return confirmerSuppression();"
                                    >

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
        "Voulez-vous vraiment supprimer cette commande ?\n\n"
        + "Si la commande est confirmée, "
        + "le stock sera automatiquement restauré."
    );

}
const rechercheCommande = document.getElementById("rechercheCommande");
const btnEffacerRecherche = document.getElementById("btnEffacerRecherche");

rechercheCommande.addEventListener("input", function () {

    const recherche = this.value.toLowerCase().trim();

    const lignes = document.querySelectorAll(
        ".orders-table tbody tr"
    );

    lignes.forEach(function (ligne) {

        const texte = ligne.textContent.toLowerCase();

        if (texte.includes(recherche)) {
            ligne.style.display = "";
        } else {
            ligne.style.display = "none";
        }

    });

});


btnEffacerRecherche.addEventListener("click", function () {

    rechercheCommande.value = "";

    const lignes = document.querySelectorAll(
        ".orders-table tbody tr"
    );

    lignes.forEach(function (ligne) {
        ligne.style.display = "";
    });

    rechercheCommande.focus();

});
</script>


</body>

</html>