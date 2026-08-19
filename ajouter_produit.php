<?php

require_once "connexions.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST["nom_produit"]);
    $description = trim($_POST["description"]);
    $prix = $_POST["prix"];
    $stock = $_POST["stock"];
    $categorie = $_POST["id_categorie"];

    $image = "";
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
        INSERT INTO produit
        (
            id_categorie,
            nom_produit,
            description,
            prix,
            stock,
            image
        )
        VALUES
        (
            :categorie,
            :nom,
            :description,
            :prix,
            :stock,
            :image
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        ":categorie" => $categorie,

        ":nom" => $nom,

        ":description" => $description,

        ":prix" => $prix,

        ":stock" => $stock,

        ":image" => $image

    ]);

    header("Location: produits.php");

    exit;
}

/* Récupérer les catégories */

$categories =
    $pdo->query(
        "SELECT * FROM categorie ORDER BY nom_categorie"
    )->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<title>Ajouter un produit</title>
<link  rel="stylesheet" href="ss.css">
<link
rel="stylesheet"
href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

</head>

    <body>

    <div class="container mt-5">

    <h1>Ajouter un produit</h1>

    <form
    method="POST"
    enctype="multipart/form-data"
    class="mt-4">

    <div class="mb-3">

    <label class="form-label">
    Nom du produit
    </label>

    <input
    type="text"
    name="nom_produit"
    class="form-control"
    required>

    </div>

    <div class="mb-3">

    <label class="form-label">
    Catégorie
    </label>

    <select
    name="id_categorie"
    class="form-select"
    required>

    <option value="">
    Choisir une catégorie
    </option>

    <?php foreach ($categories as $categorie): ?>

    <option
    value="<?= $categorie['id_categorie'] ?>">

    <?= htmlspecialchars(
    $categorie['nom_categorie']
    ) ?>

    </option>

    <?php endforeach; ?>

    </select>

    </div>

    <div class="mb-3">

    <label class="form-label">
    Description
    </label>

    <textarea
    name="description"
    class="form-control"
    rows="4">
    </textarea>

    </div>

    <div class="mb-3">

    <label class="form-label">
    Prix
    </label>

    <input
    type="number"
    name="prix"
    class="form-control"
    min="0"
    required>

    </div>

    <div class="mb-3">

    <label class="form-label">
    Stock
    </label>

    <input
    type="number"
    name="stock"
    class="form-control"
    min="0"
    required>

    </div>

    <div class="mb-3">

    <label class="form-label">
    Image
    </label>

    <input
    type="file"
    name="image"
    class="form-control"
    accept=".jpg,.jpeg,.png,.webp">

    </div>

    <button
    type="submit"
    class="btn btn-success">

    <i class="bi bi-check-circle"></i>

    Enregistrer

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
