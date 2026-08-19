<?php

session_start();

require_once "database.php";
require_once "mail.php";
$message = "";
$typeMessage = "";


/*
|--------------------------------------------------------------------------
| TRAITEMENT DE LA CONNEXION
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | Vérification des champs
    |--------------------------------------------------------------------------
    */

    if ($email === "" || $password === "") {

        $message = "Veuillez remplir tous les champs.";
        $typeMessage = "error";

    }


    /*
    |--------------------------------------------------------------------------
    | Vérification de l'adresse e-mail
    |--------------------------------------------------------------------------
    */

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Adresse e-mail invalide.";
        $typeMessage = "error";

    }


    else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Rechercher l'utilisateur dans la table utilisateurs
            |--------------------------------------------------------------------------
            */

            $sql = "
                SELECT
                    id_utilisateur,
                    nom,
                    prenom,
                    telephone,
                    email,
                    adresse,
                    mot_de_passe,
                    role,
                    statut
                FROM utilisateurs
                WHERE email = ?
                LIMIT 1
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([$email]);

            $utilisateur = $stmt->fetch();


            /*
            |--------------------------------------------------------------------------
            | Aucun compte trouvé
            |--------------------------------------------------------------------------
            */

            if (!$utilisateur) {

                $message =
                    "Adresse e-mail ou mot de passe incorrect.";

                $typeMessage = "error";

            }


            /*
            |--------------------------------------------------------------------------
            | Compte désactivé
            |--------------------------------------------------------------------------
            */

            elseif ($utilisateur["statut"] !== "actif") {

                $message =
                    "Votre compte est désactivé.";

                $typeMessage = "error";

            }


            /*
            |--------------------------------------------------------------------------
            | Vérification du mot de passe
            |--------------------------------------------------------------------------
            */

            elseif (
                !password_verify(
                    $password,
                    $utilisateur["mot_de_passe"]
                )
            ) {

                $message =
                    "Adresse e-mail ou mot de passe incorrect.";

                $typeMessage = "error";

            }


            /*
            |--------------------------------------------------------------------------
            | Connexion réussie
            |--------------------------------------------------------------------------
            */

            else {

                /*
                | Sécuriser la session
                */

                session_regenerate_id(true);


                /*
                | Enregistrer les informations de l'utilisateur
                */

                $_SESSION["connecte"] = true;

                $_SESSION["id_utilisateur"] =
                    $utilisateur["id_utilisateur"];

                $_SESSION["nom"] =
                    $utilisateur["nom"];

                $_SESSION["prenom"] =
                    $utilisateur["prenom"];

                $_SESSION["telephone"] =
                    $utilisateur["telephone"];

                $_SESSION["email"] =
                    $utilisateur["email"];

                $_SESSION["adresse"] =
                    $utilisateur["adresse"];

                $_SESSION["role"] =
                    $utilisateur["role"];

                $_SESSION["statut"] =
                    $utilisateur["statut"];


                /*
                |--------------------------------------------------------------------------
                | Si ADMIN
                |--------------------------------------------------------------------------
                */

                if ($utilisateur["role"] === "admin") {

                    $_SESSION["admin_id"] =
                        $utilisateur["id_utilisateur"];

                    header(
                        "Location: dashboard.php"
                    );

                    exit;
                }


                /*
                |--------------------------------------------------------------------------
                | Si CLIENT
                |--------------------------------------------------------------------------
                */

                elseif ($utilisateur["role"] === "client") {

                    $_SESSION["client_id"] =
                        $utilisateur["id_utilisateur"];

                    header(
                        "Location: index.php"
                    );

                    exit;
                }


                /*
                |--------------------------------------------------------------------------
                | Rôle inconnu
                |--------------------------------------------------------------------------
                */

                else {

                    session_unset();
                    session_destroy();

                    $message =
                        "Le rôle de votre compte est invalide.";

                    $typeMessage = "error";
                }
            }

        }

        catch (PDOException $e) {

            $message =
                "Erreur SQL : " . $e->getMessage();

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

    <title>
        Connexion - Provenderie
    </title>

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
            Connexion
        </h1>


        <p class="subtitle">
            Accédez à votre compte
        </p>


        <?php if ($message !== ""): ?>

            <div
                class="message <?php echo $typeMessage; ?>"
            >

                <?php

                echo htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                );

                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
            autocomplete="off">

            <div class="form-group">

                <label for="email">
                    E-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="exemple@gmail.com"
                    value="<?php

                    echo htmlspecialchars(
                        $_POST["email"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );

                    ?>"
                    required>
            </div>

            <div class="form-group">

                <label for="password">
                    Mot de passe
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Votre mot de passe"
                    required
                >

            </div>


            <!-- MOT DE PASSE OUBLIE -->

            <div class="forgot">

                <a href="mot_de_passe_oublie.php">
                    Mot de passe oublié ?
                </a>

            </div>



            <button
                type="submit"
                class="btn"
            >
                Se connecter
            </button>


        </form>


        <div class="bottom">

            Pas encore de compte ?

            <a href="inscription_client.php">
                S'inscrire
            </a>

        </div>


    </div>


</div>


</body>

</html>