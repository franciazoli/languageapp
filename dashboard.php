<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title">Üdvözlünk, <?php echo htmlspecialchars($_SESSION['user_email']); ?>!</h4>
                <p class="card-text">Jogosultságod: <?php echo htmlspecialchars($_SESSION['user_permission']); ?></p>
                
                <div class="mt-4">
                    <a href="questions.php" class="btn btn-primary w-100 mb-2">Kérdések listázása és szerkesztése</a>
                    <a href="users.php" class="btn btn-secondary w-100 mb-2">Felhasználók listázása és szerkesztése</a>
                    <a href="logout.php" class="btn btn-danger w-100">Kijelentkezés</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
