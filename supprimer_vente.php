<?php
include "connexions.php";

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_vente = (int) $_GET['id'];

    try {
        // Préparation et exécution de la suppression
        $stmt = $pdo->prepare("DELETE FROM vente WHERE id_vente = ?");
        $stmt->execute([$id_vente]);
    } catch (PDOException $e) {
        // En cas d'erreur de clé étrangère ou autre
        die("Erreur lors de la suppression : " . $e->getMessage());
    }
}

// Redirection vers la page des ventes
header("Location: ventes.php");
exit();
?>