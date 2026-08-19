
<?php
    $conn= mysqli_connect ('localhost:3306','root','','provenderie');
    if(!$conn){
        die("echec de connexion:" . mysqli_connect_error());
    }  else{
        //    echo "connexion reussi";
    }
?>    