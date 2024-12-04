<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions Table</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center mb-4">Questions Table</h1>

        <!-- Add New Question Button -->
        <div class="text-center mb-3">
            <a href="add_question.php" class="btn btn-success">Add New Question</a>
        </div>

        <?php
        $connect = mysqli_connect(
            'db', // Service name
            'php_docker', // Username
            'password', // Password
            'php_docker' // Database
        );

        $table_name = "questions_table";

        // Fetch questions
        $query = "SELECT * FROM $table_name";
        $response = mysqli_query($connect, $query);

        if ($response && mysqli_num_rows($response) > 0): ?>
            <form action="save_questions.php" method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Question</th>
                                <th>Answer 1</th>
                                <th>Answer 2</th>
                                <th>Answer 3</th>
                                <th>Answer 4</th>
                                <th>Correct Answer</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($response)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']); ?></td>
                                    <td>
                                        <input type="text" name="question[<?= $row['id']; ?>]" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($row['question']); ?>">
                                    </td>
                                    <td>
                                        <textarea name="ans1[<?= $row['id']; ?>]" 
                                                  class="form-control"><?= htmlspecialchars($row['ans1']); ?></textarea>
                                    </td>
                                    <td>
                                        <textarea name="ans2[<?= $row['id']; ?>]" 
                                                  class="form-control"><?= htmlspecialchars($row['ans2']); ?></textarea>
                                    </td>
                                    <td>
                                        <textarea name="ans3[<?= $row['id']; ?>]" 
                                                  class="form-control"><?= htmlspecialchars($row['ans3']); ?></textarea>
                                    </td>
                                    <td>
                                        <textarea name="ans4[<?= $row['id']; ?>]" 
                                                  class="form-control"><?= htmlspecialchars($row['ans4']); ?></textarea>
                                    </td>
                                    <td>
                                        <input type="number" name="correct_ans[<?= $row['id']; ?>]" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($row['correct_ans']); ?>" 
                                               min="1" max="4">
                                    </td>
                                    <td>
                                    <a href="delete_question.php?id=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Biztosan törlöd ezt a felhasználót?');">
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
            <p class="text-center text-danger">No questions found in the table.</p>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
