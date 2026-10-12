<?php

session_start();

require_once "connexions.php";


/* =========================================================
   INITIALISATION
========================================================= */

$messageErreur = "";

$recherche = "";
$dateDebut = "";
$dateFin = "";

$ventes = [];


/* =========================================================
   RÉCUPÉRATION DES FILTRES
========================================================= */

if (isset($_GET["recherche"])) {
    $recherche = trim($_GET["recherche"]);
}

if (isset($_GET["date_debut"])) {
    $dateDebut = trim($_GET["date_debut"]);
}

if (isset($_GET["date_fin"])) {
    $dateFin = trim($_GET["date_fin"]);
}


/* =========================================================
   STATISTIQUES
========================================================= */

$totalVentes = 0;
$chiffreAffaires = 0;
$ventesMois = 0;
$ventesAujourdhui = 0;


try {

    /* TOTAL DES VENTES */

    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM vente
    ");

    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalVentes = (int) ($resultat["total"] ?? 0);


    /* CHIFFRE D'AFFAIRES */

    $stmt = $pdo->query("
        SELECT COALESCE(SUM(montant), 0) AS total
        FROM vente
    ");

    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

    $chiffreAffaires = (float) ($resultat["total"] ?? 0);


    /* VENTES DU MOIS */

    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM vente
        WHERE MONTH(date_vente) = MONTH(CURRENT_DATE())
        AND YEAR(date_vente) = YEAR(CURRENT_DATE())
    ");

    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

    $ventesMois = (int) ($resultat["total"] ?? 0);


    /* VENTES AUJOURD'HUI */

    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM vente
        WHERE DATE(date_vente) = CURRENT_DATE()
    ");

    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

    $ventesAujourdhui = (int) ($resultat["total"] ?? 0);

} catch (PDOException $e) {

    $messageErreur = "Erreur statistiques : " . $e->getMessage();
}


/* =========================================================
   RÉCUPÉRATION DES VENTES
========================================================= */

try {

    $sql = "
        SELECT
            v.id_vente,
            v.id_commande,
            v.montant,
            v.date_vente,

            c.id_utilisateur,

            u.nom,
            u.prenom,
            u.telephone

        FROM vente v

        LEFT JOIN commande c
            ON v.id_commande = c.id_commande

        LEFT JOIN utilisateurs u
            ON c.id_utilisateur = u.id_utilisateur

        WHERE 1 = 1
    ";


    $parametres = [];


    /* RECHERCHE */

    if ($recherche !== "") {

        $sql .= "
            AND (
                u.nom LIKE ?
                OR u.prenom LIKE ?
                OR u.telephone LIKE ?
                OR CAST(v.id_vente AS CHAR) LIKE ?
                OR CAST(v.id_commande AS CHAR) LIKE ?
            )
        ";

        $rechercheSQL = "%" . $recherche . "%";

        $parametres[] = $rechercheSQL;
        $parametres[] = $rechercheSQL;
        $parametres[] = $rechercheSQL;
        $parametres[] = $rechercheSQL;
        $parametres[] = $rechercheSQL;
    }


    /* DATE DÉBUT */

    if ($dateDebut !== "") {

        $sql .= "
            AND DATE(v.date_vente) >= ?
        ";

        $parametres[] = $dateDebut;
    }


    /* DATE FIN */

    if ($dateFin !== "") {

        $sql .= "
            AND DATE(v.date_vente) <= ?
        ";

        $parametres[] = $dateFin;
    }


    /* TRI */

    $sql .= "
        ORDER BY
            v.date_vente DESC,
            v.id_vente DESC
    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute($parametres);

    $ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $messageErreur = "Erreur ventes : " . $e->getMessage();
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

    <title>Gestion des ventes - DORIANE AGROFEED</title>


    <link
        rel="stylesheet"
        href="bootstrap-5.0.2-dist/css/bootstrap.min.css"
    >


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background: #f4f6f9;

            font-family: Arial, Helvetica, sans-serif;

            color: #333;
        }


        /* SIDEBAR */

        .sidebar {

            position: fixed;

            top: 0;

            left: 0;

            width: 250px;

            height: 100vh;

            background:
                linear-gradient(
                    180deg,
                    #145a32,
                    #0b3d20
                );

            color: white;

            padding: 20px 15px;

            overflow-y: auto;
        }


        .logo-container {

            text-align: center;

            margin-bottom: 35px;
        }


        .logo-container img {

            width: 85px;

            height: 85px;

            object-fit: contain;

            border-radius: 50%;

            background: white;

            padding: 5px;
        }


        .logo-container h2 {

            font-size: 20px;

            margin-top: 12px;

            font-weight: bold;
        }


        .sidebar a {

            display: flex;

            align-items: center;

            gap: 12px;

            color: white;

            text-decoration: none;

            padding: 14px 15px;

            margin-bottom: 8px;

            border-radius: 8px;
        }


        .sidebar a:hover {

            background: rgba(255, 255, 255, 0.15);
        }


        .sidebar a.active {

            background: #f39c12;

            font-weight: bold;
        }


        /* CONTENU */

        .main-content {

            margin-left: 250px;

            padding: 30px;
        }


        .page-header {

            margin-bottom: 30px;
        }


        .page-header h1 {

            font-size: 28px;

            font-weight: bold;
        }


        .stats-container {

            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 20px;

            margin-bottom: 30px;
        }


        .stat-card {

            background: white;

            border-radius: 12px;

            padding: 22px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            box-shadow:
                0 4px 15px
                rgba(0, 0, 0, 0.08);
        }


        .stat-info span {

            color: #777;

            font-size: 14px;
        }


        .stat-info h3 {

            margin: 8px 0 0;

            font-size: 24px;
        }


        .stat-icon {

            width: 55px;

            height: 55px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;
        }


        .icon-blue {

            background: #e7f0ff;

            color: #0d6efd;
        }


        .icon-green {

            background: #e6f7ed;

            color: #198754;
        }


        .icon-orange {

            background: #fff0dd;

            color: #fd7e14;
        }


        .icon-red {

            background: #fde8e8;

            color: #dc3545;
        }


        .filter-card,
        .table-card {

            background: white;

            padding: 20px;

            border-radius: 12px;

            margin-bottom: 25px;

            box-shadow:
                0 4px 15px
                rgba(0, 0, 0, 0.07);
        }


        .filter-card form {

            display: flex;

            gap: 12px;

            align-items: end;

            flex-wrap: wrap;
        }


        .filter-group {

            flex: 1;

            min-width: 180px;
        }


        .filter-group label {

            display: block;

            margin-bottom: 6px;

            font-weight: bold;
        }


        .montant {

            color: #198754;

            font-weight: bold;
        }


        .action-buttons {

            display: flex;

            gap: 8px;
        }


        .btn-commande,
        .btn-imprimer {

            border: none;

            text-decoration: none;

            padding: 8px 12px;

            border-radius: 6px;

            color: white;

            font-size: 13px;
        }


        .btn-commande {

            background: #198754;
        }


        .btn-imprimer {

            background: #0d6efd;
        }


        @media (max-width: 1100px) {

            .stats-container {

                grid-template-columns: repeat(2, 1fr);
            }
        }


        @media (max-width: 768px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;
            }


            .main-content {

                margin-left: 0;
            }


            .stats-container {

                grid-template-columns: 1fr;
            }
        }

    </style>

</head>


<body>


<aside class="sidebar">

    <div class="logo-container">

        <img
            src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg"
            alt="Logo DORIANE AGROFEED"
        >

        <h2>DORIANE AGROFEED</h2>

    </div>


    <a href="dashboard.php">

        <i class="bi bi-speedometer2"></i>

        Tableau de bord

    </a>


    <a href="gestion_site.php">

        <i class="bi bi-globe"></i>

        Gestion du site

    </a>


    <a href="clients.php">

        <i class="bi bi-people"></i>

        Clients

    </a>


    <a href="commandes.php">

        <i class="bi bi-cart-check"></i>

        Commandes

    </a>


    <a href="produits.php">

        <i class="bi bi-box-seam"></i>

        Produits

    </a>


    <a
        href="vente.php"
        class="active"
    >

        <i class="bi bi-cash-stack"></i>

        Ventes

    </a>


    <a
        href="index.php"
        target="_blank"
    >

        <i class="bi bi-box-arrow-up-right"></i>

        Aller au site

    </a>


    <a href="connexion.php">

        <i class="bi bi-box-arrow-right"></i>

        Déconnexion

    </a>

</aside>


<main class="main-content">


    <div class="page-header">

        <h1>Gestion des ventes</h1>

        <p>Consultez toutes les ventes enregistrées.</p>

    </div>


    <?php if ($messageErreur !== ""): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars($messageErreur) ?>

        </div>

    <?php endif; ?>


    <section class="stats-container">


        <div class="stat-card">

            <div class="stat-info">

                <span>Total des ventes</span>

                <h3>

                    <?= number_format(
                        $totalVentes,
                        0,
                        ",",
                        " "
                    ) ?>

                </h3>

            </div>

            <div class="stat-icon icon-blue">

                <i class="bi bi-receipt"></i>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-info">

                <span>Chiffre d'affaires</span>

                <h3>

                    <?= number_format(
                        $chiffreAffaires,
                        0,
                        ",",
                        " "
                    ) ?>

                    FCFA

                </h3>

            </div>

            <div class="stat-icon icon-green">

                <i class="bi bi-cash-stack"></i>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-info">

                <span>Ventes du mois</span>

                <h3>

                    <?= $ventesMois ?>

                </h3>

            </div>

            <div class="stat-icon icon-orange">

                <i class="bi bi-calendar-month"></i>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-info">

                <span>Ventes aujourd'hui</span>

                <h3>

                    <?= $ventesAujourdhui ?>

                </h3>

            </div>

            <div class="stat-icon icon-red">

                <i class="bi bi-calendar-day"></i>

            </div>

        </div>


    </section>


    <!-- FILTRES -->

    <section class="filter-card">

        <form
            method="GET"
            action="ventes.php"
        >

            <div class="filter-group">

                <label>Rechercher</label>

                <input
                    type="text"
                    name="recherche"
                    class="form-control"
                    placeholder="Client, téléphone, commande..."
                    value="<?= htmlspecialchars($recherche) ?>"
                >

            </div>


            <div class="filter-group">

                <label>Date de début</label>

                <input
                    type="date"
                    name="date_debut"
                    class="form-control"
                    value="<?= htmlspecialchars($dateDebut) ?>"
                >

            </div>


            <div class="filter-group">

                <label>Date de fin</label>

                <input
                    type="date"
                    name="date_fin"
                    class="form-control"
                    value="<?= htmlspecialchars($dateFin) ?>"
                >

            </div>


            <button
                type="submit"
                class="btn btn-success"
            >

                <i class="bi bi-search"></i>

                Rechercher

            </button>

        </form>

    </section>


    <!-- TABLEAU -->

    <section class="table-card">

        <h3>

            <i class="bi bi-list-ul"></i>

            Liste des ventes

            <span class="text-muted fs-6">

                (<?= count($ventes) ?> résultat(s))

            </span>

        </h3>


        <div class="table-responsive">

            <table class="table table-hover">

                <thead>

                    <tr>

                        <th>N° Vente</th>

                        <th>Commande</th>

                        <th>Client</th>

                        <th>Téléphone</th>

                        <th>Montant</th>

                        <th>Date</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (!empty($ventes)): ?>


                    <?php foreach ($ventes as $vente): ?>


                        <?php

                        $nomComplet = trim(
                            ($vente["nom"] ?? "")
                            . " "
                            . ($vente["prenom"] ?? "")
                        );

                        if ($nomComplet === "") {
                            $nomComplet = "Client non renseigné";
                        }

                        ?>


                        <tr>


                            <td>

                                <strong>

                                    #<?= (int) $vente["id_vente"] ?>

                                </strong>

                            </td>


                            <td>

                                #<?= (int) $vente["id_commande"] ?>

                            </td>


                            <td>

                                <?= htmlspecialchars($nomComplet) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $vente["telephone"] ?? "—"
                                ) ?>

                            </td>


                            <td>

                                <span class="montant">

                                    <?= number_format(
                                        (float) $vente["montant"],
                                        0,
                                        ",",
                                        " "
                                    ) ?>

                                    FCFA

                                </span>

                            </td>


                            <td>

                                <?php

                                if (!empty($vente["date_vente"])) {

                                    echo date(
                                        "d/m/Y H:i",
                                        strtotime($vente["date_vente"])
                                    );

                                } else {

                                    echo "—";

                                }

                                ?>

                            </td>


                            <td>

                                <div class="action-buttons">


                                    <a
                                        href="commandes.php?id=<?= (int) $vente["id_commande"] ?>"
                                        class="btn-commande"
                                    >

                                        <i class="bi bi-eye"></i>

                                        Commande

                                    </a>


                                    <!--
                                        IMPORTANT :
                                        ON ENVOIE ID_VENTE
                                    -->

                                    <a
                                        href="facture.php?id_vente=<?= (int) $vente["id_vente"] ?>"
                                        class="btn-imprimer"
                                        target="_blank"
                                    >

                                        <i class="bi bi-printer"></i>

                                        Imprimer

                                    </a>


                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="7"
                            class="text-center text-muted py-5"
                        >

                            <i
                                class="bi bi-cash-stack"
                                style="font-size:40px;"
                            ></i>

                            <br><br>

                            Aucune vente trouvée.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </section>


</main>


<script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>