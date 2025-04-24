<?php
session_start();

$connect = mysqli_connect(
    'db',
    'php_docker',
    'password',
    'php_docker'
);

// No guest user check or creation - we assume it exists

// Handle guest login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guest_login'])) {
    $guest_email = mysqli_real_escape_string($connect, $_POST['guest_email']);
    
    // Set session variables for guest
    $_SESSION['user_id'] = 999; // Use the system guest user ID
    $_SESSION['user_email'] = $guest_email;
    $_SESSION['user_permission'] = 'student';
    $_SESSION['is_guest'] = true;
    $_SESSION['guest_email'] = $guest_email; // Store guest email for display
    
    // Redirect to dashboard
    header('Location: dashboard.php');
    exit();
}

// Handle regular login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $password = $_POST['password'];

    // Lekérdezés a felhasználó ellenőrzéséhez
    $query = "SELECT * FROM users_table WHERE email = '$email'";
    $result = mysqli_query($connect, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Ellenőrizd a jelszót
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_permission'] = $user['permission'];
            $_SESSION['is_guest'] = false;

            // Átirányítás egy védett oldalra
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Helytelen jelszó.";
        }
    } else {
        $error = "Helytelen email cím.";
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <div class="container">
    <header class="mb-4 text-center">
            <h1 class="mb-3">Quiz Application</h1>
            <p class="lead mb-0">Test your knowledge with our quiz!</p>
    </header>
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title text-center mb-4">Bejelentkezés</h4>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <!-- Regular Login Form -->
                        <form method="POST" action="index.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email cím</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Jelszó</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100 mb-3">Bejelentkezés</button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <!-- Guest Option -->
                        <div class="text-center mb-3">
                            <span>vagy</span>
                        </div>
                        
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#guestModal">
                            Folytatás Vendégként
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Guest Modal -->
    <div class="modal fade" id="guestModal" tabindex="-1" aria-labelledby="guestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="guestModalLabel">Írja be a nevét vagy email címét.</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="index.php" id="guestForm">
                        <div class="mb-3">
                            <label for="guest_email" class="form-label">Ön neve vagy email címe:</label>
                            <input type="text" class="form-control" id="guest_email" name="guest_email" required>
                            <div class="form-text">Ez a ranglétrán fog megjelenni, miután kitölt egy kvízt.</div>
                        </div>
                        <input type="hidden" name="guest_login" value="1">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Mégsem</button>
                    <button type="submit" form="guestForm" class="btn btn-primary">Tovább</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>