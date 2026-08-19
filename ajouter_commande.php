<?php

require_once "connexions.php";

/* =========================================================
   VARIABLES
========================================================= */

$erreur = "";
$succes = "";
$idCommandeSucces = null;


/* =========================================================
   MESSAGE APRÈS ENREGISTREMENT
========================================================= */

if (isset($_GET["success"]) && $_GET["success"] == "1") {

    $succes = "Commande enregistrée avec succès !";

    if (isset($_GET["id_commande"])) {
        $idCommandeSucces = intval($_GET["id_commande"]);
    }
}


/* =========================================================
   PRODUIT PRÉ-SÉLECTIONNÉ
========================================================= */

$produitSelectionne = null;

if (isset($_GET["produit"])) {

    $idProduit = intval($_GET["produit"]);

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
   ENREGISTREMENT DE LA COMMANDE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom =
        trim($_POST["nom"] ?? "");

    $prenom =
        trim($_POST["prenom"] ?? "");

    $telephone =
        trim($_POST["telephone"] ?? "");

    $produitsCommande =
        $_POST["produit"] ?? [];

    $quantites =
        $_POST["quantite"] ?? [];


    /* =====================================================
       VÉRIFICATION DES INFORMATIONS
    ===================================================== */

    if (
        $nom === "" ||
        $prenom === "" ||
        $telephone === ""
    ) {

        $erreur =
            "Veuillez remplir tous les champs du client.";

    } else {


        /* =================================================
           CONSTRUIRE LES LIGNES DE COMMANDE
        ================================================= */

        $lignesCommande = [];

        foreach (
            $produitsCommande
            as $index => $idProduit
        ) {

            $idProduit =
                intval($idProduit);

            $quantite =
                intval(
                    $quantites[$index] ?? 0
                );


            if (
                $idProduit > 0 &&
                $quantite > 0
            ) {

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


        /* =================================================
           AUCUN PRODUIT
        ================================================= */

        if (empty($lignesCommande)) {

            $erreur =
                "Veuillez sélectionner au moins un produit avec une quantité valide.";

        } else {


            try {

                /* =============================================
                   DÉBUT TRANSACTION
                ============================================= */

                $pdo->beginTransaction();


                /* =============================================
                   RECHERCHER LE CLIENT
                ============================================= */

                $stmtClient = $pdo->prepare("
                    SELECT id_utilisateur
                    FROM utilisateurs
                    WHERE telephone = ?
                    LIMIT 1
                ");

                $stmtClient->execute([
                    $telephone
                ]);

                $client =
                    $stmtClient->fetch(
                        PDO::FETCH_ASSOC
                    );


                /* =============================================
                   CLIENT EXISTANT
                ============================================= */

                if ($client) {

                    $idUtilisateur =
                        $client["id_utilisateur"];


                    /*
                     * Mise à jour du client
                     */

                    $stmtUpdate = $pdo->prepare("
                        UPDATE utilisateurs
                        SET
                            nom = ?,
                            prenom = ?,
                            telephone = ?
                        WHERE id_utilisateur = ?
                    ");

                    $stmtUpdate->execute([
                        $nom,
                        $prenom,
                        $telephone,
                        $idUtilisateur
                    ]);

                }


                /* =============================================
                   NOUVEAU CLIENT
                ============================================= */

                else {

                    $stmtInsert = $pdo->prepare("
                        INSERT INTO utilisateurs
                        (
                            nom,
                            prenom,
                            telephone
                        )
                        VALUES (?, ?, ?)
                    ");

                    $stmtInsert->execute([
                        $nom,
                        $prenom,
                        $telephone
                    ]);


                    $idUtilisateur =
                        $pdo->lastInsertId();
                }


                /* =============================================
                   CRÉER LA COMMANDE
                ============================================= */

                $stmtCommande = $pdo->prepare("
                    INSERT INTO commande
                    (
                        id_utilisateur,
                        statut
                    )
                    VALUES (?, ?)
                ");

                $stmtCommande->execute([
                    $idUtilisateur,
                    "En attente"
                ]);


                $idCommande =
                    $pdo->lastInsertId();


                /* =============================================
                   REQUÊTE PRODUIT
                ============================================= */

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


                /* =============================================
                   REQUÊTE LIGNE COMMANDE
                ============================================= */

                $stmtLigne = $pdo->prepare("
                    INSERT INTO ligne_commande
                    (
                        id_commande,
                        id_produit,
                        quantite,
                        prix_unitaire,
                        sous_total
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");


                /* =============================================
                   REQUÊTE STOCK
                ============================================= */

                $stmtStock = $pdo->prepare("
                    UPDATE produit
                    SET stock = stock - ?
                    WHERE id_produit = ?
                ");


                /* =============================================
                   ENREGISTRER LES PRODUITS
                ============================================= */

                foreach (
                    $lignesCommande
                    as $idProduit => $quantite
                ) {


                    /* Récupérer le produit */

                    $stmtProduit->execute([
                        $idProduit
                    ]);


                    $produit =
                        $stmtProduit->fetch(
                            PDO::FETCH_ASSOC
                        );


                    /* Produit inexistant */

                    if (!$produit) {

                        throw new Exception(
                            "Le produit sélectionné est introuvable."
                        );
                    }


                    /* =========================================
                       VÉRIFIER LE STOCK
                    ========================================= */

                    $stock =
                        intval($produit["stock"]);


                    if ($quantite > $stock) {

                        throw new Exception(
                            "Stock insuffisant pour le produit : "
                            . $produit["nom_produit"]
                            . ". Stock disponible : "
                            . $stock
                        );
                    }


                    /* =========================================
                       CALCUL SOUS-TOTAL
                    ========================================= */

                    $prix =
                        floatval($produit["prix"]);


                    $sousTotal =
                        $prix * $quantite;


                    /* =========================================
                       ENREGISTRER LIGNE
                    ========================================= */

                    $stmtLigne->execute([
                        $idCommande,
                        $idProduit,
                        $quantite,
                        $prix,
                        $sousTotal
                    ]);


                    /* =========================================
                       DIMINUER LE STOCK
                    ========================================= */

                    $stmtStock->execute([
                        $quantite,
                        $idProduit
                    ]);
                }


                /* =============================================
                   VALIDER
                ============================================= */

                $pdo->commit();


                /* =============================================
                   REDIRECTION SUR LA MÊME PAGE
                ============================================= */

                header(
                    "Location: ajouter_commande.php?success=1&id_commande="
                    . $idCommande
                );

                exit;


            } catch (Throwable $e) {


                /* Annuler la transaction */

                if ($pdo->inTransaction()) {

                    $pdo->rollBack();
                }


                /* Afficher l'erreur */

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

<title>Ajouter une commande</title>


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


<!-- CSS de la page -->

<link
    rel="stylesheet"
    href="ajouter_commande.css"
>


<style>

/* =========================================================
   PAGE
========================================================= */

body {

    background: #f5f7fa;

}


/* =========================================================
   CONTENEUR
========================================================= */

.commande-container {

    width: 94%;

    max-width: 1200px;

    margin: 30px auto;

}


/* =========================================================
   EN-TÊTE
========================================================= */

.commande-header {

    background: white;

    padding: 25px;

    border-radius: 15px;

    display: flex;

    align-items: center;

    gap: 20px;

    margin-bottom: 20px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.07);

}


.header-icon {

    width: 60px;

    height: 60px;

    background: #198754;

    color: white;

    border-radius: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 30px;

}


.commande-header h1 {

    margin: 0;

    font-size: 28px;

    font-weight: 700;

}


.commande-header p {

    margin: 5px 0 0;

    color: #777;

}


/* =========================================================
   CARDS
========================================================= */

.card-section {

    background: white;

    border-radius: 15px;

    padding: 25px;

    margin-bottom: 20px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.06);

}


/* =========================================================
   TITRES
========================================================= */

.section-heading {

    display: flex;

    align-items: center;

    gap: 15px;

    margin-bottom: 25px;

}


.section-icon {

    width: 45px;

    height: 45px;

    border-radius: 10px;

    background: #e8f5ee;

    color: #198754;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;

}


.section-heading h2 {

    margin: 0;

    font-size: 20px;

    font-weight: 700;

}


.section-heading p {

    margin: 3px 0 0;

    color: #777;

    font-size: 14px;

}


/* =========================================================
   PRODUITS HEADER
========================================================= */

.products-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    margin-bottom: 20px;

}


/* =========================================================
   LIGNE PRODUIT
========================================================= */

.produit-row {

    display: flex;

    align-items: flex-end;

    gap: 15px;

    padding: 15px;

    border: 1px solid #e4e4e4;

    border-radius: 12px;

    background: #fafafa;

    margin-bottom: 12px;

}


.produit-number {

    width: 38px;

    height: 38px;

    flex-shrink: 0;

    border-radius: 50%;

    background: #198754;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;

}


.produit-field {

    min-width: 220px;

}


.produit-details {

    min-width: 250px;

}


.produit-details h2 {

    font-size: 14px;

    line-height: 1.5;

    margin: 0;

    color: #34495e;

}


.quantite-field {

    min-width: 100px;

}


.action-field {

    min-width: 45px;

}


/* =========================================================
   TÉLÉPHONE
========================================================= */

.telephone-info {

    font-size: 12px;

    color: #6c757d;

    margin-top: 6px;

}


/* =========================================================
   TOTAL
========================================================= */

.total-box {

    background: white;

    border: 2px solid #198754;

    border-radius: 15px;

    padding: 20px;

    margin-bottom: 20px;

}


#total {

    font-size: 25px;

}


/* =========================================================
   BOUTONS
========================================================= */

.actions {

    background: white;

    padding: 20px;

    border-radius: 15px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.06);

}


.btn-retour {

    display: inline-flex;

    align-items: center;

    gap: 7px;

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:768px) {

    .commande-container {

        width: 94%;

        margin: 15px auto;

    }


    .commande-header {

        padding: 18px;

    }


    .commande-header h1 {

        font-size: 22px;

    }


    .products-header {

        flex-direction: column;

        align-items: stretch;

    }


    .produit-row {

        flex-direction: column;

        align-items: stretch;

    }


    .produit-number {

        align-self: flex-start;

    }


    .produit-field,
    .produit-details,
    .quantite-field,
    .action-field {

        width: 100%;

        min-width: 100%;

    }


    .actions {

        flex-direction: column;

        gap: 10px;

    }


    .actions a,
    .actions button {

        width: 100%;

    }

}


/* =========================================================
   IMPRESSION
========================================================= */

@media print {

    .no-print {

        display: none !important;

    }

}

</style>

</head>


<body>


<div class="commande-container">


<!-- =====================================================
     EN-TÊTE
===================================================== -->

<div class="commande-header">


    <div class="header-icon">

        <i class="bi bi-cart-plus"></i>

    </div>


    <div>

        <h1>
            Nouvelle commande
        </h1>

        <p>
            Enregistrez les informations du client
            et les produits commandés.
        </p>

    </div>

</div>



<!-- =====================================================
     MESSAGE DE SUCCÈS
===================================================== -->

<?php if ($succes !== ""): ?>

<div class="alert alert-success alert-dismissible fade show">


    <i class="bi bi-check-circle-fill"></i>

    <strong>
        <?= htmlspecialchars($succes) ?>
    </strong>


    <?php if ($idCommandeSucces): ?>

        <br>

        Votre numéro de commande est :

        <strong>
            #<?= $idCommandeSucces ?>
        </strong>

    <?php endif; ?>


    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert">
    </button>

</div>

<?php endif; ?>



<!-- =====================================================
     MESSAGE D'ERREUR
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
        data-bs-dismiss="alert">
    </button>

</div>

<?php endif; ?>



<!-- =====================================================
     FORMULAIRE
===================================================== -->

<form
    method="POST"
    id="commandeForm"
>


<!-- =====================================================
     INFORMATIONS CLIENT
===================================================== -->

<div class="card-section">


    <div class="section-heading">


        <div class="section-icon">

            <i class="bi bi-person-fill"></i>

        </div>


        <div>

            <h2>
                Informations du client
            </h2>
            <p>
                Entrez vos coordonnées.
            </p>

        </div>

    </div>


    <div class="row">


        <div class="col-md-4 mb-3">

            <label class="form-label">
                Nom <span>*</span>
            </label>

            <input
                type="text"
                name="nom"
                class="form-control"
                placeholder="Votre nom"
                required
            >

        </div>


        <div class="col-md-4 mb-3">

            <label class="form-label">
                Prénom <span>*</span>
            </label>

            <input
                type="text"
                name="prenom"
                class="form-control"
                placeholder="Votre prénom"
                required
            >

        </div>


        <div class="col-md-4 mb-3">

            <label class="form-label">

                Numéro de téléphone <span>*</span>

            </label>

            <input
                type="tel"
                name="telephone"
                class="form-control"
                placeholder="Ex : 6XXXXXXXX"
                required
            >

            <div class="telephone-info">

                <i class="bi bi-info-circle"></i>

                Vous pouvez saisir votre nouveau numéro.

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     PRODUITS
===================================================== -->

<div class="card-section">


    <div class="products-header">

        <div class="section-heading">

            <div class="section-icon">

                <i class="bi bi-box-seam-fill"></i>

            </div>

            <div>

                <h2>Produits commandés</h2>

                <p>
                    Ajoutez un ou plusieurs produits.
                </p>

            </div>

        </div>


        <button
            type="button"
            class="btn btn-success"
            onclick="ajouterProduit()">

            <i class="bi bi-plus-circle"></i>

            Ajouter un produit

        </button>

    </div>


    <div id="produits-container">


        <div class="produit-row mb-3">


            <div class="produit-number">
                1
            </div>


            <div
                class="produit-field"
                style="flex:2;">

                <label>
                    Produit
                </label>

                <select
                    name="produit[]"
                    class="form-select produit-select"
                    onchange="mettreAJourDetails(this)"
                    required>

                    <option value="">
                        -- Choisir un produit --
                    </option>

                    <?php foreach ($produits as $p): ?>

                    <option
                        value="<?= (int)$p["id_produit"] ?>"
                        data-nom="<?= htmlspecialchars($p["nom_produit"]) ?>"
                        data-details="<?= htmlspecialchars($p["description"] ?? "Aucun détail") ?>"
                        data-prix="<?= htmlspecialchars($p["prix"]) ?>"
                        <?= (
                            $produitSelectionne &&
                            $produitSelectionne["id_produit"]
                            == $p["id_produit"]
                        ) ? "selected" : "" ?>
                    >

                        <?= htmlspecialchars($p["nom_produit"]) ?>

                    </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div
                class="produit-details"
                style="flex:2;">

                <label>&nbsp;</label>

                <h2>
                    <em>
                        Sélectionnez un produit
                    </em>
                </h2>

            </div>


            <div
                class="quantite-field"
                style="flex:1;">

                <label>
                    Quantité
                </label>

                <input
                    type="number"
                    name="quantite[]"
                    class="form-control quantite-input"
                    min="1"
                    value="1"
                    required
                    oninput="calculerTotal()"
                >

            </div>


            <div class="action-field">

                <label>&nbsp;</label>

                <button
                    type="button"
                    class="btn btn-danger"
                    onclick="supprimerProduit(this)">

                    <i class="bi bi-trash"></i>

                </button>

            </div>


        </div>

    </div>


    <button
        type="button"
        class="btn btn-outline-success w-100 mt-2"
        onclick="ajouterProduit()">

        <i class="bi bi-plus-circle"></i>

        Ajouter un autre produit

    </button>

</div>


<!-- =====================================================
     TOTAL
===================================================== -->

<div
    class="total-box d-flex justify-content-between align-items-center p-3 bg-light rounded mb-3">

    <div>

        <small class="text-muted">
            TOTAL DE LA COMMANDE
        </small>

        <div class="fw-bold">
            Montant total
        </div>

    </div>


    <strong
        id="total"
        class="fs-4 text-success">

        0 FCFA

    </strong>

</div>


<!-- =====================================================
     BOUTONS
===================================================== -->

<div class="actions d-flex justify-content-between">


    <!-- RETOUR PRODUITS -->

    <a
        href="produit.php"
        class="btn btn-light">

        <i class="bi bi-arrow-left"></i>

        Annuler

    </a>


    <!-- ENREGISTRER -->

    <button
        type="submit"
        class="btn btn-success">

        <i class="bi bi-check-circle-fill"></i>

        Enregistrer la commande

    </button>

</div>


</form>


</div>


<script
src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js">
</script>


<script>

/* =========================================================
   DÉTAILS PRODUIT
========================================================= */

function mettreAJourDetails(select) {

    const row =
        select.closest(".produit-row");

    const details =
        row.querySelector(".produit-details");

    const option =
        select.options[select.selectedIndex];


    if (option && option.value) {

        const nom =
            option.dataset.nom;

        const description =
            option.dataset.details;

        const prix =
            parseFloat(option.dataset.prix) || 0;


        details.innerHTML = `

            <label>&nbsp;</label>

            <h2>

                <strong>${nom}</strong>

                <br>

                ${description}

                <br>

                <span class="text-success">

                    ${prix.toLocaleString("fr-FR")}
                    FCFA

                </span>

            </h2>

        `;

    } else {

        details.innerHTML = `

            <label>&nbsp;</label>

            <h2>

                <em>
                    Sélectionnez un produit
                </em>

            </h2>

        `;
    }


    calculerTotal();
}


/* =========================================================
   AJOUTER PRODUIT
========================================================= */

function ajouterProduit() {

    const container =
        document.getElementById("produits-container");

    const premiereLigne =
        document.querySelector(".produit-row");

    const nouvelleLigne =
        premiereLigne.cloneNode(true);


    nouvelleLigne
        .querySelector(".produit-select")
        .value = "";


    nouvelleLigne
        .querySelector(".quantite-input")
        .value = 1;


    nouvelleLigne
        .querySelector(".produit-details")
        .innerHTML = `

            <label>&nbsp;</label>

            <h2>

                <em>
                    Sélectionnez un produit
                </em>

            </h2>

        `;


    container.appendChild(nouvelleLigne);

    numeroterLignes();

    calculerTotal();
}


/* =========================================================
   SUPPRIMER PRODUIT
========================================================= */

function supprimerProduit(button) {

    const lignes =
        document.querySelectorAll(".produit-row");


    if (lignes.length === 1) {

        alert(
            "Une commande doit contenir au moins un produit."
        );

        return;
    }


    button
        .closest(".produit-row")
        .remove();


    numeroterLignes();

    calculerTotal();
}


/* =========================================================
   NUMÉROTATION
========================================================= */

function numeroterLignes() {

    document
        .querySelectorAll(".produit-row")
        .forEach((ligne, index) => {

            ligne
                .querySelector(".produit-number")
                .textContent = index + 1;

        });
}


/* =========================================================
   TOTAL
========================================================= */

function calculerTotal() {

    let total = 0;


    document
        .querySelectorAll(".produit-row")
        .forEach(ligne => {

            const select =
                ligne.querySelector(".produit-select");

            const quantite =
                ligne.querySelector(".quantite-input");


            const option =
                select.options[select.selectedIndex];


            if (
                option &&
                option.value &&
                option.dataset.prix
            ) {

                const prix =
                    parseFloat(option.dataset.prix) || 0;

                const qte =
                    parseInt(quantite.value) || 0;


                total += prix * qte;
            }

        });


    document.getElementById("total").textContent =
        total.toLocaleString("fr-FR") + " FCFA";
}


/* =========================================================
   INITIALISATION
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function() {

        numeroterLignes();

        calculerTotal();


        const select =
            document.querySelector(".produit-select");


        if (select && select.value) {

            mettreAJourDetails(select);
        }

    }
);

</script>

</body>
</html>