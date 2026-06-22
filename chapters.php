<?php
session_start();
require 'config.php'; 

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$subject = isset($_GET['subject']) ? $_GET['subject'] : 'Mathematics';

// 1. Fetch High Scores (Completed Quizzes)
$high_scores = [];
try {
    $score_query = "SELECT chapter, MAX(score) as max_score FROM quiz_history WHERE username = :username GROUP BY chapter";
    $stmt = $pdo->prepare($score_query);
    $stmt->execute(['username' => $username]);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $high_scores[$row['chapter']] = $row['max_score'];
    }
} catch (PDOException $e) {
    $high_scores = []; 
}

// 2. Fetch In-Progress Quizzes (Paused Quizzes)
$in_progress = [];
try {
    // NOTE: Make sure 'quiz_progress' matches the actual name of your pause table!
    $prog_query = "SELECT chapter FROM quiz_progress WHERE username = :username";
    $stmt = $pdo->prepare($prog_query);
    $stmt->execute(['username' => $username]);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $in_progress[] = $row['chapter'];
    }
} catch (PDOException $e) {
    $in_progress = []; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4 Flat 4 Everyone - Chapters</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="/Ken Project/assets/css/interior.css">
    <style>
        /* Page Elements */
        .info-banner {
            background-color: rgba(0, 229, 255, 0.05); color: #e2e8f0; padding: 15px 20px; border-radius: 12px;
            margin-bottom: 35px; font-size: 16px; border-left: 4px solid #00e5ff;
            display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 15px rgba(0, 229, 255, 0.05);
        }

        .chapter-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .chapter-card { 
            text-decoration: none; color: #e2e8f0; 
            display: flex; flex-direction: column; justify-content: space-between;
            min-height: 130px; transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .chapter-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 229, 255, 0.15); }
        .chapter-title { 
            font-size: 22px; 
            font-weight: bold; 
            margin-bottom: 10px; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            padding-bottom: 10px; 
            font-family: 'Inter'; 
            color: #ffffff; 
            text-align: center; 
            width: 100%;        
        }
        .card-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
        .score-badge { background: rgba(255, 255, 255, 0.05); color: #00e5ff; padding: 6px 12px; border-radius: 15px; font-size: 13px; font-weight: bold; border: 1px solid rgba(0,229,255,0.2) }
        .interactive-area { display: flex; align-items: center; gap: 12px; }
        .trophy-icon { font-size: 22px; text-decoration: none; transition: 0.2s; filter: drop-shadow(0 0 5px rgba(255,215,0,0.5)); }
        .trophy-icon:hover { transform: scale(1.2); }

        @media (max-width: 900px) {
            .info-banner { flex-direction: column; text-align: center; }
            .chapter-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <button class="menu-btn" onclick="toggleSidebar()">☰ Menu</button>

    <div class="page-header">
        <div class="page-title">
            <h1><?php echo htmlspecialchars($subject); ?> Chapters</h1>
            <p>Select a chapter below to master the material or take a quiz.</p>
        </div>
    </div>

    <div class="info-banner">
        <span style="font-size: 24px;">💡</span>
        <span><strong>Pro Tip:</strong> Every attempt generates a unique set of <strong>10 random questions</strong> drawn from a master bank of <strong>50+ curated questions</strong>. Keep retaking quizzes to discover them all!</span>
    </div>

    <div class="chapter-grid">
        <?php 
        $chapters = [1, 2, 3, 5, 8, 9];
        foreach ($chapters as $num): 
            $has_score = isset($high_scores[$num]);
            $is_paused = in_array($num, $in_progress);
            
            // 3. Determine the logic based on their progress state
            if ($is_paused) {
                // If they have a saved session, prompt them to continue
                $score_text = $has_score ? "Best: " . $high_scores[$num] . "/10" : "In Progress...";
                $action_text = "Continue →";
                $action_color = "#f59e0b"; // Orange to indicate a paused state
                $fresh_param = "0"; // DO NOT wipe their saved quiz!
            } else {
                // Normal Start/Retake logic
                $score_text = $has_score ? "Best: " . $high_scores[$num] . "/10" : "Unattempted";
                $action_text = $has_score ? "Retake →" : "Start →";
                $action_color = $has_score ? "#3498db" : "#2ecc71"; 
                $fresh_param = "1"; // Safe to generate a brand new quiz
            }
        ?>
            <a href="quiz.php?subject=<?php echo urlencode($subject); ?>&chapter=<?php echo $num; ?>&fresh=<?php echo $fresh_param; ?>" class="chapter-card glass-panel">
                <div class="chapter-title">Chapter <?php echo $num; ?></div>
                <div class="card-bottom">
                    <span class="score-badge"><?php echo $score_text; ?></span>
                    <div class="interactive-area">
                        <object><a href="leaderboard.php?subject=<?php echo urlencode($subject); ?>&chapter=<?php echo $num; ?>" class="trophy-icon" title="View Leaderboard">🏆</a></object>
                        <span style="color: <?php echo $action_color; ?>; font-weight: bold; font-size: 16px;"><?php echo $action_text; ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
