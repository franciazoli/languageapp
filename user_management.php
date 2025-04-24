<?php
session_start();

// --- DATABASE CONNECTION ---
function connect_to_database() {
    $db = new mysqli('db', 'php_docker', 'password', 'php_docker');
    
    if ($db->connect_error) {
        die("Database connection failed: " . $db->connect_error);
    }
    
    return $db;
}

// --- SECURITY CHECKS ---
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }
}

function check_permission() {
    $userPermission = $_SESSION['user_permission'] ?? 'student';
    if ($userPermission != 'admin') {
        header('Location: dashboard.php');
        exit();
    }
}

// --- EMAIL & PASSWORD VALIDATION ---
function is_valid_email($email) {
    // PHP's built-in email validator
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_valid_password($password) {
    // Check password strength: 8+ chars with uppercase, lowercase, number, and special char
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password) && 
           preg_match('/[^A-Za-z0-9]/', $password);
}

// --- CHECK FOR DUPLICATE EMAIL ---
function is_email_already_used($db, $email, $exclude_user_id = null) {
    // Start our query - will check if email exists
    $sql = "SELECT id FROM users_table WHERE email = ?";
    $params = [$email];
    $types = "s";
    
    // If we're updating a user, don't count their own email as a duplicate
    if ($exclude_user_id !== null) {
        $sql .= " AND id != ?";
        $params[] = $exclude_user_id;
        $types .= "i";
    }
    
    // Run the query
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->store_result();
    
    // Check if we found any results
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

// --- USER MANAGEMENT FUNCTIONS ---

// Add this function to your user_management.php
// Add this new function to find the next available ID
function get_next_available_id($db) {
    // Get the current maximum ID below 999
    $result = $db->query("SELECT MAX(id) as max_id FROM users_table WHERE id < 999");
    $row = $result->fetch_assoc();
    $max_id = (int)$row['max_id'];
    
    // Return the next available ID
    return $max_id + 1;
}

// Modified create_user function
function create_user($db, $email, $password, $permission) {
    $errors = [];
   
    if (!is_valid_email($email)) {
        $errors[] = "Please enter a valid email address";
    }
   
    if (is_email_already_used($db, $email)) {
        $errors[] = "This email is already registered";
    }
   
    if (!is_valid_password($password)) {
        $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, numbers, and special characters";
    }
   
    if (!empty($errors)) {
        return $errors;
    }
   
    $next_id = get_next_available_id($db);
   
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
   
    $stmt = $db->prepare("INSERT INTO users_table (id, email, password, permission) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $next_id, $email, $hashed_password, $permission);
   
    $success = $stmt->execute();
    $stmt->close();
   
    if (!$success) {
        return ["Database error: " . $db->error];
    }
   
    return true;
}

// Update an existing user
function update_user($db, $id, $email, $password, $permission) {
    $errors = [];
    
    // Check for valid email
    if (!is_valid_email($email)) {
        $errors[] = "Please enter a valid email address";
    }
    
    // Check if email is already used by another user
    if (is_email_already_used($db, $email, $id)) {
        $errors[] = "This email is already used by another account";
    }
    
    // If password provided, check strength
    if (!empty($password) && !is_valid_password($password)) {
        $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, numbers, and special characters";
    }
    
    // Return any errors found
    if (!empty($errors)) {
        return $errors;
    }
    
    // If validation passes, update user
    if (!empty($password)) {
        // If new password provided, hash it and update all fields
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users_table SET email = ?, password = ?, permission = ? WHERE id = ?");
        $stmt->bind_param("sssi", $email, $hashed_password, $permission, $id);
    } else {
        // Otherwise, just update email and permission
        $stmt = $db->prepare("UPDATE users_table SET email = ?, permission = ? WHERE id = ?");
        $stmt->bind_param("ssi", $email, $permission, $id);
    }
    
    $success = $stmt->execute();
    $stmt->close();
    
    // Check if database operation succeeded
    if (!$success) {
        return ["Database error: " . $db->error];
    }
    
    return true; // Success!
}

// Delete a user
function delete_user($db, $id) {
    $stmt = $db->prepare("DELETE FROM users_table WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Get all users from database
function get_users($db) {
    $result = $db->query("SELECT * FROM users_table");
    
    if (!$result) {
        return [];
    }
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

// --- MAIN CODE ---
$db = connect_to_database();
$errors = [];
$success_message = "";
$current_page = $_GET['action'] ?? 'list';  // Default to list view

// Ensure user is logged in for protected pages
if (in_array($current_page, ['list', 'add', 'edit', 'delete'])) {
    require_login();
    check_permission();
}

// Handle form submissions for adding users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $result = create_user(
        $db, 
        $_POST['email'] ?? '', 
        $_POST['password'] ?? '', 
        $_POST['permission'] ?? ''
    );
    
    if ($result === true) {
        $success_message = "User added successfully!";
    } else {
        $errors = $result;
    }
}

// Handle form submissions for updating users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    foreach ($_POST['id'] as $key => $id) {
        $result = update_user(
            $db,
            $id,
            $_POST['email'][$key] ?? '',
            $_POST['password'][$key] ?? '',
            $_POST['permission'][$key] ?? ''
        );
        
        if ($result !== true) {
            $errors = array_merge($errors, $result);
        }
    }
    
    if (empty($errors)) {
        $success_message = "Users updated successfully!";
    }
}

// Handle user deletion
if ($current_page === 'delete' && isset($_GET['id'])) {
    if (delete_user($db, $_GET['id'])) {
        $success_message = "User deleted successfully!";
    } else {
        $errors[] = "Failed to delete user";
    }
    
    // Redirect back to list view
    header('Location: user_management.php');
    exit();
}

// Get all users for display
$users = get_users($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container my-5">
        <header class="mb-4 text-center">
            <h1 class="mb-3">Quiz Application</h1>
            <p class="lead mb-0">Test your knowledge with our quiz!</p>
        </header>
        <h1 class="text-center mb-4">Felhasználók Kezelése</h1>
        
        <!-- Show error messages if any -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Show success message if any -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <!-- "ADD NEW USER" FORM -->
        <?php if ($current_page === 'add'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="mb-0">Új Felhasználó Hozzáadása</h2>
                </div>
                <div class="card-body">
                    <form action="user_management.php" method="POST">
                        <input type="hidden" name="action" value="add_user">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email cím</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                            <div class="form-text">Érvényes email cím szükséges.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Jelszó</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                            <div class="form-text">Legalább 8 karakter, kis- és nagy betűkkel illetve legalább egy speciális karakter szükséges.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="permission" class="form-label">Hozzáférési szint:</label>
                            <input type="text" name="permission" id="permission" class="form-control" required>
                        </div>
                        
                        <div class="d-flex justify-content-center gap-2">
                            <a href="user_management.php" class="btn btn-secondary">Mégsem</a>
                            <button type="submit" class="btn btn-success">Felhasználó Hozzáadása</button>
                        </div>
                    </form>
                </div>
            </div>
        
        <!-- "LIST ALL USERS" VIEW -->
        <?php elseif ($current_page === 'list'): ?>
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <a href="user_management.php?action=add" class="btn btn-success">Felhasználó Hozzáadása</a>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-primary">Vissza a Főoldalra</a>
                </div>
            </div>
            
            <?php if (!empty($users)): ?>
                <form action="user_management.php" method="POST">
                    <input type="hidden" name="action" value="update_user">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Email cím</th>
                                    <th>Jelszó</th>
                                    <th>Hozzáférési szint</th>
                                    <th>Műveletek</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $index => $user): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($user['id']) ?>
                                            <input type="hidden" name="id[<?= $index ?>]" value="<?= htmlspecialchars($user['id']) ?>">
                                        </td>
                                        <td>
                                            <input type="email" name="email[<?= $index ?>]" class="form-control" 
                                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                                        </td>
                                        <td>
                                            <input type="password" name="password[<?= $index ?>]" class="form-control" 
                                                   placeholder="Hagyja üresen ha nem változtatna">
                                        </td>
                                        <td>
                                            <input type="text" name="permission[<?= $index ?>]" class="form-control" 
                                                   value="<?= htmlspecialchars($user['permission']) ?>" required>
                                        </td>
                                        <td>
                                            <a href="user_management.php?action=delete&id=<?= $user['id'] ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Biztosan ki akarja törölni ezt a felhasználót?');">
                                                Törlés
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        <button type="submit" class="btn btn-primary">Mentés</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-info">
                    Nincs megjeleníthető felhasználó az adatbázisban.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>