<?php

session_start();

require_once "connexions.php";

/* =========================================================
   INITIALISATION
========================================================= */

if (!isset($_SESSION["panier"])) {
    $_SESSION["panier"] = [];
}

$messageSucces = "";
$messageErreur = "";
$idCommandeSucces = null;


/* =========================================================
   VÉRIFIER SI LE CLIENT EST CONNECTÉ
========================================================= */

function clientConnecte()
{
    return isset($_SESSION["id_utilisateur"])
        || isset($_SESSION["user_id"])
        || isset($_SESSION["id"]);
}


/* =========================================================
   RÉCUPÉRER L'ID UTILISATEUR
========================================================= */

function getIdUtilisateur()
{
    if (isset($_SESSION["id_utilisateur"])) {
        return (int) $_SESSION["id_utilisateur"];
    }

    if (isset($_SESSION["user_id"])) {
        return (int) $_SESSION["user_id"];
    }

    if (isset($_SESSION["id"])) {
        return (int) $_SESSION["id"];
    }

    return 0;
}


/* =========================================================
   AJOUTER AU PANIER VIA AJAX
========================================================= */

if (
    isset($_POST["ajax"])
    &&
    $_POST["ajax"] === "ajouter_panier"
) {

    header("Content-Type: application/json; charset=UTF-8");

    if (!clientConnecte()) {

        echo json_encode([
            "success" => false,
            "message" => "Veuillez vous connecter avant de commander.",
            "connexion" => true
        ]);

        exit;
    }


    $idProduit = isset($_POST["id_produit"])
        ? (int) $_POST["id_produit"]
        : 0;


    if ($idProduit <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "Produit invalide."
        ]);

        exit;
    }


    try {

        /* Récupérer le produit */

        $stmt = $pdo->prepare("
            SELECT
                id_produit,
                nom_produit,
                description,
                prix,
                stock,
                image
            FROM produit
            WHERE id_produit = ?
            LIMIT 1
        ");

        $stmt->execute([
            $idProduit
        ]);

        $produit = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$produit) {

            echo json_encode([
                "success" => false,
                "message" => "Produit introuvable."
            ]);

            exit;
        }


        $stock = (int) $produit["stock"];


        if ($stock <= 0) {

            echo json_encode([
                "success" => false,
                "message" => "Ce produit est en rupture de stock."
            ]);

            exit;
        }


        /* Si le produit existe déjà */

        if (isset($_SESSION["panier"][$idProduit])) {

            $ancienneQuantite =
                (int) $_SESSION["panier"][$idProduit]["quantite"];

            $nouvelleQuantite =
                $ancienneQuantite + 1;


            if ($nouvelleQuantite > $stock) {

                echo json_encode([
                    "success" => false,
                    "message" => "Stock maximum atteint."
                ]);

                exit;
            }


            $_SESSION["panier"][$idProduit]["quantite"] =
                $nouvelleQuantite;

            $_SESSION["panier"][$idProduit]["stock"] =
                $stock;

        } else {

            /* Nouveau produit */

            $_SESSION["panier"][$idProduit] = [

                "id_produit" =>
                    (int) $produit["id_produit"],

                "nom_produit" =>
                    $produit["nom_produit"],

                "description" =>
                    $produit["description"],

                "prix" =>
                    (float) $produit["prix"],

                "stock" =>
                    $stock,

                "image" =>
                    $produit["image"],

                "quantite" =>
                    1
            ];
        }


        /* Calcul du nombre total */

        $nombreArticles = 0;

        $totalPanier = 0;


        foreach ($_SESSION["panier"] as $article) {

            $quantite =
                (int) $article["quantite"];

            $prix =
                (float) $article["prix"];

            $nombreArticles += $quantite;

            $totalPanier +=
                $prix * $quantite;
        }


        echo json_encode([

            "success" => true,

            "message" =>
                htmlspecialchars(
                    $produit["nom_produit"]
                )
                . " a été ajouté au panier.",

            "nombreArticles" =>
                $nombreArticles,

            "totalPanier" =>
                $totalPanier,

            "quantite" =>
                (int) $_SESSION["panier"][$idProduit]["quantite"]
        ]);

    } catch (Throwable $e) {

        echo json_encode([

            "success" => false,

            "message" =>
                "Impossible d'ajouter le produit au panier."
        ]);
    }

    exit;
}


/* =========================================================
   MODIFIER LA QUANTITÉ VIA AJAX
========================================================= */

if (
    isset($_POST["ajax"])
    &&
    $_POST["ajax"] === "modifier_quantite"
) {

    header("Content-Type: application/json; charset=UTF-8");


    $idProduit =
        isset($_POST["id_produit"])
        ? (int) $_POST["id_produit"]
        : 0;


    $quantite =
        isset($_POST["quantite"])
        ? (int) $_POST["quantite"]
        : 1;


    if (
        $idProduit <= 0
        ||
        !isset($_SESSION["panier"][$idProduit])
    ) {

        echo json_encode([
            "success" => false,
            "message" => "Produit absent du panier."
        ]);

        exit;
    }


    $stock =
        (int) $_SESSION["panier"][$idProduit]["stock"];


    if ($quantite < 1) {
        $quantite = 1;
    }


    if ($quantite > $stock) {
        $quantite = $stock;
    }


    $_SESSION["panier"][$idProduit]["quantite"] =
        $quantite;


    $totalPanier = 0;
    $nombreArticles = 0;


    foreach ($_SESSION["panier"] as $article) {

        $q =
            (int) $article["quantite"];

        $p =
            (float) $article["prix"];

        $nombreArticles += $q;

        $totalPanier +=
            $p * $q;
    }


    $sousTotal =
        (float) $_SESSION["panier"][$idProduit]["prix"]
        *
        $quantite;


    echo json_encode([

        "success" => true,

        "quantite" =>
            $quantite,

        "sousTotal" =>
            $sousTotal,

        "totalPanier" =>
            $totalPanier,

        "nombreArticles" =>
            $nombreArticles
    ]);

    exit;
}


/* =========================================================
   SUPPRIMER UN PRODUIT VIA AJAX
========================================================= */

if (
    isset($_POST["ajax"])
    &&
    $_POST["ajax"] === "supprimer_panier"
) {

    header("Content-Type: application/json; charset=UTF-8");


    $idProduit =
        isset($_POST["id_produit"])
        ? (int) $_POST["id_produit"]
        : 0;


    if (isset($_SESSION["panier"][$idProduit])) {

        unset(
            $_SESSION["panier"][$idProduit]
        );
    }


    $totalPanier = 0;
    $nombreArticles = 0;


    foreach ($_SESSION["panier"] as $article) {

        $q =
            (int) $article["quantite"];

        $p =
            (float) $article["prix"];

        $nombreArticles += $q;

        $totalPanier +=
            $p * $q;
    }


    echo json_encode([

        "success" => true,

        "totalPanier" =>
            $totalPanier,

        "nombreArticles" =>
            $nombreArticles
    ]);

    exit;
}


/* =========================================================
   VIDER LE PANIER
========================================================= */

if (
    isset($_POST["vider_panier"])
) {

    $_SESSION["panier"] = [];

    header("Location: produit.php");

    exit;
}


/* =========================================================
   VALIDER LA COMMANDE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["valider_commande"])
) {

    if (!clientConnecte()) {

        header(
            "Location: connexion.php?retour=produit.php"
        );

        exit;
    }


    if (empty($_SESSION["panier"])) {

        $messageErreur =
            "Votre panier est vide.";

    } else {

        $idUtilisateur =
            getIdUtilisateur();


        if ($idUtilisateur <= 0) {

            $messageErreur =
                "Impossible d'identifier le client connecté.";

        } else {

            try {

                $pdo->beginTransaction();


                /* =========================================
                   VÉRIFIER LE CLIENT
                ========================================= */

                $stmtClient = $pdo->prepare("
                    SELECT
                        id_utilisateur,
                        nom,
                        prenom,
                        telephone,
                        email
                    FROM utilisateurs
                    WHERE id_utilisateur = ?
                    AND statut = 'actif'
                    LIMIT 1
                ");

                $stmtClient->execute([
                    $idUtilisateur
                ]);

                $client =
                    $stmtClient->fetch(PDO::FETCH_ASSOC);


                if (!$client) {

                    throw new Exception(
                        "Le compte client est introuvable ou inactif."
                    );
                }


                /* =========================================
                   CRÉER LA COMMANDE
                ========================================= */

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


                $idCommande =
                    (int) $pdo->lastInsertId();


                if ($idCommande <= 0) {

                    throw new Exception(
                        "Impossible de créer la commande."
                    );
                }


                /* =========================================
                   PRODUIT
                ========================================= */

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


                /* =========================================
                   LIGNE COMMANDE
                ========================================= */

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


                /* =========================================
                   STOCK
                ========================================= */

                $stmtStock = $pdo->prepare("
                    UPDATE produit
                    SET stock = stock - ?
                    WHERE id_produit = ?
                ");


                foreach (
                    $_SESSION["panier"]
                    as $idProduit => $article
                ) {

                    $idProduit =
                        (int) $idProduit;


                    $quantite =
                        (int) $article["quantite"];


                    $stmtProduit->execute([
                        $idProduit
                    ]);


                    $produit =
                        $stmtProduit->fetch(
                            PDO::FETCH_ASSOC
                        );


                    if (!$produit) {

                        throw new Exception(
                            "Un produit du panier est introuvable."
                        );
                    }


                    $stock =
                        (int) $produit["stock"];


                    if ($quantite <= 0) {

                        throw new Exception(
                            "Quantité invalide."
                        );
                    }


                    if ($quantite > $stock) {

                        throw new Exception(
                            "Stock insuffisant pour : "
                            .
                            $produit["nom_produit"]
                        );
                    }


                    $prix =
                        (float) $produit["prix"];


                    $sousTotal =
                        $prix * $quantite;


                    /* Enregistrer la ligne */

                    $stmtLigne->execute([

                        $idCommande,

                        $idProduit,

                        $quantite,

                        $prix,

                        $sousTotal
                    ]);


                    /* Diminuer le stock */

                    $stmtStock->execute([

                        $quantite,

                        $idProduit
                    ]);
                }


                $pdo->commit();


                /* Vider panier */

                $_SESSION["panier"] = [];


                $messageSucces =
                    "Votre commande a été enregistrée avec succès !";


                $idCommandeSucces =
                    $idCommande;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {

                    $pdo->rollBack();
                }


                $messageErreur =
                    "Impossible d'enregistrer la commande : "
                    .
                    $e->getMessage();
            }
        }
    }
}


/* =========================================================
   RÉCUPÉRER LES PRODUITS
========================================================= */

try {

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
        LEFT JOIN categorie c
            ON p.id_categorie = c.id_categorie
        ORDER BY p.id_produit ASC
    ";


    $stmt =
        $pdo->query($sql);


    $produits =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $produits = [];

    $messageErreur =
        "Erreur lors du chargement des produits.";
}


/* =========================================================
   CALCUL PANIER
========================================================= */

$totalPanier = 0;
$nombreArticles = 0;


foreach ($_SESSION["panier"] as $article) {

    $quantite =
        (int) $article["quantite"];

    $prix =
        (float) $article["prix"];


    $totalPanier +=
        $prix * $quantite;


    $nombreArticles +=
        $quantite;
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
    Nos produits - Doriane AgroFeed
</title>


<link
    rel="stylesheet"
    href="bootstrap-5.0.2-dist/css/bootstrap.min.css"
>


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


<style>

/* =========================================================
   GLOBAL
========================================================= */

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    padding: 0;
    background: #f4f7f4;
    font-family: Arial, Helvetica, sans-serif;
    color: #333;
}


/* =========================================================
   NAVBAR
========================================================= */

.navbar {
    background-color: #1B5E20 !important;
    min-height: 70px;
    padding: 8px 6px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-brand {
    background: white;
    padding: 7px 20px;
    border-radius: 20px;
    display: flex;
    align-items: center;
}

.navbar-brand img {
    width: 100px;
    height: 110px;
}

.navbar-nav {
    align-items: center;
}

.nav-link {
    color: white !important;
    font-size: 16px;
    font-weight: 600;
    padding: 15px 18px !important;
    transition: 0.3s ease;
    border-radius: 8px;
}

.nav-link:hover {
    background: rgba(255,255,255,0.15);
    color: #FFD700 !important;
}

.nav-link.active {
    background: rgba(255,255,255,0.18);
}


/* =========================================================
   AUTH
========================================================= */

.nav-auth {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: 15px;
}

.nav-auth a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 15px;
    border: 2px solid;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.nav-connexion {
    color: #0dcaf0;
    border-color: #0dcaf0;
}

.nav-connexion:hover {
    background: #0dcaf0;
    color: white;
}

.nav-inscription {
    color: #198754;
    border-color: #198754;
}

.nav-inscription:hover {
    background: #198754;
    color: white;
}

.nav-deconnexion {
    color: #fd7e14;
    border-color: #fd7e14;
}

.nav-deconnexion:hover {
    background: #fd7e14;
    color: white;
}


/* =========================================================
   PANIER
========================================================= */

.panier-nav {
    position: fixed;
    top: 140px;
    right: 60px;
    z-index: 9999;
}

.panier-button {
    background: white;
    color: #198754;
    border: 2px solid #198754;
    border-radius: 30px;
    padding: 12px 20px;
    font-size: 15px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    box-shadow: 0 5px 18px rgba(0,0,0,0.20);
    transition: all 0.3s ease;
}

.panier-button:hover {
    background: #198754;
    color: white;
}

.panier-badge {
    background: #dc3545;
    color: white;
    min-width: 24px;
    height: 24px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.panier-menu {
    position: absolute;
    top: 55px;
    right: 0;
    width: 400px;
    max-width: 92vw;
    background: white;
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.22);
    padding: 18px;
    z-index: 10000;
    display: none;
}

.panier-nav.open .panier-menu {
    display: block;
}


/* =========================================================
   PANIER TITRE
========================================================= */

.panier-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 12px;
    margin-bottom: 10px;
    border-bottom: 1px solid #e5e5e5;
}

.panier-title h3 {
    margin: 0;
    font-size: 18px;
    font-weight: bold;
    color: #198754;
}


/* =========================================================
   ARTICLE PANIER
========================================================= */

.panier-item {
    display: flex;
    gap: 10px;
    padding: 12px 0;
    border-bottom: 1px solid #eeeeee;
}

.panier-item img {
    width: 58px;
    height: 58px;
    object-fit: cover;
    border-radius: 9px;
    border: 1px solid #eeeeee;
}

.panier-item-info {
    flex: 1;
}

.panier-item-info strong {
    display: block;
    font-size: 14px;
    margin-bottom: 3px;
}

.panier-prix {
    color: #198754;
    font-size: 13px;
    font-weight: bold;
}


/* =========================================================
   QUANTITÉ
========================================================= */

.quantite {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 8px;
}

.quantite button {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
}

.btn-moins {
    background: #ffc107;
    color: #000;
}

.btn-plus {
    background: #198754;
    color: white;
}

.input-quantite {
    width: 55px;
    height: 28px;
    text-align: center;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-weight: bold;
}

.sous-total {
    font-size: 12px;
    margin-top: 5px;
    color: #555;
}


/* =========================================================
   SUPPRIMER
========================================================= */

.btn-supprimer-panier {
    border: none;
    background: transparent;
    color: #dc3545;
    font-size: 19px;
    cursor: pointer;
}

.btn-supprimer-panier:hover {
    color: #a71d2a;
}


/* =========================================================
   AJOUTER AUTRE PRODUIT
========================================================= */

.btn-autre-produit {
    display: block;
    width: 100%;
    margin-top: 15px;
    padding: 10px;
    text-align: center;
    border: 2px solid #198754;
    border-radius: 8px;
    text-decoration: none;
    color: #198754;
    font-weight: bold;
    transition: 0.3s;
}

.btn-autre-produit:hover {
    background: #198754;
    color: white;
}


/* =========================================================
   TOTAL
========================================================= */

.panier-total {
    display: flex;
    justify-content: space-between;
    padding: 15px 0;
    font-size: 18px;
    font-weight: bold;
    border-bottom: 1px solid #eeeeee;
}


/* =========================================================
   HEADER PRODUITS
========================================================= */

.produits-header {
    margin: auto;
    padding: 65px 20px;
    background: linear-gradient(
        rgba(25,135,84,0.94),
        rgba(25,135,84,0.94)
    );
    color: white;
    text-align: center;
}

.produits-header h1 {
    margin: 0 0 12px;
    font-size: 40px;
    font-weight: 800;
}

.produits-header p {
    margin: 0;
    font-size: 17px;
}


/* =========================================================
   MESSAGES
========================================================= */

.message-zone {
    width: 90%;
    max-width: 1100px;
    margin: 25px auto 0;
}


/* =========================================================
   PRODUITS
========================================================= */

.produits-container {
    width: 88%;
    max-width: 1100px;
    margin: 40px auto 60px;
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 18px;
}

.produit-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e8e8e8;
    box-shadow: 0 4px 14px rgba(0,0,0,0.07);
    transition: 0.3s;
}

.produit-card:hover {
    transform: translateY(-6px);
    background: #e8f8ee;
    border-color: #b9e8ca;
    box-shadow: 0 10px 25px rgba(25,135,84,0.20);
}

.image-container {
    width: 100%;
    height: 165px;
    overflow: hidden;
    background: #eee;
}

.image-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: 0.4s;
}

.produit-card:hover .image-container img {
    transform: scale(1.05);
}

.produit-info {
    padding: 15px;
}

.produit-info h2 {
    margin: 0 0 7px;
    color: #198754;
    font-size: 18px;
    font-weight: 700;
}

.description {
    color: #666;
    font-size: 13px;
    line-height: 1.5;
    min-height: 40px;
    margin-bottom: 10px;
}

.prix {
    color: #198754;
    font-size: 17px;
    font-weight: bold;
}

.stock {
    font-size: 13px;
    margin-bottom: 12px;
    color: #555;
}


/* =========================================================
   ACTIONS PRODUIT
========================================================= */

.actions {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.btn-commander {
    flex: 1;
    background: #fd7e14;
    color: white;
    padding: 9px 10px;
    border-radius: 7px;
    text-decoration: none;
    text-align: center;
    font-size: 14px;
    font-weight: bold;
    border: 2px solid #fd7e14;
    cursor: pointer;
    transition: 0.3s;
}

.btn-commander:hover {
    background: #198754;
    border-color: #198754;
    color: white;
}


/* =========================================================
   DETAILS
========================================================= */

details {
    flex: 1;
    position: relative;
}

.btn-details {
    display: block;
    width: 100%;
    background: #f1f3f2;
    color: #333;
    padding: 9px 10px;
    border-radius: 7px;
    cursor: pointer;
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    list-style: none;
}

.btn-details::-webkit-details-marker {
    display: none;
}

.btn-details:hover {
    background: #198754;
    color: white;
}

.details-content {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 7px);
    background: white;
    padding: 12px;
    border-radius: 9px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    z-index: 50;
    font-size: 12px;
    border: 1px solid #ddd;
}


/* =========================================================
   NOTIFICATION
========================================================= */

.notification-panier {
    position: fixed;
    top: 90px;
    right: 20px;
    z-index: 99999;
    display: none;
    min-width: 280px;
}

.notification-panier.show {
    display: block;
}


/* =========================================================
   FOOTER
========================================================= */

footer {
    margin-top: 50px;
    background: #212529 !important;
}

footer p {
    color: #ddd;
    font-size: 14px;
    line-height: 1.6;
}

footer .nav-link {
    font-size: 14px;
    padding: 4px !important;
}

footer img {
    border-radius: 8px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width:1000px) {

    .produits-container {
        grid-template-columns: repeat(2,1fr);
        width: 90%;
    }
}

@media (max-width:700px) {

    .produits-container {
        grid-template-columns: 1fr;
        width: 90%;
    }

    .panier-nav {
        top: 100px;
        right: 15px;
    }

    .panier-menu {
        width: 350px;
        max-width: 92vw;
    }

    .produits-header h1 {
        font-size: 32px;
    }

    .nav-auth {
        flex-direction: column;
        align-items: stretch;
        width: 100%;
        margin: 10px 0;
    }
}

</style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg navbar-dark">

<div class="container-fluid">

<a
    class="navbar-brand"
    href="index.php"
>

<img
    src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg"
    alt="Doriane AgroFeed"
>

</a>


<button
    class="navbar-toggler"
    type="button"
    data-bs-toggle="collapse"
    data-bs-target="#menu"
>

<span class="navbar-toggler-icon"></span>

</button>


<div
    class="collapse navbar-collapse"
    id="menu"
>

<ul class="navbar-nav mx-auto text-uppercase">

<li class="nav-item">
<a class="nav-link" href="index.php">
Accueil
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="a propos.php">
À propos
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="services.php">
Services
</a>
</li>

<li class="nav-item">
<a class="nav-link active" href="produit.php">
Produits
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="galerie.php">
Galerie
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="contact.php">
Contact
</a>
</li>

</ul>


<div class="nav-auth">

<?php if (
    isset($_SESSION["connecte"])
    &&
    $_SESSION["connecte"] === true
): ?>

<a
    href="deconexion.php"
    class="nav-deconnexion"
>
Déconnexion
</a>

<?php else: ?>

<a
    href="connexion.php"
    class="nav-connexion"
>
Connexion
</a>

<a
    href="inscription_client.php"
    class="nav-inscription"
>
Inscription
</a>

<?php endif; ?>

</div>

</div>

</div>

</nav>


<!-- =====================================================
     PANIER
===================================================== -->

<div
    class="panier-nav"
    id="panierNav"
>

<button
    type="button"
    class="panier-button"
    id="panierButton"
>

<i class="bi bi-cart-fill"></i>

Panier

<span
    class="panier-badge"
    id="nombrePanier"
>
<?= $nombreArticles ?>
</span>

</button>


<div
    class="panier-menu"
    id="panierMenu"
>

<div class="panier-title">

<h3>

<i class="bi bi-cart3"></i>

Mon panier

</h3>

<form method="POST">

<button
    type="submit"
    name="vider_panier"
    class="btn btn-sm btn-outline-danger"
    <?= empty($_SESSION["panier"]) ? "disabled" : "" ?>
>

Vider

</button>

</form>

</div>


<div id="contenuPanier">


<?php if (empty($_SESSION["panier"])): ?>

<div class="panier-vide text-center py-4">

<i
    class="bi bi-cart-x"
    style="font-size:40px;color:#aaa"
></i>

<p class="mt-2 mb-0">
Votre panier est vide.
</p>

</div>

<?php else: ?>


<?php foreach (
    $_SESSION["panier"]
    as $article
): ?>

<?php

$imagePanier =
    !empty($article["image"])
    ? "/img/" . $article["image"]
    : "/img/default.jpg";

$sousTotal =
    (float) $article["prix"]
    *
    (int) $article["quantite"];

?>

<div
    class="panier-item"
    data-id="<?= (int) $article["id_produit"] ?>"
    data-stock="<?= (int) $article["stock"] ?>"
    data-prix="<?= (float) $article["prix"] ?>"
>


<img
    src="<?= htmlspecialchars($imagePanier) ?>"
    alt="<?= htmlspecialchars($article["nom_produit"]) ?>"
>


<div class="panier-item-info">

<strong>
<?= htmlspecialchars($article["nom_produit"]) ?>
</strong>


<div class="panier-prix">

<?= number_format(
    $article["prix"],
    0,
    ",",
    " "
) ?>

FCFA

</div>


<div class="quantite">

<button
    type="button"
    class="btn-moins"
>
−
</button>


<input
    type="number"
    class="input-quantite"
    value="<?= (int) $article["quantite"] ?>"
    min="1"
    max="<?= (int) $article["stock"] ?>"
>


<button
    type="button"
    class="btn-plus"
>
+
</button>

</div>


<div class="sous-total">

Sous-total :

<strong class="sous-total-valeur">

<?= number_format(
    $sousTotal,
    0,
    ",",
    " "
) ?>

FCFA

</strong>

</div>

</div>


<button
    type="button"
    class="btn-supprimer-panier supprimer-article"
    title="Supprimer"
>

<i class="bi bi-trash"></i>

</button>


</div>

<?php endforeach; ?>


<div class="panier-total">

<span>
Total :
</span>

<strong
    class="text-success"
    id="totalPanier"
>

<?= number_format(
    $totalPanier,
    0,
    ",",
    " "
) ?>

FCFA

</strong>

</div>


<!-- AJOUTER AUTRE PRODUIT -->

<a
    href="#produits"
    class="btn-autre-produit"
    id="ajouterAutreProduit"
>

<i class="bi bi-plus-circle"></i>

Ajouter un autre produit

</a>


<!-- VALIDATION -->

<div class="mt-3">

<form
    method="POST"
    action="produit.php"
>

<button
    type="submit"
    name="valider_commande"
    class="btn btn-success w-100"
>

<i class="bi bi-check-circle"></i>

Valider la commande

</button>

</form>

</div>


<?php endif; ?>


</div>

</div>

</div>


<!-- =====================================================
     NOTIFICATION
===================================================== -->

<div
    id="notificationPanier"
    class="alert alert-success notification-panier"
>

<i class="bi bi-check-circle-fill"></i>

<span id="notificationTexte"></span>

</div>


<!-- =====================================================
     MESSAGES
===================================================== -->

<div class="message-zone">

<?php if ($messageSucces !== ""): ?>

<div class="alert alert-success">

<i class="bi bi-check-circle-fill"></i>

<strong>
<?= htmlspecialchars($messageSucces) ?>
</strong>

<?php if ($idCommandeSucces): ?>

<br>

Numéro de commande :

<strong>
#<?= (int) $idCommandeSucces ?>
</strong>

<?php endif; ?>

<br><br>

<a
    href="produit.php"
    class="btn btn-success"
>
Continuer mes achats
</a>

</div>

<?php endif; ?>


<?php if ($messageErreur !== ""): ?>

<div class="alert alert-danger">

<i class="bi bi-exclamation-triangle-fill"></i>

<?= htmlspecialchars($messageErreur) ?>

</div>

<?php endif; ?>

</div>


<!-- =====================================================
     HEADER
===================================================== -->

<section class="produits-header">

<h1>
Nos produits
</h1>

<p>
Découvrez nos produits de qualité
pour l'alimentation animale.
</p>

</section>


<!-- =====================================================
     PRODUITS
===================================================== -->

<section
    class="produits-container"
    id="produits"
>


<?php if (!empty($produits)): ?>


<?php foreach ($produits as $produit): ?>

<?php

$idProduit =
    (int) $produit["id_produit"];

$nomProduit =
    $produit["nom_produit"]
    ?? "Produit sans nom";

$description =
    $produit["description"]
    ?? "";

$prix =
    (float) $produit["prix"];

$stock =
    (int) $produit["stock"];

$image =
    $produit["image"]
    ?? "";

$categorie =
    $produit["nom_categorie"]
    ?? "Non classé";

?>


<article class="produit-card">


<div class="image-container">

<?php if ($image !== ""): ?>

<img
    src="/img/<?= htmlspecialchars($image) ?>"
    alt="<?= htmlspecialchars($nomProduit) ?>"
>

<?php else: ?>

<img
    src="/aaimg/default.jpg"
    alt="Produit"
>

<?php endif; ?>

</div>


<div class="produit-info">

<h2>
<?= htmlspecialchars($nomProduit) ?>
</h2>


<p class="description">

<?= htmlspecialchars($description) ?>

</p>


<p class="prix">

<?= number_format(
    $prix,
    0,
    ",",
    " "
) ?>

FCFA

</p>


<p class="stock">

<i class="bi bi-box-seam"></i>

<?php if ($stock > 0): ?>

Stock disponible :
<?= $stock ?>

<?php else: ?>

<span class="text-danger">
Rupture de stock
</span>

<?php endif; ?>

</p>


<div class="actions">


<?php if ($stock > 0): ?>

<!-- =================================================
     BOUTON COMMANDER
     AJAX : PAS DE RAFRAÎCHISSEMENT
================================================= -->

<button
    type="button"
    class="btn-commander commander-produit"
    data-id="<?= $idProduit ?>"
>

<i class="bi bi-cart-fill"></i>

Commander

</button>

<?php else: ?>

<button
    type="button"
    class="btn btn-secondary"
    disabled
>

Rupture de stock

</button>

<?php endif; ?>


<!-- DETAILS -->

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

<?= htmlspecialchars($nomProduit) ?>

</p>


<p>

<strong>
Catégorie :
</strong>

<?= htmlspecialchars($categorie) ?>

</p>


<p>

<strong>
Description :
</strong>

<?= htmlspecialchars($description) ?>

</p>


<p>

<strong>
Prix :
</strong>

<?= number_format(
    $prix,
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

<?= $stock ?>

</p>

</div>

</details>


</div>

</div>

</article>


<?php endforeach; ?>


<?php else: ?>

<div class="alert alert-info">

Aucun produit disponible.

</div>

<?php endif; ?>


</section>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="bg-dark text-light pt-5 pb-3">

<div class="container">

<div class="row">


<div class="col-lg-3 col-md-6 mb-4">

<img
    src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg"
    alt="Logo"
    width="90"
    class="mb-3"
>

<p>

Votre partenaire de confiance
pour l'alimentation et la
nutrition animale.

</p>

</div>


<div class="col-lg-2 col-md-6 mb-4">

<h5 class="text-warning fw-bold">
Navigation
</h5>

<ul class="nav flex-column">

<li>
<a
    href="index.php"
    class="nav-link text-light p-1"
>
Accueil
</a>
</li>

<li>
<a
    href="a propos.php"
    class="nav-link text-light p-1"
>
À propos
</a>
</li>

<li>
<a
    href="services.php"
    class="nav-link text-light p-1"
>
Nos services
</a>
</li>

<li>
<a
    href="produit.php"
    class="nav-link text-light p-1"
>
Produits
</a>
</li>

<li>
<a
    href="galerie.php"
    class="nav-link text-light p-1"
>
Galerie
</a>
</li>

<li>
<a
    href="contact.php"
    class="nav-link text-light p-1"
>
Contact
</a>
</li>

</ul>

</div>


<div class="col-lg-3 col-md-6 mb-4">

<h5 class="text-warning fw-bold">
Contact
</h5>

<p>
📍 Marché B, Première Rue,
<br>
Bafoussam
</p>

<p>
📞 +237 683 37 66 74
</p>

<p>
✉ contact@dorianeagrofeed.com
</p>

</div>


<div class="col-lg-2 col-md-6 mb-4">

<h5 class="text-warning fw-bold">
Horaires
</h5>

<p>
🕒 Lundi - Samedi
</p>

<p>
08h30 - 17h30
</p>

</div>


<div class="col-lg-2 col-md-6 mb-4">

<h5 class="text-warning fw-bold">
Suivez-nous
</h5>

<a
    href="https://wa.me/237683376674"
    target="_blank"
    class="btn btn-success rounded-circle me-2"
>

<i class="bi bi-whatsapp"></i>

</a>


<a
    href="https://facebook.com"
    target="_blank"
    class="btn btn-primary rounded-circle"
>

<i class="bi bi-facebook"></i>

</a>

</div>


</div>


<hr class="border-secondary">


<div class="text-center">

<p class="mb-0">

© 2026

<strong>
Doriane Agro Feed
</strong>

| Tous droits réservés.

</p>

</div>


</div>

</footer>


<script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>


<script>

/* =========================================================
   VARIABLES
========================================================= */

const panierButton =
    document.getElementById("panierButton");

const panierNav =
    document.getElementById("panierNav");

const panierMenu =
    document.getElementById("panierMenu");

const contenuPanier =
    document.getElementById("contenuPanier");

const nombrePanier =
    document.getElementById("nombrePanier");

const notification =
    document.getElementById("notificationPanier");

const notificationTexte =
    document.getElementById("notificationTexte");


/* =========================================================
   OUVRIR / FERMER PANIER
========================================================= */

panierButton.addEventListener(
    "click",
    function(event) {

        event.stopPropagation();

        panierNav.classList.toggle("open");

    }
);


panierMenu.addEventListener(
    "click",
    function(event) {

        event.stopPropagation();

    }
);


document.addEventListener(
    "click",
    function() {

        panierNav.classList.remove("open");

    }
);


/* =========================================================
   NOTIFICATION
========================================================= */

function afficherNotification(message, erreur = false)
{

    notificationTexte.textContent =
        message;

    notification.classList.remove(
        "alert-success",
        "alert-danger"
    );

    notification.classList.add(
        erreur
        ? "alert-danger"
        : "alert-success"
    );

    notification.classList.add("show");


    setTimeout(
        function() {

            notification.classList.remove(
                "show"
            );

        },
        2500
    );
}


/* =========================================================
   FORMATAGE PRIX
========================================================= */

function formatPrix(nombre)
{

    return Number(nombre).toLocaleString(
        "fr-FR"
    );

}


/* =========================================================
   AJOUTER UN PRODUIT
   SANS RAFRAÎCHIR
========================================================= */

document
.querySelectorAll(".commander-produit")
.forEach(
    function(bouton)
    {

        bouton.addEventListener(
            "click",
            function()
            {

                const idProduit =
                    bouton.dataset.id;


                const donnees =
                    new FormData();


                donnees.append(
                    "ajax",
                    "ajouter_panier"
                );


                donnees.append(
                    "id_produit",
                    idProduit
                );


                bouton.disabled = true;


                fetch(
                    "produit.php",
                    {
                        method: "POST",
                        body: donnees
                    }
                )
                .then(
                    response =>
                        response.json()
                )
                .then(
                    data =>
                    {

                        bouton.disabled =
                            false;


                        if (
                            !data.success
                        ) {

                            afficherNotification(
                                data.message,
                                true
                            );

                            return;
                        }


                        /* Compteur */

                        nombrePanier.textContent =
                            data.nombreArticles;


                        /* Notification */

                        afficherNotification(
                            data.message
                        );


                        /*
                         * Ouvrir automatiquement
                         * le panier
                         */

                        panierNav.classList.add(
                            "open"
                        );


                        /*
                         * Recharger uniquement
                         * le contenu du panier
                         */

                        setTimeout(
                            function()
                            {

                                location.reload();

                            },
                            600
                        );

                    }
                )
                .catch(
                    function()
                    {

                        bouton.disabled =
                            false;

                        afficherNotification(
                            "Une erreur est survenue.",
                            true
                        );

                    }
                );

            }
        );

    }
);


/* =========================================================
   MODIFIER QUANTITÉ
========================================================= */

function modifierQuantite(
    article,
    quantite
)
{

    const idProduit =
        article.dataset.id;


    const donnees =
        new FormData();


    donnees.append(
        "ajax",
        "modifier_quantite"
    );


    donnees.append(
        "id_produit",
        idProduit
    );


    donnees.append(
        "quantite",
        quantite
    );


    fetch(
        "produit.php",
        {
            method: "POST",
            body: donnees
        }
    )
    .then(
        response =>
            response.json()
    )
    .then(
        data =>
        {

            if (!data.success) {

                return;
            }


            const input =
                article.querySelector(
                    ".input-quantite"
                );


            input.value =
                data.quantite;


            article
                .querySelector(
                    ".sous-total-valeur"
                )
                .textContent =
                    formatPrix(
                        data.sousTotal
                    )
                    +
                    " FCFA";


            document
                .getElementById(
                    "totalPanier"
                )
                .textContent =
                    formatPrix(
                        data.totalPanier
                    )
                    +
                    " FCFA";


            nombrePanier.textContent =
                data.nombreArticles;

        }
    );

}


/* =========================================================
   BOUTON +
========================================================= */

document
.querySelectorAll(".btn-plus")
.forEach(
    function(bouton)
    {

        bouton.addEventListener(
            "click",
            function()
            {

                const article =
                    bouton.closest(
                        ".panier-item"
                    );


                const input =
                    article.querySelector(
                        ".input-quantite"
                    );


                let quantite =
                    parseInt(
                        input.value
                    );


                const stock =
                    parseInt(
                        article.dataset.stock
                    );


                if (
                    quantite < stock
                ) {

                    quantite++;

                    input.value =
                        quantite;

                    modifierQuantite(
                        article,
                        quantite
                    );

                } else {

                    afficherNotification(
                        "Stock maximum atteint.",
                        true
                    );

                }

            }
        );

    }
);


/* =========================================================
   BOUTON -
========================================================= */

document
.querySelectorAll(".btn-moins")
.forEach(
    function(bouton)
    {

        bouton.addEventListener(
            "click",
            function()
            {

                const article =
                    bouton.closest(
                        ".panier-item"
                    );


                const input =
                    article.querySelector(
                        ".input-quantite"
                    );


                let quantite =
                    parseInt(
                        input.value
                    );


                if (
                    quantite > 1
                ) {

                    quantite--;

                    input.value =
                        quantite;

                    modifierQuantite(
                        article,
                        quantite
                    );

                }

            }
        );

    }
);


/* =========================================================
   SAISIE DIRECTE DE LA QUANTITÉ
========================================================= */

document
.querySelectorAll(".input-quantite")
.forEach(
    function(input)
    {

        input.addEventListener(
            "change",
            function()
            {

                const article =
                    input.closest(
                        ".panier-item"
                    );


                let quantite =
                    parseInt(
                        input.value
                    );


                const stock =
                    parseInt(
                        article.dataset.stock
                    );


                if (
                    isNaN(quantite)
                    ||
                    quantite < 1
                ) {

                    quantite = 1;
                }


                if (
                    quantite > stock
                ) {

                    quantite = stock;

                    afficherNotification(
                        "La quantité dépasse le stock disponible.",
                        true
                    );
                }


                input.value =
                    quantite;


                modifierQuantite(
                    article,
                    quantite
                );

            }
        );

    }
);


/* =========================================================
   SUPPRIMER UN PRODUIT
========================================================= */

document
.querySelectorAll(".supprimer-article")
.forEach(
    function(bouton)
    {

        bouton.addEventListener(
            "click",
            function()
            {

                const article =
                    bouton.closest(
                        ".panier-item"
                    );


                const idProduit =
                    article.dataset.id;


                const donnees =
                    new FormData();


                donnees.append(
                    "ajax",
                    "supprimer_panier"
                );


                donnees.append(
                    "id_produit",
                    idProduit
                );


                fetch(
                    "produit.php",
                    {
                        method: "POST",
                        body: donnees
                    }
                )
                .then(
                    response =>
                        response.json()
                )
                .then(
                    data =>
                    {

                        if (
                            !data.success
                        ) {

                            return;
                        }


                        article.remove();


                        nombrePanier.textContent =
                            data.nombreArticles;


                        document
                            .getElementById(
                                "totalPanier"
                            )
                            .textContent =
                                formatPrix(
                                    data.totalPanier
                                )
                                +
                                " FCFA";


                        afficherNotification(
                            "Produit supprimé du panier."
                        );


                        /*
                         * Si panier vide,
                         * recharger pour afficher
                         * le message panier vide.
                         */

                        if (
                            data.nombreArticles === 0
                        ) {

                            setTimeout(
                                function()
                                {

                                    location.reload();

                                },
                                500
                            );

                        }

                    }
                );

            }
        );

    }
);


/* =========================================================
   AJOUTER UN AUTRE PRODUIT
========================================================= */

const autreProduit =
    document.getElementById(
        "ajouterAutreProduit"
    );


if (autreProduit) {

    autreProduit.addEventListener(
        "click",
        function()
        {

            panierNav.classList.remove(
                "open"
            );

        }
    );

}

</script>


</body>

</html>