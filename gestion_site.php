

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gestion des Ventes</title>
 <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="gestion.css">
</head>
<body>

<aside class="sidebar">

    <div class="logo">

        <img src="img/WhatsApp Image 2026-07-13 at 12.34.47.jpeg"alt="Logo">

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

        <li>
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

        <li class="active">
            <a href="ventes.php">
                <i class="bi bi-cash-stack"></i>
                Ventes
            </a>
        </li>

        <li>
            <a href="../index.php">
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
<main class="site-content">
    <div class="site-header">

        <div>

            <h1>Gestion du site</h1>

            <p>
                Modifier les informations de la provenderie
            </p>

        </div>

    </div>
    <section class="gestion-card">

        <div class="card-header">

            <div>

                <h2>
                    <i class="bi bi-building"></i>

                    Informations de la provenderie
                </h2>

                <p>
                    Gérez les informations visibles sur le site.
                </p>

            </div>

        </div>


        <form>


            <div class="row g-4">

                <div class="col-md-6">

                    <label for="nom">
                        Nom de la provenderie
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-shop"></i>

                        </span>

                        <input
                            type="text"
                            id="nom"
                            class="form-control"
                            value="Doriane AgroFeed" >

                    </div>

                </div>
                <div class="col-md-6">

                    <label for="telephone">
                        Téléphone
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-telephone-fill"></i>

                        </span>

                        <input
                            type="tel"
                            id="telephone"
                            class="form-control"
                            value="+237 6766870980" >

                    </div>

                </div>

                <div class="col-md-6">

                    <label for="email">
                        Adresse e-mail
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-envelope-fill"></i>

                        </span>

                        <input
                            type="email"
                            id="email"
                            class="form-control"
                            value=".jmb647307@gmailcom" >
                    </div>

                </div>

                <div class="col-md-6">

                    <label for="adresse">
                        Adresse
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-geo-alt-fill"></i>

                        </span>

                        <input
                            type="text"
                            id="adresse"
                            class="form-control"
                            value="bafousam,cameroun">
                        
                    </div>

                </div>

                <div class="col-12">

                    <label for="description">
                        Description de la provenderie
                    </label>

                    <textarea
                        id="description"
                        class="form-control"
                        rows="5"  >
                  Votre provenderie spécialisée dans la vente d'aliments et produits destinés à l'élevage.</textarea>

                </div>

                <div class="col-md-6">

                    <label for="ouverture">
                        Heure d'ouverture
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-clock-fill"></i>

                        </span>

                        <input
                            type="time"
                            id="ouverture"
                            class="form-control"
                            value="08:00">

                    </div>

                </div>

                <div class="col-md-6">

                    <label for="fermeture">
                        Heure de fermeture
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-clock-fill"></i>

                        </span>

                        <input
                            type="time"
                            id="fermeture"
                            class="form-control"
                            value="18:00" >
                    </div>

                </div>

                <div class="col-md-6">

                    <label for="logo">
                       <img src="">
                    </label>

                    <input
                        type="file"
                        id="logo"
                        class="form-control"
                        accept="image  ">

                </div>

                <div class="col-md-6">

                    <label for="statut">
                        Statut du site
                    </label>

                    <select
                        id="statut"
                        class="form-select">
                    

                        <option value="ouvert" selected>
                            Site ouvert
                        </option>

                        <option value="ferme">
                            Site fermé
                        </option>

                    </select>

                </div>


            </div>

            <div class="form-actions">

                <button
                    type="reset"
                    class="btn-annuler">

                    <i class="bi bi-arrow-counterclockwise"></i>

                    Annuler

                </button>


                <button
                    type="submit"
                    class="btn-enregistrer">

                    <i class="bi bi-check-circle-fill"></i>

                    Enregistrer les modifications

                </button>

            </div>


        </form>

    </section>

    <section class="gestion-card">

        <div class="card-header">

            <div>

                <h2>

                    <i class="bi bi-share-fill"></i>

                    Réseaux sociaux

                </h2>

                <p>
                    Ajoutez les liens vers vos réseaux sociaux.
                </p>

            </div>

        </div>


        <form>


            <div class="row g-4">


                <div class="col-md-6">

                    <label for="facebook">

                        <i class="bi bi-facebook"></i>

                        Facebook

                    </label>

                    <input
                        type="url"
                        id="facebook"
                        class="form-control"
                        placeholder="https://facebook.com/..."
                    >

                </div>


                <div class="col-md-6">

                    <label for="instagram">

                        <i class="bi bi-instagram"></i>

                        Instagram

                    </label>

                    <input
                        type="url"
                        id="instagram"
                        class="form-control"
                        placeholder="https://instagram.com/...">
                  

                </div>


                <div class="col-md-6">

                    <label for="whatsapp">

                        <i class="bi bi-whatsapp"></i>

                        WhatsApp

                    </label>

                    <input
                        type="tel"
                        id="whatsapp"
                        class="form-control"
                        placeholder="+225 07 00 00 00 00"
                    >

                </div>


                <div class="col-md-6">

                    <label for="youtube">

                        <i class="bi bi-youtube"></i>

                        YouTube

                    </label>

                    <input
                        type="url"
                        id="youtube"
                        class="form-control"
                        placeholder="https://youtube.com/...">
                    

                </div>


            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    class="btn-enregistrer">

                    <i class="bi bi-check-circle-fill"></i>

                    Enregistrer

                </button>

            </div>


        </form>

    </section>
    <section class="gestion-card">


        <div class="card-header">

            <div>

                <h2>

                    <i class="bi bi-globe2"></i>

                    Paramètres du site

                </h2>

                <p>
                    Paramètres généraux du site.
                </p>

            </div>

        </div>


        <div class="settings-list">


            <div class="setting">

                <div class="setting-icon">

                    <i class="bi bi-eye-fill"></i>

                </div>

                <div class="setting-info">

                    <h3>Visibilité du site</h3>

                    <p>
                        Permettre aux visiteurs d'accéder au site.
                    </p>

                </div>

                <div>

                    <div class="form-check form-switch">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            checked
                        >

                    </div>

                </div>

            </div>



            <div class="setting">

                <div class="setting-icon">

                    <i class="bi bi-bell-fill"></i>

                </div>

                <div class="setting-info">

                    <h3>Notifications</h3>

                    <p>
                        Recevoir les notifications des nouvelles commandes.
                    </p>

                </div>

                <div>

                    <div class="form-check form-switch">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            checked
                        >

                    </div>

                </div>

            </div>



            <div class="setting">

                <div class="setting-icon">

                    <i class="bi bi-cart-check-fill"></i>

                </div>

                <div class="setting-info">

                    <h3>Commandes en ligne</h3>

                    <p>
                        Autoriser les clients à passer des commandes.
                    </p>

                </div>

                <div>

                    <div class="form-check form-switch">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            checked >
                       

                    </div>

                </div>

            </div>


        </div>


    </section>


</main>


<script src="js/bootstrap.bundle.min.js"></script>

</body>

</html>