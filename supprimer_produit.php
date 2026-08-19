<?php

require_once "connexions;l';'/.php";

if (!isset($_GET["id"])) {

    header("Location: produits.php");

    exit;
}

$id = intval($_GET["id"]);


/* Récupérer l'image */

$stmt = $pdo->prepare(
    "SELECT image FROM produit WHERE id_produit = ?"
);

$stmt->execute([$id]);

$produit =
    $stmt->fetch(PDO::FETCH_ASSOC);


if (!$produit) {

    header("Location: produits.php");

    exit;
}


/* Supprimer le produit */

$stmt = $pdo->prepare(
    "DELETE FROM produit WHERE id_produit = ?"
);

$stmt->execute([$id]);


/* Supprimer l'image */

if (
    !empty($produit["image"]) &&
    file_exists(
        "../img/" . $produit["image"]
    )
) {

    unlink(
        "../img/" . $produit["image"]
    );
}


header("Location: produits.php");

exit;

?>