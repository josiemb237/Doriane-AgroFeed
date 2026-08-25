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
   RÉCUPÉRER L'ID DU CLIENT CONNECTÉ
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
   AJOUTER UN PRODUIT AU PANIER
========================================================= */

if (isset($_GET["ajouter"])) {

    $idProduit = (int) $_GET["ajouter"];


    /* Vérifier connexion */

    if (!clientConnecte()) {

        header(
            "Location: connexion.php?retour=produit.php"
        );

        exit;
    }


    /* Vérifier ID */

    if ($idProduit <= 0) {

        header("Location: produit.php");

        exit;
    }


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


    /* Produit inexistant */

    if (!$produit) {

        $_SESSION["erreur_panier"] =
            "Produit introuvable.";

        header("Location: produit.php");

        exit;
    }


    /* Rupture de stock */

    if ((int) $produit["stock"] <= 0) {

        $_SESSION["erreur_panier"] =
            "Ce produit est en rupture de stock.";

        header("Location: produit.php");

        exit;
    }


    /* Produit déjà dans le panier */

    if (isset($_SESSION["panier"][$idProduit])) {

        $nouvelleQuantite =
            (int) $_SESSION["panier"][$idProduit]["quantite"] + 1;


        if (
            $nouvelleQuantite >
            (int) $produit["stock"]
        ) {

            $_SESSION["erreur_panier"] =
                "La quantité demandée dépasse le stock disponible.";

        } else {

            $_SESSION["panier"][$idProduit]["quantite"] =
                $nouvelleQuantite;

            /* Actualiser le stock */

            $_SESSION["panier"][$idProduit]["stock"] =
                (int) $produit["stock"];
        }

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
                (int) $produit["stock"],

            "image" =>
                $produit["image"],

            "quantite" =>
                1
        ];
    }


    header("Location: produit.php");

    exit;
}


/* =========================================================
   AUGMENTER LA QUANTITÉ
========================================================= */

if (isset($_GET["plus"])) {

    $idProduit = (int) $_GET["plus"];


    if (isset($_SESSION["panier"][$idProduit])) {

        $quantite =
            (int) $_SESSION["panier"][$idProduit]["quantite"];

        $stock =
            (int) $_SESSION["panier"][$idProduit]["stock"];


        if ($quantite < $stock) {

            $_SESSION["panier"][$idProduit]["quantite"]++;

        } else {

            $_SESSION["erreur_panier"] =
                "Stock maximum atteint.";
        }
    }


    header("Location: produit.php");

    exit;
}


/* =========================================================
   DIMINUER LA QUANTITÉ
========================================================= */

if (isset($_GET["moins"])) {

    $idProduit = (int) $_GET["moins"];


    if (isset($_SESSION["panier"][$idProduit])) {

        $_SESSION["panier"][$idProduit]["quantite"]--;


        if (
            $_SESSION["panier"][$idProduit]["quantite"] <= 0
        ) {

            unset(
                $_SESSION["panier"][$idProduit]
            );
        }
    }


    header("Location: produit.php");

    exit;
}


/* =========================================================
   SUPPRIMER UN PRODUIT
========================================================= */

if (isset($_GET["supprimer"])) {

    $idProduit =
        (int) $_GET["supprimer"];


    if (isset($_SESSION["panier"][$idProduit])) {

        unset(
            $_SESSION["panier"][$idProduit]
        );
    }


    header("Location: produit.php");

    exit;
}


/* =========================================================
   VIDER LE PANIER
========================================================= */

if (isset($_GET["vider_panier"])) {

    $_SESSION["panier"] = [];


    header("Location: produit.php");

    exit;
}


/* =========================================================
   MESSAGE D'ERREUR DU PANIER
========================================================= */

if (isset($_SESSION["erreur_panier"])) {

    $messageErreur =
        $_SESSION["erreur_panier"];

    unset(
        $_SESSION["erreur_panier"]
    );
}


/* =========================================================
   VALIDER LA COMMANDE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["valider_commande"])
) {


    /* Vérifier connexion */

    if (!clientConnecte()) {

        header(
            "Location: connexion.php?retour=produit.php"
        );

        exit;
    }


    /* Vérifier panier */

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

                /* =========================================
                   DÉBUT TRANSACTION
                ========================================= */

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


                /* =========================================
                   REQUÊTE PRODUIT
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
                   REQUÊTE LIGNE COMMANDE
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
                   REQUÊTE STOCK
                ========================================= */

                $stmtStock = $pdo->prepare("
                    UPDATE produit
                    SET stock = stock - ?
                    WHERE id_produit = ?
                ");


                $totalCommande = 0;


                /* =========================================
                   TRAITER CHAQUE PRODUIT
                ========================================= */

                foreach (
                    $_SESSION["panier"]
                    as $idProduit => $article
                ) {

                    $idProduit =
                        (int) $idProduit;


                    $quantite =
                        (int) $article["quantite"];


                    /* Récupérer le stock réel */

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


                    /* Vérifier quantité */

                    if ($quantite <= 0) {

                        throw new Exception(
                            "La quantité demandée est invalide."
                        );
                    }


                    /* Vérifier stock */

                    if ($quantite > $stock) {

                        throw new Exception(
                            "Stock insuffisant pour le produit : "
                            .
                            $produit["nom_produit"]
                        );
                    }


                    /* Prix réel de la base */

                    $prix =
                        (float) $produit["prix"];


                    /* Sous-total */

                    $sousTotal =
                        $prix * $quantite;


                    $totalCommande +=
                        $sousTotal;


                    /* Enregistrer ligne commande */

                    $stmtLigne->execute([

                        $idCommande,

                        $idProduit,

                        $quantite,

                        $prix,

                        $sousTotal
                    ]);


                    /* Diminuer stock */

                    $stmtStock->execute([

                        $quantite,

                        $idProduit
                    ]);
                }


                /* =========================================
                   VALIDER TRANSACTION
                ========================================= */

                $pdo->commit();


                /* =========================================
                   VIDER PANIER
                ========================================= */

                $_SESSION["panier"] = [];


                /* =========================================
                   MESSAGE SUCCÈS
                ========================================= */

                $messageSucces =
                    "Votre commande a été enregistrée avec succès !";


                $idCommandeSucces =
                    $idCommande;


            } catch (Throwable $e) {


                /* Annuler transaction */

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
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $produits = [];

    $messageErreur =
        "Erreur lors du chargement des produits.";
}


/* =========================================================
   CALCUL DU PANIER
========================================================= */

$totalPanier = 0;

$nombreArticles = 0;


if (!empty($_SESSION["panier"])) {

    foreach (
        $_SESSION["panier"]
        as $article
    ) {

        $quantite =
            (int) $article["quantite"];


        $prix =
            (float) $article["prix"];


        $totalPanier +=
            $prix * $quantite;


        $nombreArticles +=
            $quantite;
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
   RESET / GLOBAL
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
  background-color: #1B5E20  !important;
    min-height: 70px;
    padding: 8px 6px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    position: sticky;
    top: 0;
    z-index: 1000;
}


/* LOGO */

.navbar-brand {
    background: white;
    padding: 7px 20px;
    border-radius:  20px ;
    display: flex;
    align-items: center;
}

.navbar-brand img {
    width: 100px;
    height: 110px;

}


/* LIENS */

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
    background: rgba(255, 255, 255, 0.15);
    color:#FFD700 !important;
}

.nav-link.active {
    background: rgba(255, 255, 255, 0.18);
}


/* BOUTON MOBILE */

.navbar-toggler {
    border: 2px solid white;
    padding: 8px 10px;
}

.navbar-toggler:focus {
    box-shadow: none;
}
/* =========================================================
   BOUTONS AUTHENTIFICATION
========================================================= */

.nav-auth {
    display: flex;
    align-items: center;
    gap: 10px;

    margin-left: 15px;
}


/* Style commun */

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

    white-space: nowrap;
}


/* =========================================================
   CONNEXION
   Couleur : bleu cyan
========================================================= */

.nav-connexion {
    color: #0dcaf0;

    border-color: #0dcaf0;

    background: transparent;
}

.nav-connexion:hover {
    background-color: #0dcaf0;

    color: white;

    transform: translateY(-2px);

    box-shadow: 0 5px 12px rgba(13, 202, 240, 0.25);
}


/* =========================================================
   INSCRIPTION
   Couleur : vert
========================================================= */

.nav-inscription {
    color: #198754;

    border-color: #198754;

    background: transparent;
}

.nav-inscription:hover {
    background-color: #198754;

    color: white;

    transform: translateY(-2px);

    box-shadow: 0 5px 12px rgba(25, 135, 84, 0.25);
}


/* =========================================================
   DECONNEXION
   Couleur : orange
========================================================= */

.nav-deconnexion {
    color: #fd7e14;

    border-color: #fd7e14;

    background: transparent;
}

.nav-deconnexion:hover {
    background-color: #fd7e14;

    color: white;

    transform: translateY(-2px);

    box-shadow: 0 5px 12px rgba(253, 126, 20, 0.25);
}
@media (max-width: 700px) {

    .nav-auth {
        flex-direction: column;
        align-items: stretch;

        width: 100%;

        margin: 10px 0 0;
    }

    .nav-auth a {
        width: 100%;
    }
}
/* =========================================================
   PANIER FLOTTANT
========================================================= */

.panier-nav {
    position:fixed;
    top: 140px;
    right: 60px;

    z-index: 9999;
}

/* Bouton panier */

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

    box-shadow: 0 5px 18px rgba(0, 0, 0, 0.20);

    transition: all 0.3s ease;
}

.panier-button:hover {
    background: #198754;
    color: white;

    transform: translateY(3px);

    box-shadow: 0 8px 22px rgba(25, 135, 84, 0.30);
}


/* Badge */

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


/* Menu du panier */

.panier-menu {
    position: absolute;

    top: 55px;
    right: 0;

    width: 380px;
    max-width: 90vw;

    background: white;

    border-radius: 15px;

    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.22);

    padding: 18px;

    z-index: 10000;

    display: none;
}

.panier-nav.open .panier-menu {
    display: block;
}
@media (max-width: 700px) {

    .panier-nav {
        top: 100px;
        right: 15px;
    }

    .panier-button {
        padding: 10px 15px;
        font-size: 14px;
    }

    .panier-menu {
        top: 52px;
        right: -5px;
        width: 350px;
        max-width: 92vw;
    }
}



/* =========================================================
   TITRE PANIER
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
   PANIER VIDE
========================================================= */

.panier-vide {
    text-align: center;

    padding: 30px 10px;

    color: #777;
}

.panier-vide i {
    font-size: 42px;
    color: #aaa;
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

    margin-top: 7px;
}

.quantite a {
    width: 26px;
    height: 26px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 6px;

    text-decoration: none;

    font-weight: bold;

    transition: 0.2s ease;
}

.quantite a:hover {
    transform: scale(1.08);
}

.btn-moins {
    background: #ffc107;
    color: #000;
}

.btn-plus {
    background: #198754;
    color: white;
}

.quantite span {
    min-width: 28px;

    text-align: center;

    font-weight: bold;
}


/* SUPPRIMER */

.supprimer {
    color: #dc3545;

    font-size: 18px;

    text-decoration: none;

    transition: 0.2s;
}

.supprimer:hover {
    color: #a71d2a;

    transform: scale(1.1);
}


/* =========================================================
   TOTAL PANIER
========================================================= */

.panier-total {
    display: flex;

    align-items: center;
    justify-content: space-between;

    padding: 15px 0;

    font-size: 18px;

    font-weight: bold;

    border-bottom: 1px solid #eeeeee;
}


.produits-header {
    
    margin: auto;
    padding: 65px 20px;

    background:
        linear-gradient(
            rgba(25, 135, 84, 0.94),
            rgba(25, 135, 84, 0.94)
        );

    color: white;

    text-align: center;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;
}

.produits-header h1 {
    margin: 0 0 12px;

    font-size: 40px;

    font-weight: 800;
}

.produits-header p {
    margin: 0;

    font-size: 17px;

    opacity: 0.95;
}


/* =========================================================
   ZONE DES MESSAGES
========================================================= */

.message-zone {
    width: 90%;

    max-width: 1100px;

    margin: 25px auto 0;
}

.message-zone .alert {
    border-radius: 10px;
}


/* =========================================================
   CONTENEUR PRODUITS
========================================================= */

.produits-container {
    width: 88%;

    max-width: 1100px;

    margin: 40px auto 60px;

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;
}


/* =========================================================
   CARTE PRODUIT
========================================================= */

.produit-card {
    background: white;

    border-radius: 12px;

    overflow: hidden;

    border: 1px solid #e8e8e8;

    box-shadow:
        0 4px 14px rgba(0, 0, 0, 0.07);

    transition:
        transform 0.3s ease,
        box-shadow 0.3s ease,
        background 0.3s ease;
}


/* HOVER CARTE */

.produit-card:hover {
    transform: translateY(-6px);

    background: #e8f8ee;

    border-color: #b9e8ca;

    box-shadow:
        0 10px 25px rgba(25, 135, 84, 0.20);
}


/* =========================================================
   IMAGE PRODUIT
========================================================= */

.image-container {
    width: 100%;

    height: 165px;

    overflow: hidden;

    background: #eeeeee;
}

.image-container img {
    width: 100%;

    height: 100%;

    object-fit: cover;

    transition: transform 0.4s ease;
}

.produit-card:hover
.image-container img {
    transform: scale(1.05);
}


/* =========================================================
   INFORMATIONS PRODUIT
========================================================= */

.produit-info {
    padding: 15px;
}

.produit-info h2 {
    margin: 0 0 7px;

    color: #198754;

    font-size: 18px;

    font-weight: 700;
}


/* DESCRIPTION */

.description {
    color: #666;

    font-size: 13px;

    line-height: 1.5;

    min-height: 40px;

    margin-bottom: 10px;
}


/* PRIX */

.prix {
    color: #198754;

    font-size: 17px;

    font-weight: bold;

    margin-bottom: 7px;
}


/* STOCK */

.stock {
    font-size: 13px;

    margin-bottom: 12px;

    color: #555;
}


/* =========================================================
   ACTIONS
========================================================= */

.actions {
    display: flex;

    align-items: flex-start;

    gap: 8px;

    position: relative;
}


/* =========================================================
   BOUTON COMMANDER
========================================================= */

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

    transition: 0.3s ease;

    border: 2px solid #fd7e14;
}

.btn-commander:hover {
    background: #198754;

    border-color: #198754;

    color: white;

    transform: translateY(-2px);

    box-shadow:
        0 5px 12px rgba(25, 135, 84, 0.25);
}


/* =========================================================
   BOUTON DETAILS
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

    transition: 0.3s ease;
}


/* Supprimer le petit triangle */

.btn-details::-webkit-details-marker {
    display: none;
}

.btn-details:hover {
    background: #198754;

    color: white;
}


/* =========================================================
   CONTENU DETAILS
========================================================= */

.details-content {
    position: absolute;

    left: 0;

    right: 0;

    top: calc(100% + 7px);

    background: white;

    padding: 12px;

    border-radius: 9px;

    box-shadow:
        0 8px 25px rgba(0, 0, 0, 0.15);

    z-index: 50;

    font-size: 12px;

    border: 1px solid #ddd;
}

.details-content p {
    margin: 0 0 7px;

    line-height: 1.4;
}

.details-content p:last-child {
    margin-bottom: 0;
}


/* =========================================================
   BOUTON RUPTURE DE STOCK
========================================================= */

.actions .btn-secondary {
    flex: 1;

    font-size: 13px;

    padding: 9px;

    border-radius: 7px;
}


/* =========================================================
   FOOTER
========================================================= */

footer {
    margin-top: 50px;

    background: #212529 !important;
}

footer h5 {
    margin-bottom: 15px;
}

footer p {
    color: #ddd;

    font-size: 14px;

    line-height: 1.6;
}

footer .nav-link {
    font-size: 14px;

    padding: 4px !important;

    background: transparent !important;
}

footer .nav-link:hover {
    color: #ffc107 !important;

    padding-left: 8px !important;
}

footer img {
    border-radius: 8px;
}


/* =========================================================
   RESEAUX SOCIAUX
========================================================= */

footer .btn {
    width: 42px;

    height: 42px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    transition: 0.3s ease;
}

footer .btn:hover {
    transform: translateY(-4px);

    box-shadow:
        0 5px 15px rgba(255, 255, 255, 0.15);
}


/* =========================================================
   HR FOOTER
========================================================= */

footer hr {
    opacity: 0.25;
}


/* =========================================================
   RESPONSIVE TABLETTE
========================================================= */

@media (max-width: 1000px) {

    .produits-container {
        grid-template-columns:
            repeat(2, 1fr);

        width: 90%;
    }

    .navbar {
        padding: 10px 15px;
    }

    .nav-link {
        padding: 10px !important;
    }

    .panier-nav {
        margin-left: 10px;
    }
}


/* =========================================================
   RESPONSIVE TÉLÉPHONE
========================================================= */

@media (max-width: 700px) {

    .produits-container {
        grid-template-columns: 1fr;

        width: 90%;

        gap: 20px;
    }

    .produit-card {
        max-width: 420px;

        width: 100%;

        margin: auto;
    }

    .produits-header {
        min-height: 200px;

        padding: 50px 15px;
    }

    .produits-header h1 {
        font-size: 32px;
    }

    .produits-header p {
        font-size: 15px;
    }

    .panier-menu {
        right: -15px;

        width: 350px;

        max-width: 92vw;
    }
}


/* =========================================================
   PETITS TÉLÉPHONES
========================================================= */

@media (max-width: 400px) {

    .panier-menu {
        width: 320px;

        max-width: 90vw;
    }

    .actions {
        flex-direction: column;
    }

    .btn-commander,
    details {
        width: 100%;
    }

    .details-content {
        position: static;

        margin-top: 7px;
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


    <!-- LOGO -->

    <a
        class="navbar-brand"
        href="index.php"
    >

        <img
            src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg"
            alt="Doriane AgroFeed"
        >

    </a>


    <!-- MENU MOBILE -->

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


        <!-- MENU -->

        <ul class="navbar-nav mx-auto text-uppercase">


            <li class="nav-item">

                <a
                    class="nav-link px-3"
                    href="index.php"
                >
                    Accueil
                </a>

            </li>


            <li class="nav-item">

                <a
                    class="nav-link px-3"
                    href="a propos.php"
                >
                    À propos
                </a>

            </li>


            <li class="nav-item">

                <a
                    class="nav-link px-3"
                    href="services.php"
                >
                    Services
                </a>

            </li>


            <li class="nav-item">

                <a
                    class="nav-link px-3 active"
                    href="produit.php"
                >
                    Produits
                </a>

            </li>


            <li class="nav-item">

                <a
                    class="nav-link px-3"
                    href="galerie.php"
                >
                    Galerie
                </a>

            </li>


            <li class="nav-item">

                <a
                    class="nav-link px-3"
                    href="contact.php"
                >
                    Contact
                </a>

            </li>

        </ul>
        <div class="nav-auth">
       <?php if (isset($_SESSION["connecte"]) && $_SESSION["connecte"] === true): ?>

                <a href="deconexion.php" class="nav-deconnexion">
                    Déconnexion
                </a>

            <?php else: ?>

                <a href="connexion.php" class="nav-connexion">
                    Connexion
                </a>

                <a href="inscription_client.php" class="nav-inscription">
                    Inscription
                </a>

            <?php endif; ?>
            </div>
    </div>
</div>
</nav>
      

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

                <span class="panier-badge">

                    <?= $nombreArticles ?>

                </span>

            </button>


            <!-- =================================================
                 CONTENU DU PANIER
            ================================================= -->

            <div
                class="panier-menu"
                id="panierMenu"
            >


                <div class="panier-title">


                    <h3>

                        <i class="bi bi-cart3"></i>

                        Mon panier

                    </h3>


                    <?php if (!empty($_SESSION["panier"])): ?>

                        <a
                            href="produit.php?vider_panier=1"
                            class="text-danger small"
                        >

                            Vider

                        </a>

                    <?php endif; ?>


                </div>


                <?php if (empty($_SESSION["panier"])): ?>


                    <!-- PANIER VIDE -->

                    <div class="panier-vide">


                        <i class="bi bi-cart-x"></i>


                        <p class="mt-2 mb-0">

                            Votre panier est vide.

                        </p>


                        <small>

                            Cliquez sur « Commander »
                            pour ajouter un produit.

                        </small>


                    </div>


                <?php else: ?>


                    <!-- PRODUITS DU PANIER -->

                    <?php foreach (
                        $_SESSION["panier"]
                        as $article
                    ): ?>


                        <?php

                        $imagePanier =
                            !empty($article["image"])
                            ? "../img/" . $article["image"]
                            : "../img/default.jpg";


                        $sousTotal =
                            (float) $article["prix"]
                            *
                            (int) $article["quantite"];

                        ?>


                        <div class="panier-item">


                            <img
                                src="<?= htmlspecialchars($imagePanier) ?>"
                                alt="<?= htmlspecialchars($article["nom_produit"]) ?>"
                            >


                            <div class="panier-item-info">


                                <strong>

                                    <?= htmlspecialchars(
                                        $article["nom_produit"]
                                    ) ?>

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


                                <!-- QUANTITÉ -->

                                <div class="quantite">


                                    <a
                                        href="produit.php?moins=<?= (int) $article["id_produit"] ?>"
                                        class="btn-moins"
                                    >

                                        −

                                    </a>


                                    <span>

                                        <?= (int) $article["quantite"] ?>

                                    </span>


                                    <a
                                        href="produit.php?plus=<?= (int) $article["id_produit"] ?>"
                                        class="btn-plus"
                                    >

                                        +

                                    </a>


                                </div>


                            </div>


                            <!-- SUPPRIMER -->

                            <div>

                                <a
                                    href="produit.php?supprimer=<?= (int) $article["id_produit"] ?>"
                                    class="supprimer"
                                    title="Supprimer"
                                >

                                    <i class="bi bi-trash"></i>

                                </a>

                            </div>


                        </div>


                    <?php endforeach; ?>


                    <!-- TOTAL -->

                    <div class="panier-total">


                        <span>

                            Total :

                        </span>


                        <span class="text-success">

                            <?= number_format(
                                $totalPanier,
                                0,
                                ",",
                                " "
                            ) ?>

                            FCFA

                        </span>


                    </div>


                    <!-- =================================================
                         VALIDATION
                    ================================================= -->

                    <div class="client-box">


                        <h4>

                            <i class="bi bi-person-check-fill"></i>

                            Client connecté

                        </h4>


                        <p class="text-muted small">

                            Vos informations enregistrées
                            seront automatiquement utilisées
                            pour cette commande.

                        </p>


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
<div class="message-zone">


    <?php if ($messageSucces !== ""): ?>


        <div class="alert alert-success">


            <i class="bi bi-check-circle-fill"></i>


            <strong>

                <?= htmlspecialchars(
                    $messageSucces
                ) ?>

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

                <i class="bi bi-shop"></i>

                Continuer mes achats

            </a>


        </div>


    <?php endif; ?>


    <?php if ($messageErreur !== ""): ?>


        <div class="alert alert-danger">


            <i class="bi bi-exclamation-triangle-fill"></i>


            <?= htmlspecialchars(
                $messageErreur
            ) ?>


        </div>


    <?php endif; ?>


   </div> 
 </div>


<section class="produits-header">


    <h1>

        Nos produits

    </h1>


    <p>

        Découvrez nos produits de qualité
        pour l'alimentation animale.

    </p>


</section>


<section class="produits-container">


<?php if (!empty($produits)): ?>


    <?php foreach ($produits as $produit): ?>


        <?php

        $idProduit =
            (int) ($produit["id_produit"] ?? 0);


        $nomProduit =
            $produit["nom_produit"]
            ?? "Produit sans nom";


        $description =
            $produit["description"]
            ?? "";


        $prix =
            (float) ($produit["prix"] ?? 0);


        $stock =
            (int) ($produit["stock"] ?? 0);


        $image =
            $produit["image"]
            ?? "";


        $categorie =
            $produit["nom_categorie"]
            ?? "Non classé";

        ?>


        <article class="produit-card">


            <!-- IMAGE -->

            <div class="image-container">


                <?php if ($image !== ""): ?>


                    <img
                        src="../img/<?= htmlspecialchars($image) ?>"
                        alt="<?= htmlspecialchars($nomProduit) ?>"
                    >


                <?php else: ?>


                    <img
                        src="../img/default.jpg"
                        alt="Produit"
                    >


                <?php endif; ?>


            </div>


            <!-- INFORMATIONS -->

            <div class="produit-info">


                <h2>

                    <?= htmlspecialchars(
                        $nomProduit
                    ) ?>

                </h2>


                <p class="description">

                    <?= htmlspecialchars(
                        $description
                    ) ?>

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


                <!-- STOCK -->

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


                <!-- ACTIONS -->

                <div class="actions">


                    <?php if ($stock > 0): ?>


                        <a
                            href="produit.php?ajouter=<?= $idProduit ?>"
                            class="btn-commander"
                        >

                            <i class="bi bi-cart-fill"></i>

                            Commander

                        </a>


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

                                <?= htmlspecialchars(
                                    $nomProduit
                                ) ?>

                            </p>


                            <p>

                                <strong>
                                    Catégorie :
                                </strong>

                                <?= htmlspecialchars(
                                    $categorie
                                ) ?>

                            </p>


                            <p>

                                <strong>
                                    Description :
                                </strong>

                                <?= htmlspecialchars(
                                    $description
                                ) ?>

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


        <!-- LOGO -->

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


        <!-- NAVIGATION -->

        <div class="col-lg-2 col-md-6 mb-4">


            <h5 class="text-warning fw-bold">

                Navigation

            </h5>


            <ul class="nav flex-column">


                <li class="nav-item">

                    <a
                        href="index.php"
                        class="nav-link text-light p-1"
                    >
                        Accueil
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        href="a propos.php"
                        class="nav-link text-light p-1"
                    >
                        À propos
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        href="services.php"
                        class="nav-link text-light p-1"
                    >
                        Nos services
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        href="produit.php"
                        class="nav-link text-light p-1"
                    >
                        Produits
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        href="galerie.php"
                        class="nav-link text-light p-1"
                    >
                        Galerie
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        href="contact.php"
                        class="nav-link text-light p-1"
                    >
                        Contact
                    </a>

                </li>


            </ul>


        </div>


        <!-- CONTACT -->

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


        <!-- HORAIRES -->

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


        <!-- RÉSEAUX -->

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


<!-- =====================================================
     BOOTSTRAP
===================================================== -->

<script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>


<!-- =====================================================
     SCRIPT PANIER
===================================================== -->

<script>

const panierButton =
    document.getElementById("panierButton");

const panierNav =
    document.getElementById("panierNav");

const panierMenu =
    document.getElementById("panierMenu");


/* Ouvrir / fermer le panier */

panierButton.addEventListener(
    "click",
    function(event) {

        event.stopPropagation();

        panierNav.classList.toggle("open");

    }
);


/* Empêcher fermeture en cliquant dans le panier */

panierMenu.addEventListener(
    "click",
    function(event) {

        event.stopPropagation();

    }
);


/* Fermer en cliquant ailleurs */

document.addEventListener(
    "click",
    function() {

        panierNav.classList.remove("open");

    }
);

</script>


</body>

</html>

