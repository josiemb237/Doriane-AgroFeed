
CREATE DATABASE IF NOT EXISTS provenderie;
USE provenderie;


SHOW DATABASES;

CREATE TABLE IF NOT EXISTS utilisateurs (

    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,

    nom VARCHAR(100) NOT NULL,

    prenom VARCHAR(100) NOT NULL,

    telephone VARCHAR(30) NOT NULL,

    email VARCHAR(150) NOT NULL UNIQUE,

    adresse VARCHAR(255) DEFAULT NULL,

    mot_de_passe VARCHAR(255) NOT NULL,

    role ENUM('admin','client')
        NOT NULL DEFAULT 'client',

    statut ENUM('actif','inactif')
        NOT NULL DEFAULT 'actif',

    date_creation TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO utilisateurs
(
    nom,
    prenom,
    telephone,
    email,
    adresse,
    mot_de_passe,
    role,
    statut
)
VALUES
(
    'jose',
    'mb',
    '+237 676870980',
    'jmb647307@gmail.com',
    'Cameroun',
    '$2y$12$7xDyeU6tp15HfxU1hgrJIulGg8aog12T6tklOWz00kYR45Abc9tj2',
    'admin',
    'actif'
);
CREATE TABLE categorie (
    id_categorie INT AUTO_INCREMENT PRIMARY KEY,
    nom_categorie VARCHAR(100) NOT NULL
);

INSERT INTO categorie (nom_categorie) VALUES
('Volaille'),
('Bovin'),
('Porcin'),
('Compléments alimentaires');

SELECT * FROM categorie;


CREATE TABLE produit (
    id_produit INT AUTO_INCREMENT PRIMARY KEY,
    id_categorie INT NOT NULL,
    nom_produit VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    image VARCHAR(255),

    FOREIGN KEY (id_categorie)
    REFERENCES categorie(id_categorie)
);



INSERT INTO produit
(id_categorie, nom_produit, description, prix, stock, image)
VALUES

(1, 'Maïs',
'Aliment énergétique de qualité pour l’alimentation animale.',
5000, 100, 'mais.jpg'),

(1, 'Soja',
'Aliment riche en protéines pour améliorer la croissance des animaux.',
8000, 60, 'soja.jpg'),

(2, 'Tourteau de soja',
'Aliment protéique destiné principalement aux bovins.',
7000, 80, 'tourteau.jpg'),

(3, 'Son de blé',
'Aliment utilisé dans différentes rations animales.',
4500, 70, 'son.jpg'),

(1, 'Arachide',
'Produit riche en énergie et en protéines.',
6000, 100, 'arachide.jpg'),

(1, 'Belgoforce concentré',
'Concentré destiné à compléter l’alimentation des animaux.',
12000, 50, 'belgoforce.jpg'),

(2, 'Coton',
'Sous-produit agricole utilisé dans certaines formulations alimentaires.',
5500, 80, 'coton.jpg'),

(3, 'Palmiste',
'Produit dérivé du palmier utilisé dans l’alimentation animale.',
5000, 80, 'palmiste.jpg'),

(4, 'Vitamines',
'Complément vitaminé destiné aux animaux.',
10000, 50, 'vitamines.jpg'),

(4, 'Fer',
'Complément minéral destiné à l’alimentation animale.',
7500, 50, 'fer.jpg'),

(1, 'Aliment volaille',
'Aliment adapté à l’élevage des volailles.',
9000, 100, 'aliment_volaille.jpg'),

(3, 'Aliment porcs',
'Aliment destiné à l’élevage porcin.',
8500, 100, 'aliment_porcs.jpg'),

(2, 'Aliment bovin',
'Aliment destiné à l’alimentation et à la croissance des bovins.',
11000, 100, 'aliment_bovin.jpg'),

(4, 'Aliment lapin',
'Aliment adapté à l’élevage et à la croissance des lapins.',
8000, 100, 'aliment_lapin.jpg'),

(1, 'Concentré',
'Aliment concentré permettant de compléter les rations alimentaires des animaux.',
10000, 70, 'concentre.jpg'),

(1, 'Son de maïs',
'Sous-produit du maïs utilisé comme complément dans l’alimentation animale.',
4000, 70, 'son_mais.jpg'),

(1, 'Soja grain',
'Graine riche en protéines utilisée dans l’alimentation animale.',
8000, 70, 'soja_grain.jpg'),

(4, 'Premix',
'Complément contenant différents éléments nutritionnels pour les animaux.',
13000, 50, 'premix.jpg'),

(4, 'Minéraux',
'Complément minéral destiné à renforcer l’alimentation des animaux.',
7500, 50, 'mineraux.jpg'),

(1, 'Aliment complet',
'Aliment complet destiné à répondre aux besoins nutritionnels des animaux.',
12000, 100, 'aliment_complet.jpg');

SELECT * FROM produit;
CREATE TABLE commande (
    id_commande INT AUTO_INCREMENT PRIMARY KEY,

    id_utilisateur INT NOT NULL,

    statut ENUM(
        'En attente',
        'Confirmée'
    ) DEFAULT 'En attente',

    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateurs(id_utilisateur)
        ON DELETE CASCADE
);

INSERT INTO commande (id_utilisateur, statut)
VALUES (1, 'En attente');
CREATE TABLE ligne_commande (
    id_ligne_commande INT AUTO_INCREMENT PRIMARY KEY,

    id_commande INT NOT NULL,

    id_produit INT NOT NULL,

    quantite INT NOT NULL,

    prix_unitaire DECIMAL(10,2) NOT NULL,

    sous_total DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (id_commande)
        REFERENCES commande(id_commande)
        ON DELETE CASCADE,

    FOREIGN KEY (id_produit)
        REFERENCES produit(id_produit)
        ON DELETE CASCADE
);
INSERT INTO ligne_commande
(id_commande, id_produit, quantite, prix_unitaire, sous_total)
VALUES
(1, 1, 2, 5000, 10000),
(1, 2, 3, 8000, 24000),
(1, 11, 1, 9000, 9000);

CREATE TABLE vente (
    id_vente INT AUTO_INCREMENT PRIMARY KEY,

    id_commande INT NOT NULL,

    montant DECIMAL(10,2) NOT NULL,

    date_vente TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_commande)
    REFERENCES commande(id_commande)
);

INSERT INTO vente (id_commande,montant)
VALUES
(2,24000),
(3,45000);

SELECT * FROM vente;