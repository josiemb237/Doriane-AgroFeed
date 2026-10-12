<?php

session_start();

require_once "connexions.php";


/* =========================================================
   RÉCUPÉRER L'ID DE LA VENTE
========================================================= */

$idVente = isset($_GET["id_vente"])
    ? (int) $_GET["id_vente"]
    : 0;


/* =========================================================
   VÉRIFICATION
========================================================= */

if ($idVente <= 0) {

    die("Facture introuvable.");

}


/* =========================================================
   INITIALISATION
========================================================= */

$facture = null;

$produits = [];

$messageErreur = "";


/* =========================================================
   RÉCUPÉRER LES INFORMATIONS DE LA VENTE
========================================================= */

try {

    $sql = "

        SELECT

            v.id_vente,

            v.id_commande,

            v.montant,

            v.date_vente,


            c.id_utilisateur,

            c.date_commande,


            u.nom,

            u.prenom,

            u.telephone


        FROM vente v


        LEFT JOIN commande c

            ON v.id_commande = c.id_commande


        LEFT JOIN utilisateurs u

            ON c.id_utilisateur = u.id_utilisateur


        WHERE v.id_vente = ?

        LIMIT 1

    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute([$idVente]);

    $facture = $stmt->fetch(PDO::FETCH_ASSOC);


    /* =====================================================
       SI LA VENTE N'EXISTE PAS
    ===================================================== */

    if (!$facture) {

        die("Facture introuvable.");

    }


} catch (PDOException $e) {

    die(
        "Erreur lors du chargement de la facture : "
        . htmlspecialchars($e->getMessage())
    );

}


/* =========================================================
   RÉCUPÉRER LES PRODUITS DE LA COMMANDE
========================================================= */

try {

    $sqlProduits = "

        SELECT

            lc.quantite,

            lc.prix_unitaire,


            p.nom_produit


        FROM ligne_commande lc


        LEFT JOIN produit p

            ON lc.id_produit = p.id_produit


        WHERE lc.id_commande = ?

    ";


    $stmtProduits = $pdo->prepare($sqlProduits);

    $stmtProduits->execute([
        $facture["id_commande"]
    ]);

    $produits = $stmtProduits->fetchAll(
        PDO::FETCH_ASSOC
    );


} catch (PDOException $e) {

    /*
        On ne bloque pas complètement la facture
        si la structure des lignes de commande
        est différente.
    */

    $messageErreur =
        "Impossible de charger les produits : "
        . $e->getMessage();

}


/* =========================================================
   NOM COMPLET DU CLIENT
========================================================= */

$nomClient = trim(

    ($facture["nom"] ?? "")
    . " "
    . ($facture["prenom"] ?? "")

);


if ($nomClient === "") {

    $nomClient = "Client non renseigné";

}


/* =========================================================
   TOTAL DES PRODUITS
========================================================= */

$totalProduits = 0;


foreach ($produits as $produit) {

    $quantite =
        (float) ($produit["quantite"] ?? 0);

    $prix =
        (float) ($produit["prix_unitaire"] ?? 0);


    $totalProduits +=
        $quantite * $prix;

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

        Facture #<?= (int) $facture["id_vente"] ?>

        - DORIANE AGROFEED

    </title>


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        * {

            box-sizing: border-box;

        }


        body {

            margin: 0;

            padding: 30px;

            background: #f1f3f5;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: #222;

        }


        .facture {

            width: 100%;

            max-width: 900px;

            margin: auto;

            background: white;

            padding: 45px;

            box-shadow:
                0 5px 25px
                rgba(0, 0, 0, 0.1);

        }


        /* EN-TÊTE */

        .header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            border-bottom:
                3px solid #145a32;

            padding-bottom: 25px;

            margin-bottom: 30px;

        }


        .entreprise h1 {

            color: #145a32;

            margin: 0 0 10px;

            font-size: 26px;

        }


        .entreprise p {

            margin: 5px 0;

            color: #555;

        }


        .titre-facture {

            text-align: right;

        }


        .titre-facture h2 {

            margin: 0;

            color: #333;

            font-size: 30px;

        }


        .titre-facture p {

            color: #666;

        }


        /* CLIENT */

        .info-container {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 25px;

            margin-bottom: 30px;

        }


        .info-box {

            background: #f8f9fa;

            padding: 20px;

            border-radius: 8px;

        }


        .info-box h3 {

            margin-top: 0;

            color: #145a32;

            font-size: 17px;

        }


        .info-box p {

            margin: 8px 0;

        }


        /* TABLEAU */

        table {

            width: 100%;

            border-collapse: collapse;

            margin-top: 25px;

        }


        th {

            background: #145a32;

            color: white;

            padding: 13px;

            text-align: left;

        }


        td {

            padding: 13px;

            border-bottom:
                1px solid #ddd;

        }


        .text-center {

            text-align: center;

        }


        .text-right {

            text-align: right;

        }


        /* TOTAL */

        .total-container {

            display: flex;

            justify-content: flex-end;

            margin-top: 30px;

        }


        .total-box {

            width: 350px;

        }


        .total-ligne {

            display: flex;

            justify-content: space-between;

            padding: 12px 0;

        }


        .total-final {

            border-top:
                2px solid #145a32;

            font-size: 22px;

            font-weight: bold;

            color: #145a32;

        }


        /* BOUTONS */

        .actions {

            display: flex;

            justify-content: center;

            gap: 15px;

            margin-top: 40px;

        }


        .btn {

            border: none;

            padding: 12px 22px;

            border-radius: 6px;

            cursor: pointer;

            text-decoration: none;

            font-size: 15px;

        }


        .btn-imprimer {

            background: #145a32;

            color: white;

        }


        .btn-retour {

            background: #6c757d;

            color: white;

        }


        /* PIED */

        .footer {

            text-align: center;

            margin-top: 50px;

            padding-top: 20px;

            border-top:
                1px solid #ddd;

            color: #777;

            font-size: 13px;

        }


        /* IMPRESSION */

        @media print {

            body {

                background: white;

                padding: 0;

            }


            .facture {

                box-shadow: none;

                width: 100%;

                max-width: none;

            }


            .actions {

                display: none;

            }

        }


        @media (max-width: 700px) {

            body {

                padding: 10px;

            }


            .facture {

                padding: 20px;

            }


            .header {

                flex-direction: column;

                gap: 20px;

            }


            .titre-facture {

                text-align: left;

            }


            .info-container {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>


<body>


<div class="facture">


    <!-- =================================================
         EN-TÊTE
    ================================================= -->

    <div class="header">


        <div class="entreprise">

            <h1>

                DORIANE AGROFEED

            </h1>


            <p>

                Votre provenderie de confiance

            </p>


            <p>

                Bafoussam - Cameroun

            </p>


            <p>

                Marché B, première rue

            </p>

        </div>


        <div class="titre-facture">

            <h2>

                FACTURE

            </h2>


            <p>

                <strong>

                    N° Vente :

                </strong>

                #<?= (int) $facture["id_vente"] ?>

            </p>


            <p>

                <strong>

                    N° Commande :

                </strong>

                #<?= (int) $facture["id_commande"] ?>

            </p>


            <p>

                <strong>

                    Date :

                </strong>


                <?php

                if (!empty($facture["date_vente"])) {

                    echo date(
                        "d/m/Y H:i",
                        strtotime(
                            $facture["date_vente"]
                        )
                    );

                } else {

                    echo "—";

                }

                ?>

            </p>

        </div>


    </div>


    <!-- =================================================
         INFORMATIONS
    ================================================= -->

    <div class="info-container">


        <!-- CLIENT -->

        <div class="info-box">

            <h3>

                Informations du client

            </h3>


            <p>

                <strong>

                    Nom :

                </strong>

                <?= htmlspecialchars($nomClient) ?>

            </p>


            <p>

                <strong>

                    Téléphone :

                </strong>

                <?= htmlspecialchars(
                    $facture["telephone"]
                    ?? "Non renseigné"
                ) ?>

            </p>

        </div>


        <!-- COMMANDE -->

        <div class="info-box">

            <h3>

                Informations de la commande

            </h3>


            <p>

                <strong>

                    N° Commande :

                </strong>

                #<?= (int) $facture["id_commande"] ?>

            </p>


            <p>

                <strong>

                    N° Vente :

                </strong>

                #<?= (int) $facture["id_vente"] ?>

            </p>

        </div>


    </div>


    <!-- =================================================
         PRODUITS
    ================================================= -->

    <h3>

        Détails de la commande

    </h3>


    <?php if ($messageErreur !== ""): ?>


        <div style="
            background:#fff3cd;
            padding:15px;
            margin-bottom:20px;
        ">

            <?= htmlspecialchars($messageErreur) ?>

        </div>


    <?php endif; ?>


    <table>


        <thead>

            <tr>

                <th>Produit</th>

                <th class="text-center">

                    Quantité

                </th>

                <th class="text-right">

                    Prix unitaire

                </th>

                <th class="text-right">

                    Total

                </th>

            </tr>

        </thead>


        <tbody>


            <?php if (!empty($produits)): ?>


                <?php foreach ($produits as $produit): ?>


                    <?php

                    $quantite =
                        (float) ($produit["quantite"] ?? 0);

                    $prix =
                        (float) ($produit["prix_unitaire"] ?? 0);

                    $total =
                        $quantite * $prix;

                    ?>


                    <tr>


                        <td>

                            <?= htmlspecialchars(
                                $produit["nom_produit"]
                                ?? "Produit inconnu"
                            ) ?>

                        </td>


                        <td class="text-center">

                            <?= $quantite ?>

                        </td>


                        <td class="text-right">

                            <?= number_format(
                                $prix,
                                0,
                                ",",
                                " "
                            ) ?>

                            FCFA

                        </td>


                        <td class="text-right">

                            <?= number_format(
                                $total,
                                0,
                                ",",
                                " "
                            ) ?>

                            FCFA

                        </td>


                    </tr>


                <?php endforeach; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="4"
                        class="text-center"
                    >

                        Aucun produit trouvé pour cette commande.

                    </td>

                </tr>


            <?php endif; ?>


        </tbody>


    </table>


    <!-- =================================================
         TOTAL
    ================================================= -->

    <div class="total-container">


        <div class="total-box">


            <div class="total-ligne">

                <span>

                    Total de la vente

                </span>


                <strong>

                    <?= number_format(
                        (float) $facture["montant"],
                        0,
                        ",",
                        " "
                    ) ?>

                    FCFA

                </strong>

            </div>


            <div
                class="
                    total-ligne
                    total-final
                "
            >

                <span>

                    TOTAL À PAYER

                </span>


                <span>

                    <?= number_format(
                        (float) $facture["montant"],
                        0,
                        ",",
                        " "
                    ) ?>

                    FCFA

                </span>

            </div>


        </div>


    </div>


    <!-- =================================================
         BOUTONS
    ================================================= -->

    <div class="actions">


        <a
            href="vente.php"
            class="btn btn-retour"
        >

            <i class="bi bi-arrow-left"></i>

            Retour

        </a>


        <button
            type="button"
            class="btn btn-imprimer"
            onclick="window.print()"
        >

            <i class="bi bi-printer"></i>

            Imprimer la facture

        </button>


    </div>


    <!-- =================================================
         PIED DE PAGE
    ================================================= -->

    <div class="footer">

        Merci pour votre confiance.

        <br>

        DORIANE AGROFEED

    </div>


</div>


</body>

</html>