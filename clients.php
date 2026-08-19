<?php

session_start();

require_once "database.php";
$stmt = $pdo->prepare("
    SELECT
        id_utilisateur,
        nom,
        prenom,
        telephone,
        email,
        adresse,
        statut,
        date_creation
    FROM utilisateurs
    WHERE role = 'client'
    ORDER BY id_utilisateur DESC
");

$stmt->execute();

$clients = $stmt->fetchAll();


/*
========================================
STATISTIQUES
========================================
*/

$totalClients = count($clients);

$stmtActifs = $pdo->query("
    SELECT COUNT(*)
    FROM utilisateurs
    WHERE role = 'client'
    AND statut = 'actif'
");

$clientsActifs = $stmtActifs->fetchColumn();


$stmtNouveaux = $pdo->query("
    SELECT COUNT(*)
    FROM utilisateurs
    WHERE role = 'client'
    AND date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");

$nouveauxClients = $stmtNouveaux->fetchColumn();

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gestion des Clients</title>

<link
    rel="stylesheet"
    href="bootstrap-5.0.2-dist/css/bootstrap.min.css"
>

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>

<link rel="stylesheet" href="client.css">

</head>


<body>


<div class="container">

<aside class="sidebar">


    <div class="logo">

        <img
            src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg"
            alt="Logo"
        >

        <h2>PROVENDERIE</h2>

    </div>


    <ul>


        <li>

            <a href="dashboard.php">

                <i class="bi bi-speedometer2"></i>

                Tableau de bord

            </a>

        </li>


        <li>

            <a href="gestion_site.php">

                <i class="bi bi-gear-fill"></i>

                Gestion du site

            </a>

        </li>


        <li class="active">

            <a href="clients.php">

                <i class="bi bi-people-fill"></i>

                Clients

            </a>

        </li>


        <li>

            <a href="commandes.php">

                <i class="bi bi-cart-fill"></i>

                Commandes

            </a>

        </li>


        <li>

            <a href="produits.php">

                <i class="bi bi-box-seam-fill"></i>

                Produits

            </a>

        </li>


        <li>

            <a href="ventes.php">

                <i class="bi bi-cash-stack"></i>

                Ventes

            </a>

        </li>


        <li>

            <a href="index.php">

                <i class="bi bi-house-fill"></i>

                Aller au site

            </a>

        </li>
        <li>
            <a href="connexion.php">
                <i class="bi bi-box-arrow-right"></i>
                Déconnexion
            </a>
        </li>



    </ul>

</aside>

<main class="client-content">


    <div class="client-header">


        <div>

            <h1>Gestion des clients</h1>

            <p>
                Gestion des clients de la provenderie
            </p>

        </div>
    </div>

    <section class="client-cards">


        <div class="client-card bg-success">

            <div class="card-icon blue">

                <i class="bi bi-people-fill"></i>

            </div>

            <div>

                <h2>
                    <?= $totalClients ?>
                </h2>

                <p>Total clients</p>

            </div>

        </div>


        <div class="client-card bg-danger">

            <div class="card-icon money">

                <i class="bi bi-person-check-fill"></i>

            </div>

            <div>

                <h2>
                    <?= $clientsActifs ?>
                </h2>

                <p>Clients actifs</p>

            </div>

        </div>


        <div class="client-card bg-secondary">

            <div class="card-icon danger">

                <i class="bi bi-person-plus-fill"></i>

            </div>

            <div>

                <h2>
                    <?= $nouveauxClients ?>
                </h2>

                <p>Nouveaux clients</p>

            </div>

        </div>


        <div class="client-card bg-warning">

            <div class="card-icon success">

                <i class="bi bi-cash-stack"></i>

            </div>

            <div>

                <h2>0 FCFA</h2>

                <p>Achats clients</p>

            </div>

        </div>


    </section>
    <section class="client-container">


        <div class="table-header">


            <div>

                <h2>Liste des clients</h2>

                <p>
                    Retrouvez et gérez tous les clients
                </p>

            </div>


            <div class="recherche">

                <i class="bi bi-search"></i>

                <input
                    type="search"
                    id="rechercheClient"
                    placeholder="Rechercher un client..."
                >

            </div>


        </div>


        <div class="table-responsive">


            <table class="client-table" id="tableClients">


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Client</th>

                        <th>Contact</th>

                        <th>Adresse</th>

                        <th>Date inscription</th>

                        <th>Statut</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (empty($clients)): ?>


                    <tr>

                        <td colspan="7" style="text-align:center;">

                            Aucun client enregistré.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($clients as $client): ?>


                    <?php

                    $initiales =
                        strtoupper(
                            substr($client["prenom"], 0, 1) .
                            substr($client["nom"], 0, 1)
                        );

                    ?>


                    <tr>


                        <td>

                            <strong>
                                CL<?= str_pad(
                                    $client["id_utilisateur"],
                                    3,
                                    "0",
                                    STR_PAD_LEFT
                                ) ?>
                            </strong>

                        </td>


                        <!-- CLIENT -->

                        <td>

                            <div class="client-info">


                                <div class="avatar">

                                    <?= htmlspecialchars($initiales) ?>

                                </div>


                                <div>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $client["nom"]
                                        ) ?>

                                        <?= htmlspecialchars(
                                            $client["prenom"]
                                        ) ?>

                                    </strong>


                                    <small>

                                        Client

                                    </small>

                                </div>


                            </div>

                        </td>
                        <td>


                            <span class="contact">

                                <i class="bi bi-telephone"></i>

                                <?= htmlspecialchars(
                                    $client["telephone"]
                                ) ?>

                            </span>


                            <span class="contact">

                                <i class="bi bi-envelope"></i>

                                <?= htmlspecialchars(
                                    $client["email"]
                                ) ?>

                            </span>


                        </td>
                        <td>

                            <?= htmlspecialchars(
                                $client["adresse"] ?? ""
                            ) ?>

                        </td>

                        <td>

                            <?= date(
                                "d/m/Y",
                                strtotime(
                                    $client["date_creation"]
                                )
                            ) ?>

                        </td>


                        <!-- STATUT -->

                        <td>


                            <?php if (
                                $client["statut"] === "actif"
                            ): ?>


                                <span class="badge-actif">

                                    Actif

                                </span>


                            <?php else: ?>


                                <span class="badge-inactif">

                                    Inactif

                                </span>


                            <?php endif; ?>


                        </td>

                        <td>


                            <div class="actions">
                                <a
                                    href="supprimer_client.php?id=<?= $client["id_utilisateur"] ?>"
                                    class="btn-supprimer"
                                    title="Supprimer"
                                    onclick="return confirm('Voulez-vous vraiment supprimer ce client ?');"
                                >

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


</div>


<script>

const recherche =
    document.getElementById("rechercheClient");

const lignes =
    document.querySelectorAll("#tableClients tbody tr");


recherche.addEventListener("input", function () {

    const texte =
        this.value.toLowerCase();


    lignes.forEach(function (ligne) {

        const contenu =
            ligne.textContent.toLowerCase();


        ligne.style.display =
            contenu.includes(texte)
                ? ""
                : "none";

    });

});

</script>


</body>

</html>