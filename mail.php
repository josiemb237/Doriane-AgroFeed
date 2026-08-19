<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/vendor/autoload.php";

function envoyerOTP($email, $otp, $objet)
{
    $mail = new PHPMailer(true);

    try {

        // SMTP Gmail
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;

        // Adresse Gmail qui envoie les codes
        $mail->Username = "jmb647307@gmail.com";

        // MOT DE PASSE D'APPLICATION GOOGLE
        // Remplace par le nouveau
        $mail->Password = "bvws eflo unxp yfen";

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->CharSet = "UTF-8";

        // Expéditeur
        $mail->setFrom(
            "jmb647307@gmail.com",
            "Doriane AgroFeed"
        );

        // Destinataire
        $mail->addAddress($email);

        // Message HTML
        $mail->isHTML(true);

        $mail->Subject = $objet;

        $mail->Body = "
        <!DOCTYPE html>
        <html lang='fr'>
        <body style='font-family:Arial,sans-serif;'>

            <div style='
                max-width:600px;
                margin:auto;
                padding:30px;
                border:1px solid #ddd;
                border-radius:10px;
            '>

                <h2 style='color:#198754;'>
                    DORIANE AGROFEED
                </h2>

                <p>Bonjour,</p>

                <p>
                    Voici votre code de vérification :
                </p>

                <div style='
                    text-align:center;
                    font-size:32px;
                    font-weight:bold;
                    letter-spacing:8px;
                    color:#198754;
                    padding:20px;
                '>
                    {$otp}
                </div>

                <p>
                    Ce code est valable pendant
                    <strong>10 minutes</strong>.
                </p>

                <p>
                    Si vous n'êtes pas à l'origine
                    de cette demande, ignorez ce message.
                </p>

            </div>

        </body>
        </html>
        ";

        $mail->AltBody =
            "Votre code de vérification est : " . $otp;

        $mail->send();

        return true;

    } catch (Exception $e) {

        // Afficher l'erreur réelle dans le journal PHP
        error_log(
            "Erreur PHPMailer : " . $mail->ErrorInfo
        );

        return false;
    }
}