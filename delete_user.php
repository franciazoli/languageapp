<?php
// Adatbázis kapcsolat
$connect = mysqli_connect('db', 'php_docker', 'password', 'php_docker');

if (!$connect) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "DELETE FROM users_table WHERE id = $id";

    if (mysqli_query($connect, $query)) {
        // Sikeres törlés esetén átirányítjuk a users.php oldalra
        header('Location: users.php');
        exit();
    } else {
        echo "Error deleting record: " . mysqli_error($connect);
    }
} else {
    echo "Invalid ID.";
}
