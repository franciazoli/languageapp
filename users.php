<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Adatbázis kapcsolat
$connect = mysqli_connect(
    'db', // Service name
    'php_docker', // Username
    'password', // Password
    'php_docker' // Database
);

if (!$connect) {
    die("Database connection failed: " . mysqli_connect_error());
}

$table_name = "users_table";

// Felhasználók lekérdezése
$query = "SELECT * FROM $table_name";
$response = mysqli_query($connect, $query);

if (!$response) {
    die("Query failed: " . mysqli_error($connect));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Table</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center mb-4">Users Table</h1>

        <!-- Add New User Button -->
        <div class="text-center mb-3">
            <a href="add_user.php" class="btn btn-success">Add New User</a>
        </div>

        <?php if (mysqli_num_rows($response) > 0): ?>
            <form action="save_users.php" method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Password</th>
                                <th>Permission</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($response)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']); ?></td>
                                    <td>
                                        <input type="email" name="email[<?= $row['id']; ?>]" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($row['email']); ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="password[<?= $row['id']; ?>]" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($row['password']); ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="permission[<?= $row['id']; ?>]" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($row['permission']); ?>">
                                    </td>
                                    <td>
                                        <a href="delete_user.php?id=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Biztosan törlöd ezt a felhasználót?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="dashboard.php" type="submit" class="btn btn-primary">Back</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        <?php else: ?>
            <p class="text-center text-danger">No users found in the table.</p>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
