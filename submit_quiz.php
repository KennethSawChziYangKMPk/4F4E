<?php
session_start();
require 'config.php'; 

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// =========================================================================
// STEP 1: HANDLE THE QUIZ SUBMISSION (POST REQUEST)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- THE BULLETPROOF DOUBLE-SUBMISSION FIX ---
    if (!isset($_SESSION['active_quiz'])) {
        header("Location: submit_quiz.php");
        exit();
    }

    // 🚨 CRITICAL FIX: Shred the ticket IMMEDIATELY! 
    // By doing this before the database runs, even if the user cancels the 
    // page load or double-clicks, they cannot submit a second time.
    unset($_SESSION['active_quiz']);
    unset($_SESSION['active_quiz_chapter']);
    unset($_SESSION['quiz_start_time']);

    $username = $_SESSION['username'];
    $chapter = $_POST['chapter'] ?? 0;
    $start_time = $_POST['start_time'] ?? time();
    $time_taken_seconds = time() - $start_time;

    $score = 0;
    $total_questions = 0;
    $results_breakdown = []; 

    try {
        $sql = "SELECT question_text, option_a, option_b, option_c, option_d, correct_option, method FROM question WHERE question_id = :q_id";
        $stmt = $pdo->prepare($sql);

        $update_correct_stat = $pdo->prepare("UPDATE question SET total_attempts = total_attempts + 1, correct_attempts = correct_attempts + 1 WHERE question_id = :q_id");
        $update_wrong_stat = $pdo->prepare("UPDATE question SET total_attempts = total_attempts + 1, wrong_attempts = wrong_attempts + 1 WHERE question_id = :q_id");

        foreach ($_POST as $key => $user_answer) {
            if (strpos($key, 'q') === 0) {
                $total_questions++;
                $q_id = substr($key, 1); 

                $stmt->execute(['q_id' => $q_id]);
                $row = $stmt->fetch(); 
                
                if ($row) {
                    $correct_choice = trim($row['correct_option']);
                    $user_choice = trim($user_answer);
                    $is_correct = ($user_choice === $correct_choice);
                    
                    if ($is_correct) {
                        $score++;
                        $update_correct_stat->execute(['q_id' => $q_id]);
                    } else {
                        $update_wrong_stat->execute(['q_id' => $q_id]);
                    }
                    
                    $option_map = [
                        'A' => $row['option_a'],
                        'B' => $row['option_b'],
                        'C' => $row['option_c'],
                        'D' => $row['option_d']
                    ];

                    $results_breakdown[] = [
                        'text' => $row['question_text'],
                        'user_ans' => $user_choice,
                        'user_text' => $option_map[$user_choice],
                        'correct_ans' => $correct_choice,
                        'correct_text' => $option_map[$correct_choice],
                        'status' => $is_correct,
                        'method' => !empty($row['method']) ? $row['method'] : 'No method provided for this question yet.'
                    ];
                }
            }
        }

        // Insert into database
        $insert_sql = "INSERT INTO quiz_history (username, chapter, score, total_questions, time_taken_seconds) 
                       VALUES (:username, :chapter, :score, :total_questions, :time_taken_seconds)";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([
            'username' => $username,
            'chapter' => $chapter,
            'score' => $score,
            'total_questions' => $total_questions,
            'time_taken_seconds' => $time_taken_seconds
        ]);

        $percentage = ($total_questions > 0) ? round(($score / $total_questions) * 100) : 0;

        $delete_progress = $pdo->prepare("DELETE FROM quiz_progress WHERE username = ? AND chapter = ?");
        $delete_progress->execute([$username, $chapter]);
        
        // Save results for the display page
        $_SESSION['last_quiz_results'] = [
            'score' => $score,
            'total_questions' => $total_questions,
            'percentage' => $percentage,
            'time_taken_seconds' => $time_taken_seconds,
            'results_breakdown' => $results_breakdown
        ];

        header("Location: submit_quiz.php");
        exit();

    } catch (PDOException $e) {
        die("Grading Error: " . $e->getMessage());
    }
}

// =========================================================================
// STEP 2: DISPLAY THE RESULTS (GET REQUEST)
// =========================================================================

if (!isset($_SESSION['last_quiz_results'])) {
    header("Location: chapters.php");
    exit();
}

$results_data = $_SESSION['last_quiz_results'];
$score = $results_data['score'];
$total_questions = $results_data['total_questions'];
$percentage = $results_data['percentage'];
$time_taken_seconds = $results_data['time_taken_seconds'];
$results_breakdown = $results_data['results_breakdown'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4 Flat 4 Everyone - Quiz Results Review</title>
    
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script>
      window.MathJax = { tex: { inlineMath: [['\\(', '\\)'], ['$$', '$$']] } };
    </script>
    
    <link rel="stylesheet" href="/Ken Project/assets/css/interior.css">
    <style>
        body { padding: 40px 20px; margin: 0; display: block; align-items: center;}
        .container { max-width: 900px; margin: 0 auto; }
        
        /* Score Card */
        .result-card { padding: 40px; text-align: center; margin-bottom: 30px; border-top: 5px solid #00e5ff;}
        .result-card h1 { margin-top: 0; color: #fff; font-family: 'Inter'; font-weight: 700; letter-spacing: -1px; font-size: 32px;}
        .score-big { font-size: 4rem; font-weight: 700; color: #fff; margin: 15px 0; font-family: 'Inter'; line-height: 1; text-shadow: 0 0 20px rgba(0,229,255,0.3);}
        .perc { font-size: 1.8rem; color: #10b981; font-weight: 700; text-shadow: 0 0 15px rgba(16,185,129,0.3);}
        
        .review-section { padding: 40px; }
        .slide { display: none; }
        .slide.active { display: block; animation: fadeIn 0.4s ease forwards; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .q-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 25px; }
        .q-title { font-size: 1.3rem; font-weight: 600; color: #e2e8f0; margin: 0; font-family: 'Inter';}
        .q-text { font-weight: 500; font-size: 1.2rem; margin-bottom: 25px; line-height: 1.6; color: #fff; overflow-x: auto; padding-bottom: 10px;}
        
        .ans-box { padding: 20px; border-radius: 10px; margin-bottom: 15px; font-size: 1.05rem; line-height: 1.5; color: #e2e8f0; border: 1px solid transparent; overflow-x: auto;}
        .your-ans { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); color: #fca5a5; }
        .correct-ans { background: rgba(16,185,129,0.1); border-color: rgba(16,185,129,0.3); color: #6ee7b7; box-shadow: 0 0 15px rgba(16,185,129,0.05); }
        
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; white-space: nowrap; letter-spacing: 1px;}
        .badge-correct { background: rgba(16,185,129,0.2); color: #10b981; border: 1px solid rgba(16,185,129,0.4); }
        .badge-wrong { background: rgba(239,68,68,0.2); color: #ef4444; border: 1px solid rgba(239,68,68,0.4); }

        .method-box { background: rgba(0,229,255,0.05); border-left: 4px solid #00e5ff; padding: 25px; border-radius: 8px; margin-top: 30px; overflow-x: auto; color: #cbd5e1;}
        .method-box h4 { margin: 0 0 15px 0; color: #00e5ff; display: flex; align-items: center; gap: 8px; font-size: 18px; }

        .nav-controls { display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.1); gap: 20px;}
        .nav-btn { background: rgba(255,255,255,0.05); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.1); padding: 15px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.2s; flex: 1; text-align: center; text-transform: uppercase; letter-spacing: 1px;}
        .nav-btn:hover:not(:disabled) { background: rgba(0,229,255,0.1); color: #00e5ff; border-color: rgba(0,229,255,0.3); box-shadow: 0 0 15px rgba(0,229,255,0.2); }
        .nav-btn:disabled { background: transparent; color: #475569; border-color: rgba(255,255,255,0.05); cursor: not-allowed; }
        
        .btn-home { display: block; width: 100%; max-width: 300px; margin: 40px auto 0; text-align: center; background: rgba(0,229,255,0.1); color: #00e5ff; border: 1px solid rgba(0,229,255,0.3); text-decoration: none; padding: 16px; border-radius: 8px; font-weight: 600; font-size: 16px; transition: all 0.2s; box-shadow: 0 4px 15px rgba(0,229,255,0.05); text-transform: uppercase; letter-spacing: 1.5px;}
        .btn-home:hover { background: #00e5ff; color: #0b0f19; box-shadow: 0 0 20px rgba(0,229,255,0.4); }

        @media (max-width: 600px) {
            body { padding: 20px; }
            .score-big { font-size: 3rem; }
            .result-card, .review-section { padding: 25px; }
            .q-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .nav-controls { flex-direction: column; gap: 15px; }
            .nav-btn { width: 100%; flex: auto; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="result-card glass-panel">
        <h1>Quiz Results Recap</h1>
        <div class="score-big"><?php echo htmlspecialchars($score); ?> <span style="font-size: 2rem; color: #64748b;">/ <?php echo htmlspecialchars($total_questions); ?></span></div>
        <div class="perc"><?php echo htmlspecialchars($percentage); ?>% Accuracy</div>
        <p style="color: #94a3b8; margin-bottom: 0; margin-top: 15px; font-size: 16px;">⏱️ Time Taken: <?php echo floor($time_taken_seconds / 60); ?>m <?php echo ($time_taken_seconds % 60); ?>s</p>
    </div>

    <div class="review-section glass-panel">
        <?php foreach ($results_breakdown as $index => $res): ?>
            <div class="slide" id="slide-<?php echo $index; ?>">
                
                <div class="q-header">
                    <h3 class="q-title">Question <?php echo $index + 1; ?> of <?php echo $total_questions; ?></h3>
                    <span class="status-badge <?php echo $res['status'] ? 'badge-correct' : 'badge-wrong'; ?>">
                        <?php echo $res['status'] ? '✓ Correct' : '✗ Wrong'; ?>
                    </span>
                </div>
                
                <div class="q-text"><?php echo $res['text']; ?></div>
                
                <?php if (!$res['status']): ?>
                    <div class="ans-box your-ans">
                        <strong>Your Answer:</strong> (<?php echo htmlspecialchars($res['user_ans']); ?>) <?php echo $res['user_text']; ?>
                    </div>
                <?php endif; ?>

                <div class="ans-box correct-ans">
                    <strong>Correct Answer:</strong> (<?php echo htmlspecialchars($res['correct_ans']); ?>) <?php echo $res['correct_text']; ?>
                </div>

                <div class="method-box">
                    <h4>💡 Method / Explanation</h4>
                    <div><?php echo $res['method']; ?></div>
                </div>

            </div>
        <?php endforeach; ?>

        <div class="nav-controls">
            <button class="nav-btn" id="prevBtn" onclick="changeSlide(-1)">&#8592; Previous</button>
            <button class="nav-btn" id="nextBtn" onclick="changeSlide(1)">Next &#8594;</button>
        </div>
    </div>

    <a href="chapters.php" class="btn-home">Back to Chapters</a>
</div>

<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    function showSlide(n) {
        slides.forEach(slide => slide.classList.remove('active'));
        slides[n].classList.add('active');
        prevBtn.disabled = (n === 0);
        nextBtn.disabled = (n === slides.length - 1);

        if (window.MathJax) {
            MathJax.typesetPromise();
        }
    }

    function changeSlide(step) {
        currentSlide += step;
        if (currentSlide < 0) currentSlide = 0;
        if (currentSlide >= slides.length) currentSlide = slides.length - 1;
        showSlide(currentSlide);
    }

    if (slides.length > 0) {
        showSlide(0);
    }
</script>

</body>
</html>
