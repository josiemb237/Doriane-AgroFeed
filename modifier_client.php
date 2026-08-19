<?php

session_start();

require_once "database.php";

// if (
//     !isset($_SESSION["connecte"]) ||
//     $_SESSION["connecte"] !== true ||
//     ($_SESSION["role"] ?? "") !== "admin"
// ) {
//     header("Location: connexion.php");
//     exit;
// }

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: clients.php");
    exit;
}


$stmt = $pdo->prepare("
    SELECT *
    FROM utilisateurs
    WHERE id_utilisateur = ?
    AND role = 'client'
    LIMIT 1
");

$stmt->execute([$id]);

$client = $stmt->fetch();


if (!$client) {
    header("Location: clients.php");
    exit;
}


$message = "";
$typeMessage = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST["nom"] ?? "");
    $prenom = trim($_POST["prenom"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $adresse = trim($_POST["adresse"] ?? "");
    $statut = $_POST["statut"] ?? "actif";


    if (
        $nom === "" ||
        $prenom === "" ||
        $telephone === "" ||
        $email === "" ||
        $adresse === ""
    ) {

        $message = "Veuillez remplir tous les champs.";

        $typeMessage = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Adresse e-mail invalide.";

        $typeMessage = "error";

    } else {

        $stmt = $pdo->prepare("
            SELECT id_utilisateur
            FROM utilisateurs
            WHERE email = ?
            AND id_utilisateur != ?
            LIMIT 1
        ");

        $stmt->execute([
            $email,
            $id
        ]);


        if ($stmt->fetch()) {

            $message =
                "Cette adresse e-mail est déjà utilisée.";

            $typeMessage = "error";

        } else {

            $stmt = $pdo->prepare("
                UPDATE utilisateurs

                SET
                    nom = ?,
                    prenom = ?,
                    telephone = ?,
                    email = ?,
                    adresse = ?,
                    statut = ?

                WHERE id_utilisateur = ?

                AND role = 'client'
            ");


            $stmt->execute([
                $nom,
                $prenom,
                $telephone,
                $email,
                $adresse,
                $statut,
                $id
            ]);


            header("Location: clients.php");

            exit;
        }
    }
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Modifier un client</title>

<link rel="stylesheet" href="auth.css">

</head>

<body>


<div class="auth-container">

<div class="auth-card">


<h1>Modifier le client</h1>

<p class="subtitle">
Modifier les informations du client
</p>


<?php if ($message !== ""): ?>

<div class="message <?= $typeMessage ?>">

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>


<form method="POST">


<div class="form-group">

<label>Nom</label>

<input
    type="text"
    name="nom"
    value="<?= htmlspecialchars($client["nom"]) ?>"
    required
>

</div>


<div class="form-group">

<label>Prénom</label>

<input
    type="text"
    name="prenom"
    value="<?= htmlspecialchars($client["prenom"]) ?>"
    required
>

</div>


<div class="form-group">

<label>Téléphone</label>

<input
    type="tel"
    name="telephone"
    value="<?= htmlspecialchars($client["telephone"]) ?>"
    required
>

</div>


<div class="form-group">

<label>E-mail</label>

<input
    type="email"
    name="email"
    value="<?= htmlspecialchars($client["email"]) ?>"
    required
>

</div>


<div class="form-group">

<label>Adresse</label>

<input
    type="text"
    name="adresse"
    value="<?= htmlspecialchars($client["adresse"]) ?>"
    required
>

</div>


<div class="form-group">

<label>Statut</label>

<select name="statut" class="form-control">

<option
    value="actif"
    <?= $client["statut"] === "actif" ? "selected" : "" ?>
>
    Actif
</option>

<option
    value="inactif"
    <?= $client["statut"] === "inactif" ? "selected" : "" ?>
>
    Inactif
</option>

</select>

</div>


<button
    type="submit"
    class="btn"
>
    Enregistrer les modifications
</button>


</form>


<div class="bottom">

<a href="clients.php">
    ← Retour à la liste
</a>

</div>


</div>

</div>


</body>

</html>