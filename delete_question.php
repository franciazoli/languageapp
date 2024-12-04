<?php
$connect = mysqli_connect(
    'db', // Service name
    'php_docker', // Username
    'password', // Password
    'php_docker' // Database
);

// Ellenőrizze, hogy van-e 'id' paraméter az URL-ben
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Törlés lekérdezés
    $query = "DELETE FROM questions_table WHERE id = $id";
    
    if (mysqli_query($connect, $query)) {
        // Sikeres törlés után átirányítás a főoldalra
        header('Location: index.php');
        exit();
    } else {
        echo "Error: " . mysqli_error($connect);
    }
} else {
    echo "No ID provided!";
}
?>
