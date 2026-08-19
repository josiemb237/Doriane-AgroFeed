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

/*
On vérifie que l'utilisateur est bien un client
avant de le supprimer.
*/

$stmt = $pdo->prepare("
    DELETE FROM utilisateurs
    WHERE id_utilisateur = ?
    AND role = 'client'
");

$stmt->execute([$id]);

header("Location: clients.php");

exit;