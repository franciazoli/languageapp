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
    if ($userPermission == 'student') {
        header('Location: dashboard.php');
        exit();
    }
}

// --- CHECK INPUTS ARE VALID ---
function is_valid_input($question, $answer1, $answer2, $answer3, $answer4, $correct_answer) {
    $errors = [];
    
    if (empty(trim($question))) {
        $errors[] = "A kérdés nem lehet üres.";
    }
    
    if (empty(trim($answer1)) || empty(trim($answer2)) || 
        empty(trim($answer3)) || empty(trim($answer4))) {
        $errors[] = "Az összes válasz mezőt ki kell tölteni.";
    }
    
    if (!is_numeric($correct_answer) || $correct_answer < 1 || $correct_answer > 4) {
        $errors[] = "A helyes válasz 1 és 4 között lehet";
    }
    
    return $errors;
}

// --- DATABASE FUNCTIONS ---

// Save a new question to database
function save_new_question($db, $question, $ans1, $ans2, $ans3, $ans4, $correct_ans) {
    $errors = is_valid_input($question, $ans1, $ans2, $ans3, $ans4, $correct_ans);
    if (!empty($errors)) {
        return $errors;
    }
    
    $stmt = $db->prepare("INSERT INTO questions_table (question, ans1, ans2, ans3, ans4, correct_ans) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $question, $ans1, $ans2, $ans3, $ans4, $correct_ans);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success ? true : ["Database error: " . $db->error];
}

// Update existing question
function save_edited_question($db, $id, $question, $ans1, $ans2, $ans3, $ans4, $correct_ans) {
    // Check for errors first
    $errors = is_valid_input($question, $ans1, $ans2, $ans3, $ans4, $correct_ans);
    if (!empty($errors)) {
        return $errors;
    }
    
    // Update database if no errors
    $stmt = $db->prepare("UPDATE questions_table SET question = ?, ans1 = ?, ans2 = ?, ans3 = ?, ans4 = ?, correct_ans = ? WHERE id = ?");
    $stmt->bind_param("sssssii", $question, $ans1, $ans2, $ans3, $ans4, $correct_ans, $id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success ? true : ["Database error: " . $db->error];
}

// Remove a question
function remove_question($db, $id) {
    $stmt = $db->prepare("DELETE FROM questions_table WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Get all questions
function get_questions($db) {
    $result = $db->query("SELECT * FROM questions_table");
    
    if (!$result) {
        return [];
    }
    
    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    
    return $questions;
}

// --- MAIN CODE ---
$db = connect_to_database();
$errors = [];
$success_message = "";
$current_page = $_GET['action'] ?? 'list'; // Default to list view

// Make sure user is logged in
if (in_array($current_page, ['list', 'add', 'edit', 'delete'])) {
    require_login();
    check_permission();
}

// Handle adding questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $result = save_new_question(
        $db, 
        $_POST['question'] ?? '', 
        $_POST['ans1'] ?? '', 
        $_POST['ans2'] ?? '', 
        $_POST['ans3'] ?? '', 
        $_POST['ans4'] ?? '', 
        $_POST['correct_ans'] ?? 1
    );
    
    if ($result === true) {
        $success_message = "Kérdés sikeresen felvéve!";
    } else {
        $errors = $result;
    }
}

// Handle saving edited questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_questions') {
    $update_success = true;
    
    foreach ($_POST['id'] as $key => $id) {
        $result = save_edited_question(
            $db,
            $id,
            $_POST['question'][$key] ?? '',
            $_POST['ans1'][$key] ?? '',
            $_POST['ans2'][$key] ?? '',
            $_POST['ans3'][$key] ?? '',
            $_POST['ans4'][$key] ?? '',
            $_POST['correct_ans'][$key] ?? 1
        );
        
        if ($result !== true) {
            $errors = array_merge($errors, $result);
            $update_success = false;
        }
    }
    
    if ($update_success) {
        $success_message = "Változtatások elmentve!";
    }
}

// Handle deleting questions
if ($current_page === 'delete' && isset($_GET['id'])) {
    if (remove_question($db, $_GET['id'])) {
        $_SESSION['success_message'] = "Kérdés sikeresen etávolítva!";
    } else {
        $_SESSION['error_messages'] = ["Hiba a kérdés törlésekör"];
    }
    
    header('Location: question_management.php');
    exit();
}

// Check for messages from redirects
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_messages'])) {
    $errors = $_SESSION['error_messages'];
    unset($_SESSION['error_messages']);
}

// Get all questions for display
$questions = get_questions($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Question Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container my-5">
        <header class="mb-4 text-center">
            <h1 class="mb-3">Quiz Application</h1>
            <p class="lead mb-0">Test your knowledge with our quiz!</p>
        </header>
        <h1 class="text-center mb-4">Kvíz Kérdések Kezelése</h1>
        
        <!-- Show any error messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Show success message -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <!-- "ADD NEW QUESTION" FORM -->
        <?php if ($current_page === 'add'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="mb-0">Új Kérdés Hozzáadása</h2>
                </div>
                <div class="card-body">
                    <form action="question_management.php" method="POST">
                        <input type="hidden" name="action" value="add_question">
                        
                        <div class="mb-3">
                            <label for="question" class="form-label">Kérdés</label>
                            <input type="text" name="question" id="question" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ans1" class="form-label">Válasz 1</label>
                            <textarea name="ans1" id="ans1" class="form-control" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ans2" class="form-label">Válasz 2</label>
                            <textarea name="ans2" id="ans2" class="form-control" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ans3" class="form-label">Válasz 3</label>
                            <textarea name="ans3" id="ans3" class="form-control" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ans4" class="form-label">Válasz 4</label>
                            <textarea name="ans4" id="ans4" class="form-control" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="correct_ans" class="form-label">Helyes Válasz (1-4)</label>
                            <input type="number" name="correct_ans" id="correct_ans" class="form-control" min="1" max="4" value="1" required>
                        </div>
                        
                        <div class="d-flex justify-content-center gap-2">
                            <a href="question_management.php" class="btn btn-secondary">Mégsem</a>
                            <button type="submit" class="btn btn-success">Kérdés Hozzáadása</button>
                        </div>
                    </form>
                </div>
            </div>
        
        <!-- "LIST ALL QUESTIONS" VIEW -->
        <?php elseif ($current_page === 'list'): ?>
            <div class="d-flex justify-content-between mb-3">
                <a href="question_management.php?action=add" class="btn btn-success">Kérdés Hozzáadása</a>
                <a href="dashboard.php" class="btn btn-primary">Vissza a Főoldalra</a>
            </div>
            
            <?php if (!empty($questions)): ?>
                <form action="question_management.php" method="POST">
                    <input type="hidden" name="action" value="update_questions">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Kérdés</th>
                                    <th>Válasz 1</th>
                                    <th>Válasz 2</th>
                                    <th>Válasz 3</th>
                                    <th>Válasz 4</th>
                                    <th>Correct Válasz</th>
                                    <th>Műveletek</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $index => $question): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($question['id']) ?>
                                            <input type="hidden" name="id[<?= $index ?>]" value="<?= htmlspecialchars($question['id']) ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="question[<?= $index ?>]" class="form-control" 
                                                   value="<?= htmlspecialchars($question['question']) ?>" required>
                                        </td>
                                        <td>
                                            <textarea name="ans1[<?= $index ?>]" class="form-control" required><?= htmlspecialchars($question['ans1']) ?></textarea>
                                        </td>
                                        <td>
                                            <textarea name="ans2[<?= $index ?>]" class="form-control" required><?= htmlspecialchars($question['ans2']) ?></textarea>
                                        </td>
                                        <td>
                                            <textarea name="ans3[<?= $index ?>]" class="form-control" required><?= htmlspecialchars($question['ans3']) ?></textarea>
                                        </td>
                                        <td>
                                            <textarea name="ans4[<?= $index ?>]" class="form-control" required><?= htmlspecialchars($question['ans4']) ?></textarea>
                                        </td>
                                        <td>
                                            <input type="number" name="correct_ans[<?= $index ?>]" class="form-control" 
                                                   value="<?= htmlspecialchars($question['correct_ans']) ?>" min="1" max="4" required>
                                        </td>
                                        <td>
                                            <a href="question_management.php?action=delete&id=<?= $question['id'] ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Biztosan ki akarja törölni ezt a kérdést?');">
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
                    Nincsenek kérdések az adatbázisban.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>