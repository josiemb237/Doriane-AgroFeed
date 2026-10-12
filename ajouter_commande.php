<?php

require_once "connexions.php";

/* =========================================================
   VARIABLES
========================================================= */

$erreur = "";
$succes = "";


/* =========================================================
   PRODUIT PRÉ-SÉLECTIONNÉ
========================================================= */

$produitSelectionne = null;

if (isset($_GET["produit"])) {

    $idProduit = (int) $_GET["produit"];

    if ($idProduit > 0) {

        $stmt = $pdo->prepare("
            SELECT
                id_produit,
                nom_produit,
                description,
                prix,
                stock
            FROM produit
            WHERE id_produit = ?
            AND stock > 0
            LIMIT 1
        ");

        $stmt->execute([$idProduit]);

        $produitSelectionne =
            $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


/* =========================================================
   RÉCUPÉRER LES PRODUITS DISPONIBLES
========================================================= */

$stmtProduits = $pdo->query("
    SELECT
        id_produit,
        nom_produit,
        description,
        prix,
        stock
    FROM produit
    WHERE stock > 0
    ORDER BY nom_produit ASC
");

$produits =
    $stmtProduits->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   ENREGISTRER LA COMMANDE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* -----------------------------------------------------
       INFORMATIONS CLIENT
    ----------------------------------------------------- */

    $nom = trim($_POST["nom"] ?? "");

    $prenom = trim($_POST["prenom"] ?? "");

    $telephone = trim($_POST["telephone"] ?? "");


    /* -----------------------------------------------------
       PRODUITS
    ----------------------------------------------------- */

    $produitsCommande =
        $_POST["produit"] ?? [];

    $quantites =
        $_POST["quantite"] ?? [];


    /* =====================================================
       VÉRIFICATION CLIENT
    ===================================================== */

    if (
        $nom === "" ||
        $prenom === "" ||
        $telephone === ""
    ) {

        $erreur =
            "Veuillez remplir le nom, le prénom et le téléphone du client.";

    } else {


        /* =================================================
           CONSTRUIRE LES LIGNES DE COMMANDE
        ================================================= */

        $lignesCommande = [];


        if (is_array($produitsCommande)) {

            foreach (
                $produitsCommande as $index => $idProduit
            ) {

                $idProduit =
                    (int) $idProduit;

                $quantite =
                    (int) ($quantites[$index] ?? 0);


                if (
                    $idProduit > 0 &&
                    $quantite > 0
                ) {

                    /*
                     * Si le même produit est sélectionné
                     * plusieurs fois, on additionne les quantités.
                     */

                    if (
                        isset(
                            $lignesCommande[$idProduit]
                        )
                    ) {

                        $lignesCommande[$idProduit]
                            += $quantite;

                    } else {

                        $lignesCommande[$idProduit]
                            = $quantite;
                    }
                }
            }
        }


        /* =================================================
           VÉRIFIER LES PRODUITS
        ================================================= */

        if (empty($lignesCommande)) {

            $erreur =
                "Veuillez sélectionner au moins un produit et indiquer une quantité.";

        } else {


            try {

                /* =================================================
                   DÉBUT TRANSACTION
                ================================================= */

                $pdo->beginTransaction();


                /* =================================================
                   RECHERCHER LE CLIENT
                ================================================= */

                $stmtClient = $pdo->prepare("
                    SELECT
                        id_utilisateur,
                        nom,
                        prenom,
                        telephone,
                        role
                    FROM utilisateurs
                    WHERE telephone = ?
                    AND role = 'client'
                    LIMIT 1
                    FOR UPDATE
                ");

                $stmtClient->execute([
                    $telephone
                ]);

                $client =
                    $stmtClient->fetch(
                        PDO::FETCH_ASSOC
                    );


                /* =================================================
                   CLIENT EXISTANT
                ================================================= */

                if ($client) {

                    $idUtilisateur =
                        (int) $client["id_utilisateur"];


                    /* ---------------------------------------------
                       Mettre à jour les informations
                    --------------------------------------------- */

                    $stmtUpdateClient = $pdo->prepare("
                        UPDATE utilisateurs
                        SET
                            nom = ?,
                            prenom = ?
                        WHERE id_utilisateur = ?
                    ");

                    $stmtUpdateClient->execute([
                        $nom,
                        $prenom,
                        $idUtilisateur
                    ]);
                }


                /* =================================================
                   NOUVEAU CLIENT
                ================================================= */

                else {

                    /*
                     * On crée uniquement :
                     * nom
                     * prénom
                     * téléphone
                     * rôle
                     *
                     * L'e-mail n'est PAS inséré ici.
                     *
                     * Cela évite les problèmes de Duplicate entry
                     * sur la colonne email.
                     */

                    $stmtInsertClient = $pdo->prepare("
                        INSERT INTO utilisateurs
                        (
                            nom,
                            prenom,
                            telephone,
                            role
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            'client'
                        )
                    ");

                    $stmtInsertClient->execute([
                        $nom,
                        $prenom,
                        $telephone
                    ]);


                    $idUtilisateur =
                        (int) $pdo->lastInsertId();
                }


                /* =================================================
                   VÉRIFICATION ID UTILISATEUR
                ================================================= */

                if ($idUtilisateur <= 0) {

                    throw new Exception(
                        "Impossible de récupérer l'identifiant du client."
                    );
                }


                /* =================================================
                   CRÉER LA COMMANDE
                ================================================= */

                $stmtCommande = $pdo->prepare("
                    INSERT INTO commande
                    (
                        id_utilisateur,
                        statut
                    )
                    VALUES
                    (
                        ?,
                        'En attente'
                    )
                ");

                $stmtCommande->execute([
                    $idUtilisateur
                ]);


                /* =================================================
                   RÉCUPÉRER ID COMMANDE
                ================================================= */

                $idCommande =
                    (int) $pdo->lastInsertId();


                if ($idCommande <= 0) {

                    throw new Exception(
                        "Impossible de créer la commande."
                    );
                }


                /* =================================================
                   PRÉPARER REQUÊTE PRODUIT
                ================================================= */

                $stmtProduit = $pdo->prepare("
                    SELECT
                        id_produit,
                        nom_produit,
                        prix,
                        stock
                    FROM produit
                    WHERE id_produit = ?
                    FOR UPDATE
                ");


                /* =================================================
                   PRÉPARER INSERTION LIGNE COMMANDE
                ================================================= */

                $stmtLigne = $pdo->prepare("
                    INSERT INTO ligne_commande
                    (
                        id_commande,
                        id_produit,
                        quantite,
                        prix_unitaire,
                        sous_total
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");


                /* =================================================
                   PRÉPARER MISE À JOUR STOCK
                ================================================= */

                $stmtStock = $pdo->prepare("
                    UPDATE produit
                    SET stock = stock - ?
                    WHERE id_produit = ?
                ");


                /* =================================================
                   ENREGISTRER CHAQUE PRODUIT
                ================================================= */

                foreach (
                    $lignesCommande
                    as $idProduit => $quantite
                ) {


                    /* ---------------------------------------------
                       Récupérer le produit
                    --------------------------------------------- */

                    $stmtProduit->execute([
                        $idProduit
                    ]);

                    $produit =
                        $stmtProduit->fetch(
                            PDO::FETCH_ASSOC
                        );


                    /* ---------------------------------------------
                       Produit inexistant
                    --------------------------------------------- */

                    if (!$produit) {

                        throw new Exception(
                            "Le produit sélectionné est introuvable."
                        );
                    }


                    /* ---------------------------------------------
                       Vérifier la quantité
                    --------------------------------------------- */

                    if ($quantite <= 0) {

                        throw new Exception(
                            "La quantité du produit "
                            . $produit["nom_produit"]
                            . " est invalide."
                        );
                    }


                    /* ---------------------------------------------
                       Vérifier le stock
                    --------------------------------------------- */

                    $stock =
                        (int) $produit["stock"];


                    if ($quantite > $stock) {

                        throw new Exception(
                            "Stock insuffisant pour le produit : "
                            . $produit["nom_produit"]
                            . ". Stock disponible : "
                            . $stock
                        );
                    }


                    /* ---------------------------------------------
                       Prix
                    --------------------------------------------- */

                    $prix =
                        (float) $produit["prix"];


                    /* ---------------------------------------------
                       Sous-total
                    --------------------------------------------- */

                    $sousTotal =
                        $prix * $quantite;


                    /* ---------------------------------------------
                       Enregistrer ligne commande
                    --------------------------------------------- */

                    $stmtLigne->execute([
                        $idCommande,
                        $idProduit,
                        $quantite,
                        $prix,
                        $sousTotal
                    ]);


                    /* ---------------------------------------------
                       Diminuer le stock
                    --------------------------------------------- */

                    $stmtStock->execute([
                        $quantite,
                        $idProduit
                    ]);


                    /* ---------------------------------------------
                       Vérification mise à jour stock
                    --------------------------------------------- */

                    if (
                        $stmtStock->rowCount() <= 0
                    ) {

                        throw new Exception(
                            "Impossible de mettre à jour le stock du produit : "
                            . $produit["nom_produit"]
                        );
                    }
                }


                /* =================================================
                   VALIDER LA TRANSACTION
                ================================================= */

                $pdo->commit();


                /* =================================================
                   REDIRECTION VERS COMMANDES.PHP
                ================================================= */

                header(
                    "Location: commandes.php?success=ajout&id_commande="
                    . $idCommande
                );

                exit;


            } catch (Throwable $e) {


                /* =================================================
                   ANNULER LA TRANSACTION
                ================================================= */

                if ($pdo->inTransaction()) {

                    $pdo->rollBack();
                }


                /* =================================================
                   MESSAGE D'ERREUR
                ================================================= */

                $erreur =
                    "Impossible d'enregistrer la commande : "
                    . $e->getMessage();
            }
        }
    }
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
        Nouvelle commande - DORIANE AGROFEED
    </title>


    <!-- Bootstrap -->

    <link
        rel="stylesheet"
        href="bootstrap-5.0.2-dist/css/bootstrap.min.css"
    >


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        body {

            margin: 0;

            background: #f5f7fa;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: #333;
        }


        /* =====================================================
           CONTENEUR
        ===================================================== */

        .commande-container {

            width: 94%;

            max-width: 1200px;

            margin: 30px auto;
        }


        /* =====================================================
           EN-TÊTE
        ===================================================== */

        .page-header {

            background: white;

            padding: 25px;

            border-radius: 15px;

            margin-bottom: 20px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            box-shadow:
                0 5px 20px rgba(0,0,0,.07);
        }


        .header-left {

            display: flex;

            align-items: center;

            gap: 18px;
        }


        .header-icon {

            width: 60px;

            height: 60px;

            border-radius: 15px;

            background: #198754;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;
        }


        .page-header h1 {

            margin: 0;

            font-size: 28px;

            font-weight: 700;
        }


        .page-header p {

            margin: 5px 0 0;

            color: #777;
        }


        /* =====================================================
           CARDS
        ===================================================== */

        .card-section {

            background: white;

            padding: 25px;

            border-radius: 15px;

            margin-bottom: 20px;

            box-shadow:
                0 5px 20px rgba(0,0,0,.06);
        }


        /* =====================================================
           TITRE SECTION
        ===================================================== */

        .section-title {

            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 22px;
        }


        .section-title-icon {

            width: 45px;

            height: 45px;

            border-radius: 10px;

            background: #e8f5ee;

            color: #198754;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;
        }


        .section-title h2 {

            margin: 0;

            font-size: 20px;

            font-weight: 700;
        }


        .section-title p {

            margin: 3px 0 0;

            color: #777;

            font-size: 14px;
        }


        /* =====================================================
           LABELS
        ===================================================== */

        .form-label {

            font-weight: 600;

            color: #444;
        }


        /* =====================================================
           PRODUIT
        ===================================================== */

        .produit-row {

            background: #fafafa;

            border: 1px solid #e3e3e3;

            border-radius: 12px;

            padding: 15px;

            margin-bottom:12px;
            display: grid;

            grid-template-columns:
                40px
                1fr
                120px
                45px;

            align-items: end;

            gap: 15px;
        }


        .produit-number {

            width: 36px;

            height: 36px;

            background: #198754;

            color: white;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: bold;

            margin-bottom: 5px;
        }


        .produit-info {

            margin-top: 6px;

            color: #777;

            font-size: 13px;
        }


        .btn-supprimer {

            width: 40px;

            height: 40px;

            border: none;

            border-radius: 8px;

            background: #dc3545;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            cursor: pointer;
        }


        .btn-supprimer:hover {

            background: #bb2d3b;
        }


        /* =====================================================
           TOTAL
        ===================================================== */

        .total-box {

            background: #f8fff9;

            border: 2px solid #198754;

            border-radius: 15px;

            padding: 20px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;
        }


        .total-label {

            font-size: 17px;

            font-weight: 600;
        }


        #total {

            font-size: 25px;

            font-weight: 700;

            color: #198754;
        }


        /* =====================================================
           ACTIONS
        ===================================================== */

        .actions {

            background: white;

            padding: 20px;

            border-radius: 15px;

            box-shadow:
                0 5px 20px rgba(0,0,0,.06);

            display: flex;

            justify-content: space-between;

            gap: 15px;
        }


        .btn-retour {

            display: inline-flex;

            align-items: center;

            gap: 8px;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media(max-width: 768px) {

            .page-header {

                flex-direction: column;

                align-items: stretch;
            }


            .page-header h1 {

                font-size: 22px;
            }


            .produit-row {

                grid-template-columns: 1fr;

            }


            .produit-number {

                margin-bottom: 0;
            }


            .btn-supprimer {

                width: 100%;
            }


            .total-box {

                flex-direction: column;

                align-items: flex-start;

                gap: 10px;
            }


            .actions {

                flex-direction: column;
            }


            .actions a,
            .actions button {

                width: 100%;
            }
        }

    </style>

</head>


<body>


<div class="commande-container">


    <!-- =====================================================
         EN-TÊTE
    ===================================================== -->

    <div class="page-header">


        <div class="header-left">


            <div class="header-icon">

                <i class="bi bi-cart-plus-fill"></i>

            </div>


            <div>

                <h1>
                    Nouvelle commande
                </h1>

                <p>
                    Enregistrez une nouvelle commande client.
                </p>

            </div>

        </div>


        <a
            href="commandes.php"
            class="btn btn-outline-secondary"
        >

            <i class="bi bi-arrow-left"></i>

            Retour aux commandes

        </a>

    </div>



    <!-- =====================================================
         ERREUR
    ===================================================== -->

    <?php if ($erreur !== ""): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-triangle-fill"></i>

            <strong>
                Erreur
            </strong>

            <br>

            <?= htmlspecialchars($erreur) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         FORMULAIRE
    ===================================================== -->

    <form
        method="POST"
        id="commandeForm"
    >


        <!-- =================================================
             INFORMATIONS CLIENT
        ================================================= -->

        <div class="card-section">


            <div class="section-title">


                <div class="section-title-icon">

                    <i class="bi bi-person-fill"></i>

                </div>


                <div>

                    <h2>
                        Informations du client
                    </h2>

                    <p>
                        Renseignez les informations du client.
                    </p>

                </div>

            </div>



            <div class="row g-3">


                <!-- NOM -->

                <div class="col-md-4">

                    <label
                        for="nom"
                        class="form-label"
                    >
                        Nom
                    </label>

                    <input
                        type="text"
                        name="nom"
                        id="nom"
                        class="form-control"
                        placeholder="Nom du client"
                        value="<?= htmlspecialchars($_POST["nom"] ?? "") ?>"
                        required
                    >

                </div>


                <!-- PRÉNOM -->

                <div class="col-md-4">

                    <label
                        for="prenom"
                        class="form-label"
                    >
                        Prénom
                    </label>

                    <input
                        type="text"
                        name="prenom"
                        id="prenom"
                        class="form-control"
                        placeholder="Prénom du client"
                        value="<?= htmlspecialchars($_POST["prenom"] ?? "") ?>"
                        required
                    >

                </div>


                <!-- TÉLÉPHONE -->

                <div class="col-md-4">

                    <label
                        for="telephone"
                        class="form-label"
                    >
                        Téléphone
                    </label>

                    <input
                        type="text"
                        name="telephone"
                        id="telephone"
                        class="form-control"
                        placeholder="+237 6XXXXXXXX"
                        value="<?= htmlspecialchars($_POST["telephone"] ?? "") ?>"
                        required
                    >

                    <div class="form-text">

                        <i class="bi bi-info-circle"></i>

                        Le téléphone permet de retrouver automatiquement le client.

                    </div>

                </div>

            </div>

        </div>



        <!-- =================================================
             PRODUITS
        ================================================= -->

        <div class="card-section">


            <div class="section-title">


                <div class="section-title-icon">

                    <i class="bi bi-box-seam-fill"></i>

                </div>


                <div>

                    <h2>
                        Produits commandés
                    </h2>

                    <p>
                        Sélectionnez les produits et indiquez les quantités.
                    </p>

                </div>

            </div>



            <div id="produitsContainer">


                <!-- =========================================
                     PRODUIT PRÉ-SÉLECTIONNÉ
                ========================================= -->

                <?php if ($produitSelectionne): ?>

                    <div class="produit-row">


                        <div class="produit-number">
                            1
                        </div>


                        <div>

                            <label class="form-label">
                                Produit
                            </label>

                            <select
                                name="produit[]"
                                class="form-select produit-select"
                                required
                            >

                                <option value="">
                                    Sélectionner un produit
                                </option>


                                <?php foreach (
                                    $produits
                                    as
                                    $produit
                                ): ?>

                                    <option
                                        value="<?= (int) $produit["id_produit"] ?>"
                                        data-prix="<?= (float) $produit["prix"] ?>"
                                        data-stock="<?= (int) $produit["stock"] ?>"
                                        <?= (
                                            (int)$produit["id_produit"]
                                            ===
                                            (int)$produitSelectionne["id_produit"]
                                        )
                                            ? "selected"
                                            : ""
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $produit["nom_produit"]
                                        ) ?>

                                        -
                                        <?= number_format(
                                            $produit["prix"],
                                            0,
                                            ",",
                                            " "
                                        ) ?>

                                        FCFA

                                    </option>

                                <?php endforeach; ?>

                            </select>


                            <div class="produit-info">

                                Stock :

                                <span class="stock-info">

                                    <?= (int) $produitSelectionne["stock"] ?>

                                </span>

                                unité(s)

                            </div>

                        </div>


                        <div>

                            <label class="form-label">
                                Quantité
                            </label>

                            <input
                                type="number"
                                name="quantite[]"
                                class="form-control quantite-input"
                                value="1"
                                min="1"
                                max="<?= (int) $produitSelectionne["stock"] ?>"
                                required
                            >

                        </div>


                        <button
                            type="button"
                            class="btn-supprimer supprimer-produit"
                            title="Supprimer"
                        >

                            <i class="bi bi-trash-fill"></i>

                        </button>

                    </div>


                <?php else: ?>


                    <!-- =====================================
                         PREMIÈRE LIGNE
                    ===================================== -->

                    <div class="produit-row">


                        <div class="produit-number">
                            1
                        </div>


                        <div>

                            <label class="form-label">
                                Produit
                            </label>

                            <select
                                name="produit[]"
                                class="form-select produit-select"
                                required
                            >

                                <option value="">
                                    Sélectionner un produit
                                </option>


                                <?php foreach (
                                    $produits
                                    as
                                    $produit
                                ): ?>

                                    <option
                                        value="<?= (int) $produit["id_produit"] ?>"
                                        data-prix="<?= (float) $produit["prix"] ?>"
                                        data-stock="<?= (int) $produit["stock"] ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $produit["nom_produit"]
                                        ) ?>

                                        -
                                        <?= number_format(
                                            $produit["prix"],
                                            0,
                                            ",",
                                            " "
                                        ) ?>

                                        FCFA

                                    </option>

                                <?php endforeach; ?>

                            </select>


                            <div class="produit-info">

                                Stock :

                                <span class="stock-info">
                                    -
                                </span>

                            </div>

                        </div>


                        <div>

                            <label class="form-label">
                                Quantité
                            </label>

                            <input
                                type="number"
                                name="quantite[]"
                                class="form-control quantite-input"
                                value="1"
                                min="1"
                                required
                            >

                        </div>


                        <button
                            type="button"
                            class="btn-supprimer supprimer-produit"
                            title="Supprimer"
                        >

                            <i class="bi bi-trash-fill"></i>

                        </button>

                    </div>

                <?php endif; ?>


            </div>



            <!-- =================================================
                 AJOUTER PRODUIT
            ================================================= -->

            <button
                type="button"
                class="btn btn-outline-success"
                id="ajouterProduit"
            >

                <i class="bi bi-plus-circle-fill"></i>

                Ajouter un produit

            </button>

        </div>



        <!-- =================================================
             TOTAL
        ================================================= -->

        <div class="total-box">


            <div class="total-label">

                <i class="bi bi-calculator"></i>

                Total de la commande

            </div>


            <div id="total">

                0 FCFA

            </div>

        </div>



        <!-- =================================================
             ACTIONS
        ================================================= -->

        <div class="actions">


            <a
                href="commandes.php"
                class="btn btn-outline-secondary btn-retour"
            >

                <i class="bi bi-arrow-left"></i>

                Annuler

            </a>


            <button
                type="submit"
                class="btn btn-success"
            >

                <i class="bi bi-check-circle-fill"></i>

                Enregistrer la commande

            </button>

        </div>


    </form>


</div>



<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =================================================
           ÉLÉMENTS
        ================================================= */

        const container =
            document.getElementById(
                "produitsContainer"
            );

        const boutonAjouter =
            document.getElementById(
                "ajouterProduit"
            );

        const totalElement =
            document.getElementById(
                "total"
            );


        /* =================================================
           METTRE À JOUR LES NUMÉROS
        ================================================= */

        function mettreAJourNumeros() {

            const lignes =
                container.querySelectorAll(
                    ".produit-row"
                );

            lignes.forEach(
                function (ligne, index) {

                    const numero =
                        ligne.querySelector(
                            ".produit-number"
                        );

                    if (numero) {

                        numero.textContent =
                            index + 1;
                    }
                }
            );
        }


        /* =================================================
           METTRE À JOUR LE STOCK
        ================================================= */

        function mettreAJourStock(ligne) {

            const select =
                ligne.querySelector(
                    ".produit-select"
                );

            const input =
                ligne.querySelector(
                    ".quantite-input"
                );

            const stockInfo =
                ligne.querySelector(
                    ".stock-info"
                );


            if (!select) {
                return;
            }


            const option =
                select.options[
                    select.selectedIndex
                ];


            if (
                option &&
                option.dataset.stock
            ) {

                const stock =
                    parseInt(
                        option.dataset.stock
                    );


                stockInfo.textContent =
                    stock;


                input.max =
                    stock;


                if (
                parseInt(input.value) >
                    stock
                ) {

                    input.value =
                        stock;
                }

            } else {

                stockInfo.textContent =
                    "-";

                input.removeAttribute(
                    "max"
                );
            }


            calculerTotal();
        }


        /* =================================================
           CALCULER TOTAL
        ================================================= */

        function calculerTotal() {

            let total = 0;


            const lignes =
                container.querySelectorAll(
                    ".produit-row"
                );


            lignes.forEach(
                function (ligne) {

                    const select =
                        ligne.querySelector(
                            ".produit-select"
                        );

                    const quantite =
                        ligne.querySelector(
                            ".quantite-input"
                        );


                    if (
                        !select ||
                        !quantite
                    ) {

                        return;
                    }


                    const option =
                        select.options[
                            select.selectedIndex
                        ];


                    if (!option) {
                        return;
                    }


                    const prix =
                        parseFloat(
                            option.dataset.prix
                            || 0
                        );


                    const qte =
                        parseInt(
                            quantite.value
                            || 0
                        );


                    total +=
                        prix * qte;
                }
            );


            totalElement.textContent =
                new Intl.NumberFormat(
                    "fr-FR"
                ).format(total)
                + " FCFA";
        }


        /* =================================================
           ÉCOUTER LES CHANGEMENTS
        ================================================= */

        function initialiserLigne(ligne) {

            const select =
                ligne.querySelector(
                    ".produit-select"
                );

            const quantite =
                ligne.querySelector(
                    ".quantite-input"
                );


            if (select) {

                select.addEventListener(
                    "change",
                    function () {

                        mettreAJourStock(
                            ligne
                        );
                    }
                );
            }


            if (quantite) {

                quantite.addEventListener(
                    "input",
                    function () {

                        calculerTotal();
                    }
                );
            }


            mettreAJourStock(
                ligne
            );
        }


        /* =================================================
           AJOUTER UNE LIGNE
        ================================================= */

        boutonAjouter.addEventListener(
            "click",
            function () {


                const ligne =
                    document.createElement(
                        "div"
                    );


                ligne.className =
                    "produit-row";


                ligne.innerHTML = `

                    <div class="produit-number">
                        1
                    </div>


                    <div>

                        <label class="form-label">
                            Produit
                        </label>

                        <select
                            name="produit[]"
                            class="form-select produit-select"
                            required
                        >

                            <option value="">
                                Sélectionner un produit
                            </option>

                            <?php foreach (
                                $produits
                                as
                                $produit
                            ): ?>

                                <option
                                    value="<?= (int) $produit["id_produit"] ?>"
                                    data-prix="<?= (float) $produit["prix"] ?>"
                                    data-stock="<?= (int) $produit["stock"] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $produit["nom_produit"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                    -
                                    <?= number_format(
                                        $produit["prix"],
                                        0,
                                        ",",
                                        " "
                                    ) ?>

                                    FCFA

                                </option>

                            <?php endforeach; ?>

                        </select>


                        <div class="produit-info">

                            Stock :

                            <span class="stock-info">
                                -
                            </span>

                        </div>

                    </div>


                    <div>

                        <label class="form-label">
                            Quantité
                        </label>

                        <input
                            type="number"
                            name="quantite[]"
                            class="form-control quantite-input"
                            value="1"
                            min="1"
                            required
                        >

                    </div>


                    <button
                        type="button"
                        class="btn-supprimer supprimer-produit"
                        title="Supprimer"
                    >

                        <i class="bi bi-trash-fill"></i>

                    </button>

                `;


                container.appendChild(
                    ligne
                );


                initialiserLigne(
                    ligne
                );


                mettreAJourNumeros();


                calculerTotal();
            }
        );


        /* =================================================
           SUPPRIMER UNE LIGNE
        ================================================= */

        container.addEventListener(
            "click",
            function (event) {


                const bouton =
                    event.target.closest(
                        ".supprimer-produit"
                    );


                if (!bouton) {
                    return;
                }


                const lignes =
                    container.querySelectorAll(
                        ".produit-row"
                    );


                /*
                 * Empêcher de supprimer
                 * la dernière ligne.
                 */

                if (lignes.length <= 1) {

                    alert(
                        "Une commande doit contenir au moins un produit."
                    );

                    return;
                }


                const ligne =
                    bouton.closest(
                        ".produit-row"
                    );


                if (ligne) {

                    ligne.remove();
                }


                mettreAJourNumeros();

                calculerTotal();
            }
        );


        /* =================================================
           INITIALISER LES LIGNES EXISTANTES
        ================================================= */

        const lignes =
            container.querySelectorAll(
                ".produit-row"
            );


        lignes.forEach(
            function (ligne) {

                initialiserLigne(
                    ligne
                );
            }
        );


        mettreAJourNumeros();

        calculerTotal();


        /* =================================================
           VALIDATION AVANT ENVOI
        ================================================= */

        document
            .getElementById(
                "commandeForm"
            )
            .addEventListener(
                "submit",
                function (event) {


                    const lignes =
                        container.querySelectorAll(
                            ".produit-row"
                        );


                    if (
                        lignes.length === 0
                    ) {

                        event.preventDefault();

                        alert(
                            "Veuillez ajouter au moins un produit."
                        );

                        return;
                    }


                    let valide =
                        true;


                    lignes.forEach(
                        function (ligne) {

                            const select =
                                ligne.querySelector(
                                    ".produit-select"
                                );

                            const quantite =
                                ligne.querySelector(
                                    ".quantite-input"
                                );


                            if (
                                !select.value ||
                                parseInt(
                                    quantite.value
                                ) <= 0
                            ) {

                                valide =
                                    false;
                            }


                            const option =
                                select.options[
                                    select.selectedIndex
                                ];


                            if (
                                option &&
                                option.dataset.stock
                            ) {

                                const stock =
                                    parseInt(
                                        option.dataset.stock
                                    );


                                const qte =
                                    parseInt(
                                        quantite.value
                                    );


                                if (
                                    qte > stock
                                ) {

                                    valide =
                                        false;

                                    alert(
                                        "La quantité demandée dépasse le stock disponible."
                                    );
                                }
                            }
                        }
                    );


                    if (!valide) {

                        event.preventDefault();

                        alert(
                            "Veuillez vérifier les produits et les quantités."
                        );
                    }

                }
            );

    }
);

</script>


<script
    src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>