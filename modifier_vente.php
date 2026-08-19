<?php
include "connexions.php";

$erreur = "";
$id_vente = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_vente) {
    header("Location: ventes.php");
    exit();
}

// -------------------------------------------------------------------------
// TRAITEMENT DU FORMULAIRE DE MODIFICATION
// -------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $montant = (float) $_POST['montant'];
    $date_vente = $_POST['date_vente'];
    $statut = $_POST['statut'];
    $id_commande = (int) $_POST['id_commande'];

    try {
        $pdo->beginTransaction();

        // 1. Mise à jour de la vente
        $stmtVente = $pdo->prepare("UPDATE vente SET montant = ?, date_vente = ? WHERE id_vente = ?");
        $stmtVente->execute([$montant, $date_vente, $id_vente]);

        // 2. Mise à jour du statut de la commande associée
        $stmtCmd = $pdo->prepare("UPDATE commande SET statut = ? WHERE id_commande = ?");
        $stmtCmd->execute([$statut, $id_commande]);

        $pdo->commit();

        // Redirection après succès
        header("Location: ventes.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $erreur = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

// -------------------------------------------------------------------------
// RÉCUPÉRATION DES DONNÉES DE LA VENTE
// -------------------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT v.*, c.statut, u.nom, u.prenom
    FROM vente v
    INNER JOIN commande c ON v.id_commande = c.id_commande
    INNER JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
    WHERE v.id_vente = ?
");
$stmt->execute([$id_vente]);
$vente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vente) {
    header("Location: ventes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la vente FAC-<?= str_pad($vente['id_vente'], 4, "0", STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 40px 0; }
        .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4">
                <h3 class="mb-3 text-center">
                    <i class="bi bi-pencil-square"></i> Modifier la Vente
                </h3>
                <p class="text-muted text-center">Facture N° <strong>FAC-<?= str_pad($vente['id_vente'], 4, "0", STR_PAD_LEFT) ?></strong></p>
                <p class="text-center"><strong>Client :</strong> <?= htmlspecialchars($vente['nom'] . " " . $vente['prenom']) ?></p>

                <?php if (!empty($erreur)): ?>
                    <div class="alert alert-danger"><?= $erreur ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="id_commande" value="<?= $vente['id_commande'] ?>">

                    <div class="mb-3">
                        <label for="montant" class="form-label">Montant (FCFA)</label>
                        <input type="number" step="0.01" class="form-control" id="montant" name="montant" value="<?= $vente['montant'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="date_vente" class="form-label">Date et heure de la vente</label>
                        <input type="datetime-local" class="form-control" id="date_vente" name="date_vente" value="<?= date('Y-m-d\TH:i', strtotime($vente['date_vente'])) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="statut" class="form-label">Statut de la commande</label>
                        <select class="form-select" id="statut" name="statut" required>
                            <option value="Validée" <?= $vente['statut'] == 'Validée' ? 'selected' : '' ?>>Validée</option>
                            <option value="Livrée" <?= $vente['statut'] == 'Livrée' ? 'selected' : '' ?>>Livrée</option>
                            <option value="En attente" <?= $vente['statut'] == 'En attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="Annulée" <?= $vente['statut'] == 'Annulée' ? 'selected' : '' ?>>Annulée</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="ventes.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>