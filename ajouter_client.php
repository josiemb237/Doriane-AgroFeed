<?php

session_start();

require_once "database.php";

$message = "";
$typeMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST["nom"] ?? "");
    $prenom = trim($_POST["prenom"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $adresse = trim($_POST["adresse"] ?? "");
    $password = $_POST["password"] ?? "";

    if (
        $nom === "" ||
        $prenom === "" ||
        $telephone === "" ||
        $email === "" ||
        $adresse === "" ||
        $password === ""
    ) {

        $message = "Veuillez remplir tous les champs.";
        $typeMessage = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Adresse e-mail invalide.";
        $typeMessage = "error";

    } elseif (strlen($password) < 8) {

        $message = "Le mot de passe doit contenir au moins 8 caractères.";
        $typeMessage = "error";

    } else {

        $stmt = $pdo->prepare("
            SELECT id_utilisateur
            FROM utilisateurs
            WHERE email = ?
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $message = "Cette adresse e-mail existe déjà.";
            $typeMessage = "error";

        } else {

            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO utilisateurs
                (
                    nom,
                    prenom,
                    telephone,
                    email,
                    adresse,
                    mot_de_passe,
                    role,
                    statut
                )
                VALUES (?, ?, ?, ?, ?, ?, 'client', 'actif')
            ");

            $stmt->execute([
                $nom,
                $prenom,
                $telephone,
                $email,
                $adresse,
                $hash
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

<title>Ajouter un client</title>

<link rel="stylesheet" href="auth.css">

</head>

<body>

<div class="auth-container">

<div class="auth-card">

<h1>Ajouter un client</h1>

<p class="subtitle">
Créer un nouveau compte client
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
    required
>

</div>

<div class="form-group">

<label>Prénom</label>

<input
    type="text"
    name="prenom"
    required
>

</div>

<div class="form-group">

<label>Téléphone</label>

<input
    type="tel"
    name="telephone"
    required
>

</div>

<div class="form-group">

<label>E-mail</label>

<input
    type="email"
    name="email"
    required
>

</div>

<div class="form-group">

<label>Adresse</label>

<input
    type="text"
    name="adresse"
    required
>

</div>

<div class="form-group">

<label>Mot de passe</label>

<input
    type="password"
    name="password"
    minlength="8"
    required
>

</div>

<button type="submit" class="btn">
    Ajouter le client
</button>

</form>

<div class="bottom">

<a href="clients.php">
    Retour aux clients
</a>

</div>

</div>

</div>

</body>

</html>