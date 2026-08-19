<?php

session_start();

require_once "database.php";


if (
    !isset($_SESSION["reset_password"]) ||
    !isset($_SESSION["reset_password"]["verified"]) ||
    $_SESSION["reset_password"]["verified"] !== true
) {

    header(
        "Location: mot_de_passe_oublie.php"
    );

    exit;
}


$data =
    $_SESSION["reset_password"];


$message = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password =
        $_POST["password"] ?? "";

    $confirmation =
        $_POST["confirmation"] ?? "";


    if (strlen($password) < 8) {

        $message =
            "Le mot de passe doit contenir au moins 8 caractères.";

    }

    elseif ($password !== $confirmation) {

        $message =
            "Les mots de passe ne correspondent pas.";

    }

    else {

        $hash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        $stmt = $pdo->prepare(
            "UPDATE utilisateurs
             SET mot_de_passe = ?
             WHERE id_utilisateur = ?"
        );


        $stmt->execute([

            $hash,

            $data["id"]
        ]);


        unset(
            $_SESSION["reset_password"]
        );


        header(
            "Location: connexion.php"
        );

        exit;
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

<title>Nouveau mot de passe</title>

<link
    rel="stylesheet"
    href="auth.css"
>

</head>

<body>

<div class="auth-container">

<div class="auth-card">

<div class="logo">
      <img src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg" alt="logo" width="50px">
</div>

<h1>
    Nouveau mot de passe
</h1>

<p class="subtitle">
    Choisissez votre nouveau mot de passe
</p>


<?php if ($message !== ""): ?>

<div class="message error">

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>


<form method="POST">

<div class="form-group">

<label>
    Nouveau mot de passe
</label>

<input
    type="password"
    name="password"
    minlength="8"
    required
>

</div>


<div class="form-group">

<label>
    Confirmation
</label>

<input
    type="password"
    name="confirmation"
    minlength="8"
    required
>

</div>


<button
    type="submit"
    class="btn"
>
    Modifier le mot de passe
</button>

</form>

</div>

</div>

</body>

</html>