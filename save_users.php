<?php
// Adatbázis kapcsolat
$connect = mysqli_connect('db', 'php_docker', 'password', 'php_docker');

if (!$connect) {
    die("Database connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['email'] as $id => $email) {
        $password = mysqli_real_escape_string($connect, $_POST['password'][$id]);
        $permission = mysqli_real_escape_string($connect, $_POST['permission'][$id]);

        $email = mysqli_real_escape_string($connect, $email);
        $query = "
            UPDATE users_table 
            SET email = '$email', password = '$password', permission = '$permission' 
            WHERE id = $id
        ";
        if (!mysqli_query($connect, $query)) {
            echo "Error updating record for ID $id: " . mysqli_error($connect);
        }
    }
    header('Location: users.php');
    exit();
} else {
    echo "Invalid request method.";
}
?>
