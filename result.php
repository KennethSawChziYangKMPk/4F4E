<?php
include 'config.php';
include 'footer.php'; 
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$score = 0;
$total = 0;
$results_html = "";
$chapter = $_POST['chapter'];

// 1. Get all correct answers for this chapter from the database
$sql = "SELECT id, question_text, correct_option FROM math_questions WHERE chapter = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $chapter);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $total++;
    $question_id = $row['id'];
    $correct_answer = $row['correct_option'];
    
    // Check what the user submitted (e.g., name="q1", name="q2")
    $user_answer = isset($_POST['q' . $question_id]) ? $_POST['q' . $question_id] : "None";

    if ($user_answer === $correct_answer) {
        $score++;
        $status = "<span style='color: #10b981; font-weight: 600; background: rgba(16,185,129,0.1); padding: 4px 10px; border-radius: 6px; display: inline-block; margin-top: 10px;'>✅ Correct</span>";
    } else {
        $status = "<span style='color: #ef4444; font-weight: 600; background: rgba(239,68,68,0.1); padding: 4px 10px; border-radius: 6px; display: inline-block; margin-top: 10px;'>❌ Wrong (Correct: " . htmlspecialchars($correct_answer) . ")</span>";
    }

    $results_html .= "<div class='result-item'>
                        <p style='margin-bottom: 15px; font-size: 18px;'><strong>Question $total:</strong> " . $row['question_text'] . "</p>
                        <p style='margin: 0;'>Your Answer: <strong style='color: #00e5ff;'>" . htmlspecialchars($user_answer) . "</strong></p>
                        <div>$status</div>
                      </div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <title>Mathematical Universe - Quiz Results</title>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        body { justify-content: center; align-items: flex-start; min-height: 100vh; margin: 0; padding: 40px; box-sizing: border-box; }
        .results-container { max-width: 800px; width: 100%; margin: 0 auto; padding: 50px; border-top: 5px solid #00e5ff; }
        
        .score-circle { 
            width: 120px; height: 120px; border-radius: 50%; 
            background: rgba(0,229,255,0.1); color: #00e5ff; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 32px; margin: 0 auto 30px; font-family: 'Inter'; font-weight: 700; 
            border: 2px solid #00e5ff; box-shadow: 0 0 20px rgba(0,229,255,0.2) inset, 0 0 30px rgba(0,229,255,0.2);
        }
        
        .results-container h2 { text-align:center; color: #fff; font-family: 'Inter'; font-size: 36px; margin-top: 0; margin-bottom: 10px; font-weight: 700; letter-spacing: -1px; }
        .results-container p.subtitle { text-align:center; color: #94a3b8; font-size: 18px; margin-bottom: 40px; }
        
        .result-item { 
            border-bottom: 1px solid rgba(255,255,255,0.1); padding: 30px 0;
            color: #cbd5e1; font-size: 16px; line-height: 1.6;
        }
        .result-item:last-child { border-bottom: none; }
        .result-item strong { color: #fff; font-weight: 600; font-family: 'Inter'; }
        
        .btn-wrapper { text-align:center; display: flex; justify-content: center; gap: 20px; margin-top: 40px; }
        .btn-action { 
            display: inline-block; padding: 15px 30px; 
            background: rgba(0,229,255,0.1); color: #00e5ff; 
            text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; font-family: 'Inter';
            border: 1px solid rgba(0,229,255,0.3); transition: all 0.2s; box-shadow: 0 4px 15px rgba(0,229,255,0.05);
        }
        .btn-action:hover { background: #00e5ff; color: #0b0f19; box-shadow: 0 0 20px rgba(0,229,255,0.4); }
        
        .btn-secondary { 
            background: rgba(255,255,255,0.05); color: #e2e8f0; 
            border: 1px solid rgba(255,255,255,0.1);
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); color: #fff; box-shadow: 0 0 15px rgba(255,255,255,0.1); }
        
        @media (max-width: 768px) {
            body { padding: 20px; }
            .results-container { padding: 30px 20px; }
            .btn-wrapper { flex-direction: column; gap: 15px; }
            .results-container h2 { font-size: 28px; }
        }
    </style>
</head>
<body>

<div class="results-container glass-panel">
    <div class="score-circle"><?php echo "$score/$total"; ?></div>
    <h2>Quiz Complete!</h2>
    <p class="subtitle">Chapter <?php echo htmlspecialchars($chapter); ?></p>
    
    <div class="summary">
        <?php echo $results_html; ?>
    </div>

    <div class="btn-wrapper">
        <a href="chapters.php" class="btn-action">Try Another Chapter</a>
        <a href="dashboard.php" class="btn-action btn-secondary">Return to Dashboard</a>
    </div>
</div>

</body>
</html>
