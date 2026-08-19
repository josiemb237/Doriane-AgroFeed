<?php

require_once "connexions.php";

if (!isset($_GET["id"])) {

    header("Location: produits.php");

    exit;
}

$id = intval($_GET["id"]);


/* Récupérer le produit */

$stmt = $pdo->prepare(
    "SELECT * FROM produit WHERE id_produit = ?"
);

$stmt->execute([$id]);

$produit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produit) {

    die("Produit introuvable.");

}


/* Modification */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom =
        trim($_POST["nom_produit"]);

    $description =
        trim($_POST["description"]);

    $prix =
        $_POST["prix"];

    $stock =
        $_POST["stock"];

    $categorie =
        $_POST["id_categorie"];

    $image =
        $produit["image"];


        $image = "";

        if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {

            $extension = strtolower(
                pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION)
            );

            $extensionsAutorisees = ["jpg", "jpeg", "png", "webp"];

            if (!in_array($extension, $extensionsAutorisees)) {
                die("Format d'image non autorisé.");
            }

            $nouveauNom = uniqid("produit_", true) . "." . $extension;

            $destination = "../img/" . $nouveauNom;

            if (move_uploaded_file(
                $_FILES["image"]["tmp_name"],
                $destination
            )) {
                $image = $nouveauNom;
            } else {
                die("Erreur lors de l'enregistrement de l'image.");
            }
        }


    $sql = "

        UPDATE produit

        SET

        id_categorie = :categorie,

        nom_produit = :nom,

        description = :description,

        prix = :prix,

        stock = :stock,

        image = :image

        WHERE id_produit = :id

    ";


    $stmt =
        $pdo->prepare($sql);


    $stmt->execute([

        ":categorie" => $categorie,

        ":nom" => $nom,

        ":description" => $description,

        ":prix" => $prix,

        ":stock" => $stock,

        ":image" => $image,

        ":id" => $id

    ]);


    header("Location: produits.php");

    exit;
}


/* Catégories */

$categories =
    $pdo->query(
        "SELECT * FROM categorie"
    )->fetchAll(PDO::FETCH_ASSOC);

?>

 <!DOCTYPE html>

 <html lang="fr">

 <head>

 <meta charset="UTF-8">

 <title>Modifier produit</title>
 <link  rel="stylesheet" href="ss.css">    
 <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
 </head>

 <body >

    <div class="container mt-5" style="">

        <h1>Modifier le produit</h1>

        <form
        method="POST"
        enctype="multipart/form-data"
        class="mt-4">


        <div class="mb-3">

        <label>
        Nom du produit
        </label>

        <input
        type="text"
        name="nom_produit"
        class="form-control"
        value="<?= htmlspecialchars(
        $produit["nom_produit"]
        ) ?>"
        required>

        </div>


        <div class="mb-3">

        <label>
        Catégorie
        </label>

        <select
        name="id_categorie"
        class="form-select"
        required>

        <?php foreach ($categories as $categorie): ?>

        <option
        value="<?= $categorie["id_categorie"] ?>"

        <?= $categorie["id_categorie"]
        ==
        $produit["id_categorie"]
        ? "selected"
        : "" ?>>

        <?= htmlspecialchars(
        $categorie["nom_categorie"]
        ) ?>

        </option>

        <?php endforeach; ?>

        </select>

        </div>


        <div class="mb-3">

        <label>
        Description
        </label>

        <textarea
        name="description"
        class="form-control"
        rows="4"><?= htmlspecialchars(
        $produit["description"]
        ) ?></textarea>

        </div>


        <div class="mb-3">

        <label>
        Prix
        </label>

        <input
        type="number"
        name="prix"
        class="form-control"
        value="<?= $produit["prix"] ?>"
        required>

        </div>


        <div class="mb-3">

        <label>
        Stock
        </label>

        <input
        type="number"
        name="stock"
        class="form-control"
        value="<?= $produit["stock"] ?>"
        required>

        </div>


        <div class="mb-3">

        <label>
        Nouvelle image
        </label>

        <input
        type="file"
        name="image"
        class="form-control"
        accept=".jpg,.jpeg,.png,.webp">

        </div>


        <?php if (!empty($produit["image"])): ?>

        <img
        src="../img/<?= htmlspecialchars(
        $produit["image"]
        ) ?>"
        width="120"
        class="mb-3">

        <?php endif; ?>


        <br>


        <button
        type="submit"
        class="btn btn-primary">

        Modifier

        </button>


        <a
        href="produits.php"
        class="btn btn-secondary">

        Annuler

        </a>

        </form>

     </div>

 </body>

 </html>