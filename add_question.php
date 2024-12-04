<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Question</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center mb-4">Add New Question</h1>
        
        <form action="insert_question.php" method="POST">
            <div class="mb-3">
                <label for="question" class="form-label">Question</label>
                <input type="text" name="question" id="question" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="ans1" class="form-label">Answer 1</label>
                <textarea name="ans1" id="ans1" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="ans2" class="form-label">Answer 2</label>
                <textarea name="ans2" id="ans2" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="ans3" class="form-label">Answer 3</label>
                <textarea name="ans3" id="ans3" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="ans4" class="form-label">Answer 4</label>
                <textarea name="ans4" id="ans4" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="correct_ans" class="form-label">Correct Answer (1-4)</label>
                <input type="number" name="correct_ans" id="correct_ans" class="form-control" min="1" max="4" required>
            </div>
            <div class="text-center">
                <button href="questions.php "type="submit" class="btn btn-success">Add Question</button>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
