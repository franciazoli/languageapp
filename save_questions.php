<?php
$connect = mysqli_connect(
    'db', // Service name
    'php_docker', // Username
    'password', // Password
    'php_docker' // Database
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Előkészített lekérdezés
    $stmt = mysqli_prepare(
        $connect,
        "UPDATE questions_table 
         SET question = ?, ans1 = ?, ans2 = ?, ans3 = ?, ans4 = ?, correct_ans = ? 
         WHERE id = ?"
    );

    // Végigmegyünk minden beküldött adaton
    foreach ($_POST['question'] as $id => $question) {
        $ans1 = $_POST['ans1'][$id];
        $ans2 = $_POST['ans2'][$id];
        $ans3 = $_POST['ans3'][$id];
        $ans4 = $_POST['ans4'][$id];
        $correct_ans = (int)$_POST['correct_ans'][$id];

        // Paraméterek kötése az előkészített lekérdezéshez
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssi', // A paraméterek típusa: s = string, i = integer
            $question,
            $ans1,
            $ans2,
            $ans3,
            $ans4,
            $correct_ans,
            $id
        );

        // Lekérdezés végrehajtása
        mysqli_stmt_execute($stmt);
    }

    // Lekérdezés bezárása
    mysqli_stmt_close($stmt);

    // Átirányítás vissza a questions.php-re
    header('Location: questions.php');
    exit();
}
?>
