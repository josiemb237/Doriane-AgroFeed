<?php

session_start();

if (
    !isset($_SESSION["reset_password"])
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

    $otp = trim(
        $_POST["otp"] ?? ""
    );


    if (
        !ctype_digit($otp) ||
        strlen($otp) !== 6
    ) {

        $message =
            "Code invalide.";

    }

    elseif (
        time() > $data["expiration"]
    ) {

        unset(
            $_SESSION["reset_password"]
        );

        $message =
            "Le code a expiré.";

    }

    elseif (
        $otp !== (string)$data["otp"]
    ) {

        $message =
            "Code incorrect.";

    }

    else {

        $_SESSION["reset_password"]["verified"]
            = true;


        header(
            "Location: nouveau_mot_de_passe.php"
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

<title>Vérification</title>

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
    Vérification
</h1>

<p class="subtitle">

Code envoyé à

<strong>
<?php echo htmlspecialchars($data["email"]); ?>
</strong>

</p>


<?php if ($message !== ""): ?>

<div class="message error">

<?php echo htmlspecialchars($message); ?>

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
    required
>

</div>


<button
    type="submit"
    class="btn"
>
    Vérifier
</button>

</form>

</div>

</div>

</body>

</html>