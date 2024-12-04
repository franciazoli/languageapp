<?php
// Adatbázis kapcsolat
$connect = mysqli_connect('db', 'php_docker', 'password', 'php_docker');

if (!$connect) {
    die("Database connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $password = mysqli_real_escape_string($connect, $_POST['password']);
    $permission = mysqli_real_escape_string($connect, $_POST['permission']);

    $query = "INSERT INTO users_table (email, password, permission) VALUES ('$email', '$password', '$permission')";

    if (mysqli_query($connect, $query)) {
        header('Location: users.php');
        exit();
    } else {
        echo "Error inserting record: " . mysqli_error($connect);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center mb-4">Add New User</h1>
        <form action="add_user.php" method="POST" class="w-50 mx-auto">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="text" name="password" id="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="permission" class="form-label">Permission</label>
                <input type="text" name="permission" id="permission" class="form-control" required>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-success">Add User</button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
