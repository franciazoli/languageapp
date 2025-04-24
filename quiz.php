<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get user permission level
$userPermission = $_SESSION['user_permission'] ?? 'student';
$isGuest = $_SESSION['is_guest'] ?? false;

// Database connection
function connect_to_database() {
    $db = new mysqli('db', 'php_docker', 'password', 'php_docker');
    
    if ($db->connect_error) {
        die("Database connection failed: " . $db->connect_error);
    }
    
    return $db;
}

$db = connect_to_database();

// Ensure guest user exists in the database
function ensure_guest_user_exists($db) {
    // Check if the guest system user exists
    $query = "SELECT id FROM users_table WHERE id = 999";
    $result = $db->query($query);
    
    if (!$result || $result->num_rows == 0) {
        // Create guest system user if it doesn't exist
        $query = "INSERT INTO users_table (id, email, password, permission) VALUES 
                 (999, 'guest@system.local', 'not_accessible', 'guest')";
        $db->query($query);
    }
}

// Make sure the guest user exists
ensure_guest_user_exists($db);

// Functions for quiz management
function get_random_questions($db, $count = 10) {
    $result = $db->query("SELECT * FROM questions_table ORDER BY RAND() LIMIT $count");
    
    if (!$result) {
        return [];
    }
    
    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    
    return $questions;
}

function save_quiz_result($db, $user_id, $user_email, $score, $time_taken, $is_guest = 0) {
    if ($is_guest) {
        $_SESSION['guest_email'] = $user_email;
        $user_id = 999;
    }
    
    $stmt = $db->prepare("INSERT INTO quiz_results (user_id, score, time_taken, completion_date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iid", $user_id, $score, $time_taken);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

function get_leaderboard($db, $limit = 20) {
    $sql = "SELECT qr.*, u.email AS user_email, u.permission 
            FROM quiz_results qr 
            LEFT JOIN users_table u ON qr.user_id = u.id 
            ORDER BY qr.score DESC, qr.time_taken ASC LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $leaderboard = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['user_id'] == 999) {
            if (isset($_SESSION['is_guest']) && $_SESSION['is_guest'] && isset($_SESSION['guest_email'])) {
                $row['user_email'] = $_SESSION['guest_email'];
            } else {
                $row['user_email'] = 'Guest User';
            }
            $row['is_guest'] = 1;
        } else {
            $row['is_guest'] = 0;
        }
        $leaderboard[] = $row;
    }
    
    return $leaderboard;
}

// Determine the current state
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'start';

// Process quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $score = 0;
    $time_taken = floatval($_POST['time_taken']);
    
    // Calculate score
    foreach ($_POST['question'] as $q_id => $answer) {
        $correct_answer = $_POST['correct_answer'][$q_id];
        if ($answer == $correct_answer) {
            $score++;
        }
    }
    
    // Save result to database
    $user_id = $_SESSION['user_id'];
    $user_email = $_SESSION['user_email'] ?? '';
    $is_guest = $_SESSION['is_guest'] ?? false;
    
    save_quiz_result($db, $user_id, $user_email, $score, $time_taken, $is_guest ? 1 : 0);
    
    // Redirect to results page
    header('Location: quiz.php?mode=result&score=' . $score . '&time=' . $time_taken);
    exit();
}

// Get questions if in quiz mode
$questions = [];
if ($mode === 'quiz') {
    $questions = get_random_questions($db);
    
    // If no questions found, show an error
    if (empty($questions)) {
        $mode = 'error';
    }
}

// Get leaderboard data for leaderboard and results modes
$leaderboard = [];
if ($mode === 'leaderboard' || $mode === 'result') {
    $leaderboard = get_leaderboard($db);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .timer {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .question-card {
            min-height: 350px;
        }
        .answer-option {
            background-color: #a3b18a;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .answer-option:hover {
            background-color: #588157;
        }
        .answer-option.selected {
            background-color: #588157;
            border-color: #344e41;
        }
        .progress {
            height: 10px;
        }
        .hide {
            display: none;
        }
        .guest-badge {
            font-size: 0.8rem;
            margin-left: 5px;
            padding: 2px 5px;
            border-radius: 3px;
            background-color: #f8f9fa;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <!-- Header -->
        <header class="mb-4 text-center">
            <h1 class="mb-3">Quiz Application</h1>
            <p class="lead mb-0">Test your knowledge with our quiz!</p>
        </header>

        <?php if ($mode === 'start'): ?>
            <!-- Start Page -->
            <div class="card shadow-sm">
                <div class="card-body text-center p-5">
                    <h2 class="card-title mb-4">Üdv a kvízben!</h2>
                    <p class="card-text mb-4">Tíz darab véletlenszerű kérdést fog kapni. Próbálja megválaszolni minél gyorsabban és pontosabban.</p>
                    <p class="card-text mb-4">A pontszáma és az ideje fel lesz véve a ranglétrában</p>
                    <div class="d-grid gap-2 col-6 mx-auto">
                        <a href="quiz.php?mode=quiz" class="btn btn-primary btn-lg">Kvíz elkezdése</a>
                        <a href="quiz.php?mode=leaderboard" class="btn btn-secondary">Ranglétra</a>
                        <a href="dashboard.php" class="btn btn-outline-dark">Vissza a Főoldalra</a>
                    </div>
                </div>
            </div>

        <?php elseif ($mode === 'quiz'): ?>
            <!-- Quiz Page -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Timer and Progress Bar -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="progress w-75">
                            <div id="quiz-progress" class="progress-bar" role="progressbar" style="width: 10%;" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">1/10</div>
                        </div>
                        <div class="timer" id="quiz-timer">00:00</div>
                    </div>

                    <!-- Quiz Form -->
                    <form id="quiz-form" action="quiz.php" method="POST">
                        <input type="hidden" name="time_taken" id="time-taken-input" value="0">
                        <input type="hidden" name="submit_quiz" value="1">
                        
                        <?php foreach($questions as $index => $question): ?>
                            <div class="question-card card mb-4 <?php echo $index > 0 ? 'hide' : ''; ?>" data-question-index="<?php echo $index; ?>">
                                <div class="card-header">
                                    <h5 class="mb-0">Question <?php echo $index + 1; ?> of 10</h5>
                                </div>
                                <div class="card-body">
                                    <h4 class="card-title mb-4"><?php echo htmlspecialchars($question['question']); ?></h4>
                                    
                                    <input type="hidden" name="question[<?php echo $question['id']; ?>]" value="">
                                    <input type="hidden" name="correct_answer[<?php echo $question['id']; ?>]" value="<?php echo $question['correct_ans']; ?>">
                                    
                                    <div class="answer-options">
                                        <?php for($i = 1; $i <= 4; $i++): ?>
                                            <div class="answer-option card mb-2" data-value="<?php echo $i; ?>">
                                                <div class="card-body">
                                                    <?php echo htmlspecialchars($question['ans'.$i]); ?>
                                                </div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <?php if($index < count($questions) - 1): ?>
                                        <button type="button" class="btn btn-primary next-btn" disabled>Következő kérdés</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-success finish-btn" disabled>Kvíz befejezése</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </form>
                </div>
            </div>

        <?php elseif ($mode === 'result'): ?>
            <!-- Results Page -->
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center p-5">
                    <h2 class="card-title mb-3">Quiz Completed!</h2>
                    <div class="display-1 mb-4"><?php echo $_GET['score']; ?>/10</div>
                    <p class="lead">Time taken: <strong><?php echo number_format($_GET['time'], 2); ?> seconds</strong></p>
                    <div class="d-grid gap-2 col-6 mx-auto mt-4">
                        <a href="quiz.php?mode=quiz" class="btn btn-primary">Kvíz megpróbálása újra</a>
                        <a href="quiz.php?mode=leaderboard" class="btn btn-secondary">Ranglétra</a>
                        <a href="dashboard.php" class="btn btn-outline-dark">Vissza a Főoldalra</a>
                    </div>
                </div>
            </div>
            
            <!-- Show Leaderboard -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="mb-0">Ranglétra</h3>
                </div>
                <div class="card-body">
                    <?php if(!empty($leaderboard)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Helyezés</th>
                                        <th>Felhasználó</th>
                                        <th>Pont</th>
                                        <th>Idő (mp)</th>
                                        <th>Dátum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($leaderboard as $rank => $result): ?>
                                        <?php 
                                        // Check if this is the current user's recent result
                                        $isCurrentUser = false;
                                        if (!$isGuest && $result['user_id'] == $_SESSION['user_id']) {
                                            if ($result['score'] == $_GET['score'] && abs($result['time_taken'] - $_GET['time']) < 0.1) {
                                                $isCurrentUser = true;
                                            }
                                        } else if ($isGuest && $result['user_id'] == 999) {
                                            if ($result['score'] == $_GET['score'] && abs($result['time_taken'] - $_GET['time']) < 0.1) {
                                                $isCurrentUser = true;
                                            }
                                        }
                                        ?>
                                        <tr class="<?php echo $isCurrentUser ? 'table-primary' : ''; ?>">
                                            <td><?php echo $rank + 1; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($result['user_email']); ?>
                                                <?php if (isset($result['is_guest']) && $result['is_guest'] == 1): ?>
                                                    <span class="guest-badge">Vendég</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $result['score']; ?>/10</td>
                                            <td><?php echo number_format($result['time_taken'], 2); ?></td>
                                            <td><?php echo date('M j, Y, g:i a', strtotime($result['completion_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">Még nincsenek megjeleníthető eredmények.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($mode === 'leaderboard'): ?>
            <!-- Leaderboard Only Page -->
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Ranglétra</h3>
                    <div>
                        <a href="quiz.php" class="btn btn-primary">Kvíz elkezdése</a>
                        <a href="dashboard.php" class="btn btn-outline-dark">Vissza a Főoldalra</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(!empty($leaderboard)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Helyezés</th>
                                        <th>Felhasználó</th>
                                        <th>Pont</th>
                                        <th>Idő (mp)</th>
                                        <th>Dátum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($leaderboard as $rank => $result): ?>
                                        <?php 
                                        $isCurrentUser = false;
                                        if (!$isGuest && $result['user_id'] == $_SESSION['user_id']) {
                                            $isCurrentUser = true;
                                        } else if ($isGuest && $result['user_id'] == 999 && isset($_SESSION['guest_email']) && $result['user_email'] == $_SESSION['guest_email']) {
                                            $isCurrentUser = true;
                                        }
                                        ?>
                                        <tr class="<?php echo $isCurrentUser ? 'table-primary' : ''; ?>">
                                            <td><?php echo $rank + 1; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($result['user_email']); ?>
                                                <?php if (isset($result['is_guest']) && $result['is_guest'] == 1): ?>
                                                    <span class="guest-badge">Vendég</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $result['score']; ?>/10</td>
                                            <td><?php echo number_format($result['time_taken'], 2); ?></td>
                                            <td><?php echo date('M j, Y, g:i a', strtotime($result['completion_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">Még nincsenek megjeleníthető eredmények.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($mode === 'error'): ?>
            <!-- Error Page -->
            <div class="card shadow-sm">
                <div class="card-body text-center p-5">
                    <h2 class="card-title mb-4 text-danger">Hiba</h2>
                    <p class="card-text mb-4">Nincsenek kérdések az adatbázisban. Kérjen meg egy tanárt, hogy töltsön fel kérdéseket.</p>
                    <div class="d-grid gap-2 col-6 mx-auto">
                        <a href="dashboard.php" class="btn btn-primary">Vissza a Főoldalra</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($mode === 'quiz'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables
            let currentQuestion = 0;
            const totalQuestions = <?php echo count($questions); ?>;
            let startTime = Date.now();
            let timerInterval;
            
            // Elements
            const quizForm = document.getElementById('quiz-form');
            const questions = document.querySelectorAll('.question-card');
            const progressBar = document.getElementById('quiz-progress');
            const timerElement = document.getElementById('quiz-timer');
            const timeInput = document.getElementById('time-taken-input');
            
            // Start timer
            startTimer();
            
            // Handle answer selection
            document.querySelectorAll('.answer-option').forEach(option => {
                option.addEventListener('click', function() {
                    // Get question card and input
                    const questionCard = this.closest('.question-card');
                    const questionIndex = questionCard.dataset.questionIndex;
                    const questionInputs = questionCard.querySelectorAll('input[type="hidden"]');
                    const questionId = questionInputs[0].name.match(/\d+/)[0];
                    
                    // Remove selected class from all options in this question
                    questionCard.querySelectorAll('.answer-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Add selected class to this option
                    this.classList.add('selected');
                    
                    // Set the value in the hidden input
                    document.querySelector(`input[name="question[${questionId}]"]`).value = this.dataset.value;
                    
                    // Enable the next/finish button
                    const nextBtn = questionCard.querySelector('.next-btn, .finish-btn');
                    if (nextBtn) {
                        nextBtn.disabled = false;
                    }
                });
            });
            
            // Handle next button clicks
            document.querySelectorAll('.next-btn').forEach(button => {
                button.addEventListener('click', function() {
                    // Hide current question
                    questions[currentQuestion].classList.add('hide');
                    
                    // Show next question
                    currentQuestion++;
                    questions[currentQuestion].classList.remove('hide');
                    
                    // Update progress bar
                    updateProgress();
                });
            });
            
            // Handle finish button click
            document.querySelector('.finish-btn')?.addEventListener('click', function() {
                // Stop timer
                clearInterval(timerInterval);
                
                // Set final time
                const timeTaken = (Date.now() - startTime) / 1000;
                timeInput.value = timeTaken.toFixed(2);
                
                // Submit form
                quizForm.submit();
            });
            
            // Timer function
            function startTimer() {
                timerInterval = setInterval(function() {
                    const elapsedTime = (Date.now() - startTime) / 1000;
                    const minutes = Math.floor(elapsedTime / 60);
                    const seconds = Math.floor(elapsedTime % 60);
                    
                    timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                }, 1000);
            }
            
            // Update progress bar
            function updateProgress() {
                const percentage = ((currentQuestion + 1) / totalQuestions) * 100;
                progressBar.style.width = `${percentage}%`;
                progressBar.setAttribute('aria-valuenow', percentage);
                progressBar.textContent = `${currentQuestion + 1}/${totalQuestions}`;
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>