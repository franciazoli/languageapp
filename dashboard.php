<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get user permission for access control
$userPermission = $_SESSION['user_permission'] ?? 'student';
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container my-5">
        <!-- Header -->
        <header class="mb-4 text-center">
            <h1 class="mb-3">Quiz Application</h1>
            <p class="lead mb-0">Test your knowledge with our quiz!</p>
        </header>

        <!-- Dashboard Card -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="mb-0">Főoldal</h2>
            </div>
            <div class="card-body p-4">
                <h3 class="card-title mb-3">Üdv, <?php echo htmlspecialchars($_SESSION['user_email']); ?>!</h3>
                <p class="card-text mb-4">Hozzáférési szintje: <?php echo htmlspecialchars($_SESSION['user_permission']); ?></p>
                
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="d-grid gap-3">
                            <!-- All users can access these options -->
                            <a href="quiz.php" class="btn btn-primary btn-lg">Kvíz Kitöltése!</a>
                            <a href="quiz.php?mode=leaderboard" class="btn btn-success">Ranglétra</a>
                            
                            <?php if ($userPermission == 'admin' || $userPermission == 'teacher'): ?>
                            <!-- Only admins and teachers can access question management -->
                            <a href="question_management.php" class="btn btn-secondary">Kérdések Kezelése</a>
                            <?php endif; ?>
                            
                            <?php if ($userPermission == 'admin'): ?>
                            <!-- Only admins can access user management -->
                            <a href="user_management.php" class="btn btn-secondary">Felhasználók Kezelése</a>
                            <?php endif; ?>
                            
                            <a href="logout.php" class="btn btn-danger">Kijelentkezés</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center text-muted">
                <small>Válasszon egy opciót.</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>