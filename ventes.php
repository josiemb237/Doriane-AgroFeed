<?php
include "connexions.php";

// -------------------------------------------------------------------------
// AUTOMATISATION : Insérer automatiquement les commandes validées/livrées dans la table vente
// -------------------------------------------------------------------------
try {
    // 1. Récupérer les commandes qui sont validées ou livrées mais qui n'ont pas encore de vente associée
    $sqlCommandesValidees = "
        SELECT c.id_commande, 
               COALESCE(SUM(lc.sous_total), 0) AS total_montant
        FROM commande c
        LEFT JOIN ligne_commande lc ON c.id_commande = lc.id_commande
        WHERE (c.statut = 'Validée' OR c.statut = 'Livrée')
          AND c.id_commande NOT IN (SELECT id_commande FROM vente)
        GROUP BY c.id_commande
    ";
    
    $stmtCmd = $pdo->query($sqlCommandesValidees);
    $commandesAConvertir = $stmtCmd->fetchAll(PDO::FETCH_ASSOC);

    // 2. Insérer chaque commande trouvée dans la table 'vente'
    if (!empty($commandesAConvertir)) {
        $insertVente = $pdo->prepare("
            INSERT INTO vente (id_commande, montant, date_vente) 
            VALUES (?, ?, NOW())
        ");
        
        foreach ($commandesAConvertir as $cmd) {
            $insertVente->execute([
                $cmd['id_commande'], 
                $cmd['total_montant']
            ]);
        }
    }
} catch (PDOException $e) {
    // En cas d'erreur silencieuse pour ne pas bloquer l'affichage
    $erreurAutomatisation = $e->getMessage();
}

// -------------------------------------------------------------------------
// STATISTIQUES GLOBALISÉES
// -------------------------------------------------------------------------
$stmt = $pdo->query("SELECT COUNT(*) FROM vente");
$totalVentes = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM vente");
$chiffreAffaires = (float) $stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM vente 
    WHERE MONTH(date_vente) = MONTH(CURRENT_DATE()) 
    AND YEAR(date_vente) = YEAR(CURRENT_DATE())
");
$ventesMois = (int) $stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM vente 
    WHERE DATE(date_vente) = CURRENT_DATE()
");
$ventesAujourdHui = (int) $stmt->fetchColumn();

// -------------------------------------------------------------------------
// REQUÊTE PRINCIPALE DES VENTES
// -------------------------------------------------------------------------
$sql = "
    SELECT 
        v.id_vente,
        v.id_commande,
        v.montant,
        v.date_vente,
        c.statut,
        u.nom,
        u.prenom,
        u.telephone
    FROM vente v
    INNER JOIN commande c ON v.id_commande = c.id_commande
    INNER JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
    ORDER BY v.id_vente DESC
";

$stmt = $pdo->query($sql);
$ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour récupérer les produits d'une vente
function getProduitsVente($pdo, $idCommande) {
    $sql = "
        SELECT 
            p.nom_produit,
            lc.quantite,
            lc.prix_unitaire,
            lc.sous_total
        FROM ligne_commande lc
        INNER JOIN produit p ON lc.id_produit = p.id_produit
        WHERE lc.id_commande = ?
        ORDER BY lc.id_ligne_commande ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idCommande]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatFCFA($montant) {
    return number_format($montant, 0, ",", " ") . " FCFA";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Optionnel : Actualise automatiquement la page toutes les 60 secondes -->
    <meta http-equiv="refresh" content="60">
    <title>Gestion des ventes</title>

    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="ventes.css">
</head>
<body>

<aside class="sidebar">
    <div class="logo">
        <img src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg" alt="Logo">
        <h2>DORIANE<br>AGROFEED</h2>
    </div>

    <ul class="menu">
        <li>
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Tableau de bord</a>
        </li>
        <li>
            <a href="clients.php"><i class="bi bi-people-fill"></i> Clients</a>
        </li>
        <li>
            <a href="commandes.php"><i class="bi bi-cart-fill"></i> Commandes</a>
        </li>
        <li>
            <a href="produits.php"><i class="bi bi-box-seam-fill"></i> Produits</a>
        </li>
        <li class="active">
            <a href="ventes.php"><i class="bi bi-cash-stack"></i> Ventes</a>
        </li>
        <li>
            <a href="../index.php"><i class="bi bi-house-fill"></i> Aller au site</a>
        </li>
        <li>
            <a href="admin.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </li>
    </ul>
</aside>

<main class="vente-content">

    <div class="vente-header">
        <div>
            <h1><i class="bi bi-cash-stack"></i> Gestion des ventes</h1>
            <p>Consultez et gérez toutes les ventes (mises à jour automatiques)</p>
        </div>
    </div>

    <section class="vente-cards">
        <div class="vente-card card-blue">
            <div class="card-icon"><i class="bi bi-cart-check-fill"></i></div>
            <div>
                <h2><?= $ventesAujourdHui ?></h2>
                <p>Ventes aujourd'hui</p>
            </div>
        </div>

        <div class="vente-card card-green">
            <div class="card-icon"><i class="bi bi-calendar-month-fill"></i></div>
            <div>
                <h2><?= $ventesMois ?></h2>
                <p>Ventes du mois</p>
            </div>
        </div>

        <div class="vente-card card-orange">
            <div class="card-icon"><i class="bi bi-cash-stack"></i></div>
            <div>
                <h2><?= formatFCFA($chiffreAffaires) ?></h2>
                <p>Chiffre d'affaires</p>
            </div>
        </div>

        <div class="vente-card card-purple">
            <div class="card-icon"><i class="bi bi-receipt-cutoff"></i></div>
            <div>
                <h2><?= $totalVentes ?></h2>
                <p>Total des ventes</p>
            </div>
        </div>
    </section>

    <section class="vente-container">
        <div class="vente-title">
            <div>
                <h2><i class="bi bi-list-ul"></i> Liste des ventes</h2>
                <p>Toutes les ventes enregistrées</p>
            </div>

            <div class="recherche">
                <i class="bi bi-search"></i>
                <input type="search" id="recherche" placeholder="Rechercher une vente...">
            </div>
        </div>

        <div class="table-wrapper">
            <table class="vente-table" id="tableVentes">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Client</th>
                        <th>Produits</th>
                        <th>Quantités</th>
                        <th>Montants</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Facture</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($ventes)): ?>
                    <tr>
                        <td colspan="10" class="aucune-vente">
                            <i class="bi bi-receipt"></i>
                            <p>Aucune vente enregistrée.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ventes as $vente): ?>
                        <?php $produitsVente = getProduitsVente($pdo, $vente["id_commande"]); ?>
                        <tr class="ligne-vente">
                            <td>
                                <strong class="numero-facture">
                                    FAC-<?= str_pad($vente["id_vente"], 4, "0", STR_PAD_LEFT) ?>
                                </strong>
                            </td>

                            <td>
                                <div class="client">
                                    <div class="client-icon"><i class="bi bi-person-fill"></i></div>
                                    <div>
                                        <strong><?= htmlspecialchars($vente["nom"] . " " . $vente["prenom"]) ?></strong>
                                        <?php if (!empty($vente["telephone"])): ?>
                                            <br><small><?= htmlspecialchars($vente["telephone"]) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <td class="produits-cell">
                                <?php foreach ($produitsVente as $produit): ?>
                                    <div class="produit-item">
                                        <i class="bi bi-box-seam"></i> <?= htmlspecialchars($produit["nom_produit"]) ?>
                                    </div>
                                <?php endforeach; ?>
                            </td>

                            <td class="quantites-cell">
                                <?php foreach ($produitsVente as $produit): ?>
                                    <div class="quantite-item"><?= $produit["quantite"] ?></div>
                                <?php endforeach; ?>
                            </td>

                            <td class="montants-cell">
                                <?php foreach ($produitsVente as $produit): ?>
                                    <div class="montant-item"><?= formatFCFA($produit["sous_total"]) ?></div>
                                <?php endforeach; ?>
                            </td>

                            <td>
                                <strong class="total-vente"><?= formatFCFA($vente["montant"]) ?></strong>
                            </td>

                            <td>
                                <span class="date-vente">
                                    <?= date("d/m/Y", strtotime($vente["date_vente"])) ?>
                                    <br><small><?= date("H:i", strtotime($vente["date_vente"])) ?></small>
                                </span>
                            </td>

                            <td>
                                <?php
                                $statut = $vente["statut"];
                                $classeStatut = match ($statut) {
                                    "Livrée" => "statut-livree",
                                    "Validée" => "statut-livree",
                                    "En attente" => "statut-attente",
                                    "Annulée" => "statut-annulee",
                                    default => "statut-autre",
                                };
                                ?>
                                <span class="statut <?= $classeStatut ?>">
                                    <?= htmlspecialchars($statut) ?>
                                </span>
                            </td>

                            <td>
                                <a href="facture.php?id=<?= $vente["id_vente"] ?>" target="_blank" class="btn-imprimer">
                                    <i class="bi bi-printer-fill"></i> Imprimer
                                </a>
                            </td>

                            <td>
                                <div class="actions">
                                    <a href="modifier_vente.php?id=<?= $vente["id_vente"] ?>" class="btn-modifier" title="Modifier">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="supprimer_vente.php?id=<?= $vente="id_vente" ?>" class="btn-supprimer" title="Supprimer" onclick="return confirm('Voulez-vous vraiment supprimer cette vente ?');">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<script>
const recherche = document.getElementById("recherche");

recherche.addEventListener("input", function () {
    const texte = this.value.toLowerCase();
    const lignes = document.querySelectorAll(".ligne-vente");

    lignes.forEach(function (ligne) {
        const contenu = ligne.textContent.toLowerCase();
        ligne.style.display = contenu.includes(texte) ? "" : "none";
    });
});
</script>

</body>
</html>