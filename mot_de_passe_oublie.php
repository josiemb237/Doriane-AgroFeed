<?php

session_start();

require_once "database.php";
require_once "mail.php";

$message = "";
$typeMessage = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");


    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Veuillez saisir une adresse e-mail valide.";

        $typeMessage = "error";

    }

    else {

        $stmt = $pdo->prepare(
            "SELECT *
             FROM utilisateurs
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();


        if (!$user) {

            $message =
                "Aucun compte associé à cette adresse.";

            $typeMessage = "error";

        }

        else {

            $otp = random_int(
                100000,
                999999
            );

            $_SESSION["reset_password"] = [

                "id" =>
                    $user["id_utilisateur"],

                "email" =>
                    $user["email"],

                "otp" =>
                    $otp,

                "expiration" =>
                    time() + 600
            ];


            if (
                envoyerOTP(
                    $email,
                    $otp,
                    "Réinitialisation du mot de passe"
                )
            ) {

                header(
                    "Location: verification_reset.php"
                );

                exit;

            }

            else {

                unset(
                    $_SESSION["reset_password"]
                );

                $message =
                    "Impossible d'envoyer l'e-mail.";

                $typeMessage = "error";
            }
        }
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

<title>Mot de passe oublié</title>

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
    Mot de passe oublié
</h1>

<p class="subtitle">
    Recevez un code par e-mail
</p>


<?php if ($message !== ""): ?>

<div class="message <?php echo $typeMessage; ?>">

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>


<form method="POST">

<div class="form-group">

<label>
    Adresse e-mail
</label>

<input
    type="email"
    name="email"
    required
>

</div>


<button
    type="submit"
    class="btn"
>
    Envoyer le code
</button>

</form>


<div class="bottom">

<a href="connexion.php">
    Retour à la connexion
</a>

</div>

</div>

</div>

</body>

</html>