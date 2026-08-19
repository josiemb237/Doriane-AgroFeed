<?php

session_start();

require_once "database.php";

$message = "";
$typeMessage = "";


/*
|--------------------------------------------------------------------------
| Vérifier qu'une inscription est en cours
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["inscription"])) {

    header("Location: connexion.php");
    exit;
}


$data = $_SESSION["inscription"];


/*
|--------------------------------------------------------------------------
| Vérification du code OTP
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $otp = trim($_POST["otp"] ?? "");


    /*
    |--------------------------------------------------------------------------
    | Champ vide
    |--------------------------------------------------------------------------
    */

    if ($otp === "") {

        $message = "Veuillez entrer le code.";
        $typeMessage = "error";

    }


    /*
    |--------------------------------------------------------------------------
    | Vérifier format
    |--------------------------------------------------------------------------
    */

    elseif (
        !ctype_digit($otp) ||
        strlen($otp) !== 6
    ) {

        $message =
            "Le code doit contenir exactement 6 chiffres.";

        $typeMessage = "error";

    }


    /*
    |--------------------------------------------------------------------------
    | Vérifier expiration
    |--------------------------------------------------------------------------
    */

    elseif (time() > $data["expiration"]) {

        $message =
            "Le code a expiré. Veuillez recommencer l'inscription.";

        $typeMessage = "error";

        unset($_SESSION["inscription"]);

    }


    /*
    |--------------------------------------------------------------------------
    | Vérifier le code
    |--------------------------------------------------------------------------
    */

    elseif ($otp !== $data["otp"]) {

        $message =
            "Code de vérification incorrect.";

        $typeMessage = "error";

    }


    /*
    |--------------------------------------------------------------------------
    | Code correct
    |--------------------------------------------------------------------------
    */

    else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Vérifier encore une fois l'e-mail
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                "SELECT id_utilisateur
                 FROM utilisateurs
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->execute([
                $data["email"]
            ]);

            if ($stmt->fetch()) {

                $message =
                    "Cette adresse e-mail est déjà utilisée.";

                $typeMessage = "error";

                unset($_SESSION["inscription"]);

            }

            else {

                /*
                |--------------------------------------------------------------------------
                | Insérer dans utilisateurs
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare(
                    "INSERT INTO utilisateurs
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
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'actif'
                    )"
                );


                $stmt->execute([

                    $data["nom"],

                    $data["prenom"],

                    $data["telephone"],

                    $data["email"],

                    $data["adresse"],

                    $data["mot_de_passe"],

                    $data["role"]

                ]);


                /*
                |--------------------------------------------------------------------------
                | Récupérer l'identifiant
                |--------------------------------------------------------------------------
                */

                $id = $pdo->lastInsertId();


                /*
                |--------------------------------------------------------------------------
                | Supprimer les données temporaires
                |--------------------------------------------------------------------------
                */

                unset($_SESSION["inscription"]);


                /*
                |--------------------------------------------------------------------------
                | Ne PAS connecter automatiquement
                |--------------------------------------------------------------------------
                */

                /*
                IMPORTANT :
                On ne crée PAS $_SESSION["connecte"] ici.
                L'utilisateur doit d'abord passer par connexion.php.
                */


                /*
                |--------------------------------------------------------------------------
                | Redirection vers connexion
                |--------------------------------------------------------------------------
                */

                header(
                    "Location: connexion.php?inscription=success"
                );

                exit;
            }

        } catch (PDOException $e) {

            $message =
                "Erreur lors de la création du compte : "
                . $e->getMessage();

            $typeMessage = "error";
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

<title>Vérification du compte</title>

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
    Vérification
</h1>


<p class="subtitle">

Code envoyé à :

<br>

<strong>

<?php

echo htmlspecialchars(
    $data["email"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</strong>

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


<div class="form-group">

<label>
    Code OTP
</label>

<input
    type="text"
    name="otp"
    maxlength="6"
    inputmode="numeric"
    placeholder="123456"
    required
>

</div>


<button
    type="submit"
    class="btn"
>
    Vérifier mon compte
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