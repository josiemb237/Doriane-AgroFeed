<?php

session_start();

require_once "database.php";
require_once "mail.php";

$message = "";
$typeMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST["nom"] ?? "");
    $prenom = trim($_POST["prenom"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $adresse = trim($_POST["adresse"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirmation = $_POST["confirmation"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | Vérification des champs
    |--------------------------------------------------------------------------
    */

    if (
        $nom === "" ||
        $prenom === "" ||
        $telephone === "" ||
        $email === "" ||
        $adresse === "" ||
        $password === "" ||
        $confirmation === ""
    ) {

        $message = "Veuillez remplir tous les champs.";
        $typeMessage = "error";

    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Adresse e-mail invalide.";
        $typeMessage = "error";

    }

    elseif (strlen($password) < 8) {

        $message =
            "Le mot de passe doit contenir au moins 8 caractères.";

        $typeMessage = "error";

    }

    elseif ($password !== $confirmation) {

        $message =
            "Les mots de passe ne correspondent pas.";

        $typeMessage = "error";

    }

    else {

        /*
        |--------------------------------------------------------------------------
        | Vérifier si l'e-mail existe déjà
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare(
            "SELECT id_utilisateur
             FROM utilisateurs
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        $existe = $stmt->fetch();


        if ($existe) {

            $message =
                "Cette adresse e-mail est déjà utilisée.";

            $typeMessage = "error";

        }

        else {

            /*
            |--------------------------------------------------------------------------
            | Génération du code OTP
            |--------------------------------------------------------------------------
            */

            $otp = random_int(100000, 999999);

            // 10 minutes
            $expiration = time() + 600;


            /*
            |--------------------------------------------------------------------------
            | Hachage du mot de passe
            |--------------------------------------------------------------------------
            */

            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /*
            |--------------------------------------------------------------------------
            | Stocker temporairement les informations
            |--------------------------------------------------------------------------
            */

            $_SESSION["inscription"] = [

                "nom" => $nom,

                "prenom" => $prenom,

                "telephone" => $telephone,

                "email" => $email,

                "adresse" => $adresse,

                "mot_de_passe" => $hash,

                "otp" => (string)$otp,

                "expiration" => $expiration,

                "role" => "admin"
            ];


            /*
            |--------------------------------------------------------------------------
            | Envoyer le code OTP
            |--------------------------------------------------------------------------
            */

            $envoye = envoyerOTP(
                $email,
                $otp,
                "Code de vérification - Doriane AgroFeed"
            );


            if ($envoye) {

                header("Location: verification.php");
                exit;

            }

            else {

                unset($_SESSION["inscription"]);

                $message =
                    "Impossible d'envoyer le code de vérification. Vérifiez la configuration de votre e-mail.";

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

<title>Inscription administrateur</title>

<link
    rel="stylesheet"
    href="auth.css"
>

</head>

<body>

<div class="auth-container">

<div class="auth-card">


<div class="logo">

<img
    src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg"
    alt="Logo"
    width="50"
>

</div>


<h1>
    Créer un compte
</h1>


<p class="subtitle">
    Inscription administrateur
</p>


<?php if ($message !== ""): ?>

<div class="message <?php echo htmlspecialchars($typeMessage); ?>">

<?php

echo htmlspecialchars(
    $message,
    ENT_QUOTES,
    "UTF-8"
);

?>

</div>

<?php endif; ?>


<form method="POST">


<div class="row">


<div class="form-group">

<label>
    Nom
</label>

<input
    type="text"
    name="nom"
    value="<?php
        echo htmlspecialchars(
            $_POST["nom"] ?? "",
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"
    required
>

</div>


<div class="form-group">

<label>
    Prénom
</label>

<input
    type="text"
    name="prenom"
    value="<?php
        echo htmlspecialchars(
            $_POST["prenom"] ?? "",
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"
    required
>

</div>


</div>


<div class="form-group">

<label>
    Téléphone
</label>

<input
    type="tel"
    name="telephone"
    value="<?php
        echo htmlspecialchars(
            $_POST["telephone"] ?? "",
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"
    required
>

</div>


<div class="form-group">

<label>
    E-mail
</label>

<input
    type="email"
    name="email"
    value="<?php
        echo htmlspecialchars(
            $_POST["email"] ?? "",
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"
    required
>

</div>


<div class="form-group">

<label>
    Adresse
</label>

<input
    type="text"
    name="adresse"
    value="<?php
        echo htmlspecialchars(
            $_POST["adresse"] ?? "",
            ENT_QUOTES,
            "UTF-8"
        );
    ?>"
    required
>

</div>


<div class="form-group">

<label>
    Mot de passe
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
    Confirmation du mot de passe
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
    Créer mon compte
</button>


</form>


<div class="bottom">

Déjà un compte ?

<a href="connexion.php">
    Se connecter
</a>

</div>


</div>

</div>

</body>

</html>