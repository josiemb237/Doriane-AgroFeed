<?php

require_once "connexions.php";

/* =====================================================
   RÉCUPÉRER L'ID DE LA VENTE
===================================================== */

$idVente = isset($_GET["id"])
    ? intval($_GET["id"])
    : 0;

if ($idVente <= 0) {
    die("Facture introuvable.");
}


/* =====================================================
   RÉCUPÉRER LA VENTE + CLIENT
===================================================== */

$sql = "

    SELECT
        v.id_vente,
        v.id_commande,
        v.montant,
        v.date_vente,

        c.statut,

        u.id_utilisateur,
        u.nom,
        u.prenom,
        u.telephone,
        u.email

    FROM vente v

    INNER JOIN commande c
        ON v.id_commande = c.id_commande

    INNER JOIN utilisateurs u
        ON c.id_utilisateur = u.id_utilisateur

    WHERE v.id_vente = ?

";


$stmt = $pdo->prepare($sql);

$stmt->execute([
    $idVente
]);

$vente = $stmt->fetch(
    PDO::FETCH_ASSOC
);


if (!$vente) {
    die("Cette facture n'existe pas.");
}


/* =====================================================
   RÉCUPÉRER LES PRODUITS
===================================================== */

$sqlProduits = "

    SELECT

        p.nom_produit,
        lc.quantite,
        lc.prix_unitaire,
        lc.sous_total

    FROM ligne_commande lc

    INNER JOIN produit p
        ON lc.id_produit = p.id_produit

    WHERE lc.id_commande = ?

    ORDER BY lc.id_ligne_commande ASC

";


$stmtProduits =
    $pdo->prepare($sqlProduits);

$stmtProduits->execute([
    $vente["id_commande"]
]);

$produits =
    $stmtProduits->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =====================================================
   NUMÉRO DE FACTURE
===================================================== */

$numeroFacture =
    "FAC-" .
    str_pad(
        $vente["id_vente"],
        4,
        "0",
        STR_PAD_LEFT
    );


/* =====================================================
   DATE
===================================================== */

$dateFacture = date(
    "d/m/Y",
    strtotime(
        $vente["date_vente"]
    )
);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>
    <?= $numeroFacture ?>
</title>


<link
    rel="stylesheet"
    href="facture.css">


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

</head>


<body>


<!-- =====================================================
     FACTURE
===================================================== -->

<div class="facture">


    <!-- =================================================
         TITRE
    ================================================== -->

    <div class="titre-facture">

        FACTURE N°

        <span>
            <?= htmlspecialchars(
                $numeroFacture
            ) ?>
        </span>

    </div>


    <!-- =================================================
         INFORMATIONS ENTREPRISE + CLIENT
    ================================================== -->

    <div class="entete">


        <!-- ENTREPRISE -->

        <div class="entreprise">

            <h1>
                DORIANE AGROFEED
            </h1>

            <p>
                Provenderie
            </p>

            <p>
                Bafoussam, Cameroun
            </p>

            <p>
                Marché B – Première rue
            </p>

            <p>
                Tél : <?= htmlspecialchars(
                    "À compléter"
                ) ?>
            </p>

        </div>


        <!-- CLIENT -->

        <div class="client-box">

            <h3>
                INFORMATIONS CLIENT
            </h3>

            <p>

                <strong>
                    <?= htmlspecialchars(
                        $vente["nom"]
                        . " "
                        . $vente["prenom"]
                    ) ?>
                </strong>

            </p>


            <?php if (
                !empty(
                    $vente["telephone"]
                )
            ): ?>

                <p>

                    Téléphone :

                    <?= htmlspecialchars(
                        $vente["telephone"]
                    ) ?>

                </p>

            <?php endif; ?>


            <?php if (
                !empty(
                    $vente["email"]
                )
            ): ?>

                <p>

                    Email :

                    <?= htmlspecialchars(
                        $vente["email"]
                    ) ?>

                </p>

            <?php endif; ?>

        </div>

    </div>


    <!-- =================================================
         INFORMATIONS COMMANDE
    ================================================== -->

    <div class="infos-commande">

        <div>

            <strong>
                Date de livraison :
            </strong>

            <?= $dateFacture ?>

        </div>


        <div>

            <strong>
                Date de facturation :
            </strong>

            <?= $dateFacture ?>

        </div>


        <div>

            <strong>
                Référence :
            </strong>

            CMD-<?= str_pad(
                $vente["id_commande"],
                4,
                "0",
                STR_PAD_LEFT
            ) ?>

        </div>

    </div>


    <!-- =================================================
         TABLEAU DES PRODUITS
    ================================================== -->

    <table class="table-facture">

        <thead>

            <tr>

                <th class="col-quantite">
                    Quantité
                </th>

                <th>
                    Désignation
                </th>

                <th class="col-prix">
                    Prix unitaire
                </th>

                <th class="col-total">
                    Montant
                </th>

            </tr>

        </thead>


        <tbody>


        <?php if (
            empty($produits)
        ): ?>

            <tr>

                <td
                    colspan="4"
                    class="aucun-produit">

                    Aucun produit

                </td>

            </tr>

        <?php else: ?>


            <?php foreach (
                $produits
                as
                $produit
            ): ?>

                <tr>


                    <!-- QUANTITÉ -->

                    <td class="quantite-cell">

                        <?= htmlspecialchars(
                            $produit[
                                "quantite"
                            ]
                        ) ?>

                    </td>


                    <!-- DÉSIGNATION -->

                    <td>

                        <?= htmlspecialchars(
                            $produit[
                                "nom_produit"
                            ]
                        ) ?>

                    </td>


                    <!-- PRIX UNITAIRE -->

                    <td class="prix-cell">

                        <?= number_format(
                            $produit[
                                "prix_unitaire"
                            ],
                            0,
                            ",",
                            " "
                        ) ?>

                        FCFA

                    </td>


                    <!-- MONTANT -->

                    <td class="montant-cell">

                        <?= number_format(
                            $produit[
                                "sous_total"
                            ],
                            0,
                            ",",
                            " "
                        ) ?>

                        FCFA

                    </td>

                </tr>

            <?php endforeach; ?>


        <?php endif; ?>


        </tbody>

    </table>


    <!-- =================================================
         TOTAL
    ================================================== -->

    <div class="total-zone">


        <div class="total-box">


            <div class="ligne-total">

                <span>
                    Sous-total
                </span>

                <strong>

                    <?= number_format(
                        $vente["montant"],
                        0,
                        ",",
                        " "
                    ) ?>

                    FCFA

                </strong>

            </div>


            <div class="ligne-total">

                <span>
                    TVA
                </span>

                <strong>
                    0 FCFA
                </strong>

            </div>


            <div class="total-final">

                <span>
                    TOTAL TTC
                </span>

                <strong>

                    <?= number_format(
                        $vente["montant"],
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
         STATUT
    ================================================== -->

    <div class="statut">

        Statut de la commande :

        <strong>

            <?= htmlspecialchars(
                $vente["statut"]
            ) ?>

        </strong>

    </div>


    <!-- =================================================
         MESSAGE
    ================================================== -->

    <div class="message">

        <p>
            Nous vous remercions pour votre confiance.
        </p>

        <p>
            DORIANE AGROFEED vous accompagne
            dans l'alimentation et l'élevage des animaux.
        </p>

    </div>


    <!-- =================================================
         SIGNATURE
    ================================================== -->

    <div class="signature">

        <div>

            <strong>
                DORIANE AGROFEED
            </strong>

            <br>

            La direction

        </div>


        <div>

            Signature du client

        </div>

    </div>


</div>
<!-- =====================================================
     BOUTONS ACTIONS (RETOUR + IMPRIMER)
===================================================== -->

<div class="zone-impression" style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">

    <a href="ventes.php" class="btn-retour" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
        <i class="bi bi-arrow-left"></i> Retour aux ventes
    </a>

    <button
        type="button"
        onclick="window.print()"
        class="btn-imprimer" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background-color: #0d6efd; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">

        <i class="bi bi-printer-fill"></i>

        Imprimer la facture

    </button>

</div>

</body>

</html>