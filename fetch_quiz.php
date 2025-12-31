<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once 'includes/config.php';

if (!isset($_POST['lesson_id'])) {
    echo "<p class='text-muted'>No lesson selected.</p>";
    exit;
}

$lessonId = (int) $_POST['lesson_id'];
$userId = $_SESSION['user_id'] ?? null;

// --- Check if user already passed this quiz ---
$alreadyPassed = false;
$userScore = null;

if ($userId) {
    $stmt = $pdo->prepare("SELECT score, passed FROM user_quiz_results WHERE user_id = ? AND lesson_id = ?");
    $stmt->execute([$userId, $lessonId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && (int) $result['passed'] === 1) {
        $alreadyPassed = true;
        $userScore = (int) $result['score'];
    }
}

$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE lesson_id = ?");
$stmt->execute([$lessonId]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$quizzes) {
    echo "<p class='text-muted'>No quiz available for this lesson.</p>";
    exit;
}

// --- If user already passed ---
if ($alreadyPassed) {
    echo "
    <div class='alert alert-success text-center mt-4'>
        ✅ You have already passed this quiz!<br>
        <strong>Score:</strong> {$userScore}%
    </div>";
    exit;
}
?>

<div class="card mt-4">
    <div class="card-header bg-light">
        <h5><i class='fas fa-question-circle text-primary me-2'></i> Lesson Quiz</h5>
    </div>
    <div class="card-body">
        <form id="quizForm">
            <?php foreach ($quizzes as $index => $q): ?>
                <div class="mb-4">
                    <strong>Q<?= $index + 1; ?>:</strong> <?= htmlspecialchars($q['question']); ?><br>
                    <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="radio" name="q<?= $q['id']; ?>" value="<?= $opt; ?>">
                            <label class="form-check-label"><?= htmlspecialchars($q['option_' . strtolower($opt)]); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <button type="button" class="btn btn-primary" id="submitQuiz">Submit Quiz</button>
        </form>
        <div id="quizResult" class="mt-3"></div>
    </div>
</div>