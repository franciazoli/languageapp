<?php
$connect = mysqli_connect(
    'db', // Service name
    'php_docker', // Username
    'password', // Password
    'php_docker' // Database
);

// Ellenőrzés, hogy minden mező ki van-e töltve
if (isset($_POST['question'], $_POST['ans1'], $_POST['ans2'], $_POST['ans3'], $_POST['ans4'], $_POST['correct_ans'])) {
    $question = mysqli_real_escape_string($connect, $_POST['question']);
    $ans1 = mysqli_real_escape_string($connect, $_POST['ans1']);
    $ans2 = mysqli_real_escape_string($connect, $_POST['ans2']);
    $ans3 = mysqli_real_escape_string($connect, $_POST['ans3']);
    $ans4 = mysqli_real_escape_string($connect, $_POST['ans4']);
    $correct_ans = (int)$_POST['correct_ans'];

    // Lekérdezés az új kérdés hozzáadásához
    $query = "INSERT INTO questions_table (question, ans1, ans2, ans3, ans4, correct_ans) 
              VALUES ('$question', '$ans1', '$ans2', '$ans3', '$ans4', $correct_ans)";
    
    if (mysqli_query($connect, $query)) {
        // Sikeres hozzáadás után átirányítás a főoldalra
        header('Location: questions.php');
        exit();
    } else {
        echo "Error: " . mysqli_error($connect);
    }
}
?>
