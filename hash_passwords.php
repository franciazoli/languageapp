<?php
$connect = mysqli_connect(
    'db', 
    'php_docker',
    'password',
    'php_docker'
);

$query = "SELECT * FROM users_table";
$result = mysqli_query($connect, $query);

while ($user = mysqli_fetch_assoc($result)) {
    $hashed_password = password_hash($user['password'], PASSWORD_BCRYPT);
    $update_query = "UPDATE users_table SET password = '$hashed_password' WHERE id = {$user['id']}";
    mysqli_query($connect, $update_query);
}

echo "Jelszavak sikeresen titkosítva.";
?>
