function envoyerWhatsApp(){

    let nom = document.getElementById("nom").value;
    let email = document.getElementById("email").value;
    let telephone = document.getElementById("telephone").value;
    let ville = document.getElementById("ville").value;
    let produit = document.getElementById("produit").value;
    let animal = document.getElementById("animal").value;
    let quantite = document.getElementById("quantite").value;
    let message = document.getElementById("message").value;

    let numero = "237676870980"; 

    let texte =
`Bonjour Doriane Agro Feed,

Nom : ${nom}
E-mail : ${email}
Téléphone : ${telephone}
Ville : ${ville}

Produit : ${produit}
Animal : ${animal}
Quantité : ${quantite}

Message :
${message}`;

    let lien = "https://wa.me/" + numero + "?text=" + encodeURIComponent(texte);

    window.open(lien, "_blank");
}


const liens = document.querySelectorAll(".sidebar .nav-link");

liens.forEach((lien) => {

    lien.addEventListener("click", function () {

        liens.forEach(item => item.classList.remove("active"));

        this.classList.add("active");

    });

});


const supprimer = document.querySelectorAll(".btn-danger");

supprimer.forEach(btn=>{

btn.addEventListener("click",function(){

const rep = confirm("Voulez-vous vraiment supprimer cet élément ?");

if(rep){

alert("Suppression effectuée.");

}

});

});



const recherche = document.querySelector("input[type='search']");

if(recherche){

recherche.addEventListener("keyup",function(){

let valeur = this.value.toLowerCase();

let lignes = document.querySelectorAll("tbody tr");

lignes.forEach(function(ligne){

ligne.style.display = ligne.innerText.toLowerCase().includes(valeur)

? ""

: "none";

});

});

}


const notification = document.querySelector(".bi-bell-fill");

if(notification){

notification.addEventListener("click",function(){

alert("Vous avez 4 nouvelles notifications.");

});

}


const date = new Date();

console.log("Connexion :",date.toLocaleString());

const canvas = document.getElementById("venteChart");

if(canvas){

const ctx = canvas.getContext("2d");

new Chart(ctx,{

type:"bar",

data:{

labels:["Jan","Fév","Mar","Avr","Mai","Juin"],

datasets:[{

label:"Ventes",

data:[25,40,35,60,55,80],

backgroundColor:[

"#198754",

"#ffc107",

"#0d6efd",

"#dc3545",

"#20c997",

"#fd7e14"

],

borderRadius:8

}]

},

options:{

responsive:true,

plugins:{

legend:{

display:true

}

}

}

});

}


function heure(){

let maintenant = new Date();

let h = maintenant.toLocaleTimeString();

let zone = document.getElementById("heure");

if(zone){

zone.innerHTML = h;

}

}

setInterval(heure,1000);


// ===============================
// MENU ACTIF
// ===============================

const menu = document.querySelectorAll(".sidebar ul li");

menu.forEach(item => {

    item.addEventListener("click", function () {

        menu.forEach(i => i.classList.remove("active"));

        this.classList.add("active");

    });

});

// ===============================
// CONFIRMATION DE DECONNEXION
// ===============================

const logout = document.querySelector(".logout");

if(logout){

logout.addEventListener("click", function(e){

e.preventDefault();

let rep = confirm("Voulez-vous vraiment vous déconnecter ?");

if(rep){

window.location.href="connexion.html";

}

});

}

// ===============================
// ANIMATION DES CARTES
// ===============================

const cards = document.querySelectorAll(".card");

cards.forEach(card=>{

card.addEventListener("mouseover",()=>{

card.style.transform="translateY(-10px)";
card.style.transition=".3s";

});

card.addEventListener("mouseout",()=>{

card.style.transform="translateY(0px)";

});

});

// ===============================
// RECHERCHE DANS LE TABLEAU
// ===============================

const recherche = document.getElementById("recherche");

if(recherche){

recherche.addEventListener("keyup",function(){

let valeur = this.value.toLowerCase();

let lignes = document.querySelectorAll("tbody tr");

lignes.forEach(function(ligne){

let texte = ligne.textContent.toLowerCase();

if(texte.indexOf(valeur)>-1){

ligne.style.display="";

}else{

ligne.style.display="none";

}

});

});

}