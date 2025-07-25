<?php
$servername = "localhost";
$username = "root";       // you're using root locally with no password
$password = "";           // leave blank if root has no password
$database = "property_management"; // update if your DB name differs

$conn = new mysqli($servername, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}
?>
