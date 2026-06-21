<?php
session_start();
require 'config.php'; 

// SECURITY: Kick out anyone who isn't logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// 1. Get all chapters this student has attempted for the Dropdown Menu
try {
    $chap_stmt = $pdo->prepare("SELECT DISTINCT chapter FROM quiz_history WHERE username = ? ORDER BY chapter ASC");
    $chap_stmt->execute([$username]);
    $available_chapters = $chap_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

// 2. Figure out which chapter we are currently looking at
$selected_chapter = isset($_GET['chapter']) ? $_GET['chapter'] : (count($available_chapters) > 0 ? $available_chapters[0] : null);

// 3. Fetch analytics ONLY for the selected chapter (For the User)
$graph_labels = [];
$graph_data = [];
$total_time_taken = 0;
$total_questions_attempted = 0;
$best_score_pct = 0;
$total_pct_sum = 0;
$attempts_count = 0;
$global_avg_pct = 0;

if ($selected_chapter) {
    try {
        // --- Get Logged-in User's Data ---
        $stmt = $pdo->prepare("SELECT score, total_questions, time_taken_seconds FROM quiz_history WHERE username = ? AND chapter = ? ORDER BY id ASC");
        $stmt->execute([$username, $selected_chapter]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($logs as $index => $log) {
            $score = $log['score'];
            $total_q = $log['total_questions'] > 0 ? $log['total_questions'] : 10; 
            $time = $log['time_taken_seconds'];
            
            // Pacing Data
            $total_time_taken += $time;
            $total_questions_attempted += $total_q;
            
            // Graph & Stats Data
            $pct = round(($score / $total_q) * 100);
            $graph_labels[] = "Attempt " . ($index + 1);
            $graph_data[] = $pct;
            
            if ($pct > $best_score_pct) {
                $best_score_pct = $pct;
            }
            $total_pct_sum += $pct;
            $attempts_count++;
        }
        
        // Limit the Progress Graph to the last 5 attempts
        if (count($graph_labels) > 5) {
            $graph_labels = array_slice($graph_labels, -5);
            $graph_data = array_slice($graph_data, -5);
        }

        // --- NEW: Get Global Data (All Users) for the same Chapter ---
        $global_stmt = $pdo->prepare("SELECT score, total_questions FROM quiz_history WHERE chapter = ?");
        $global_stmt->execute([$selected_chapter]);
        $global_logs = $global_stmt->fetchAll(PDO::FETCH_ASSOC);

        $global_total_sum = 0;
        $global_attempts_count = 0;

        foreach ($global_logs as $g_log) {
            $g_score = $g_log['score'];
            $g_total_q = $g_log['total_questions'] > 0 ? $g_log['total_questions'] : 10;
            $global_total_sum += ($g_score / $g_total_q) * 100;
            $global_attempts_count++;
        }

        if ($global_attempts_count > 0) {
            $global_avg_pct = round($global_total_sum / $global_attempts_count);
        }

    } catch (PDOException $e) {
        die("Database Error: " . htmlspecialchars($e->getMessage()));
    }
}

// 4. Calculate Average Score
$avg_score_pct = $attempts_count > 0 ? round($total_pct_sum / $attempts_count) : 0;

// Calculate Peer Comparison
$comparison_diff = $avg_score_pct - $global_avg_pct;
if ($comparison_diff > 0) {
    $comp_text = "+" . $comparison_diff . "% vs Peers";
    $comp_color = "#27ae60"; // Green for better than average
} elseif ($comparison_diff < 0) {
    $comp_text = $comparison_diff . "% vs Peers";
    $comp_color = "#e74c3c"; // Red for worse than average
} else {
    $comp_text = "Exactly Average";
    $comp_color = "#f39c12"; // Yellow for exactly average
}

// 5. Calculate Pacing & Provide Smart Feedback
$avg_time_per_question = 0;
$time_message = "Not enough data yet. Take a quiz to see your pacing!";
$time_color = "#95a5a6";

if ($total_questions_attempted > 0) {
    $avg_time_per_question = round($total_time_taken / $total_questions_attempted);
    
    // Define our Thresholds
    $is_score_low = ($avg_score_pct < 50);
    $is_score_avg = ($avg_score_pct >= 50 && $avg_score_pct < 80);
    $is_score_high = ($avg_score_pct >= 80);
    
    $is_time_low = ($avg_time_per_question < 30);
    $is_time_high = ($avg_time_per_question > 300);
    $is_time_perfect = (!$is_time_low && !$is_time_high);

    if ($is_score_low && $is_time_low) {
        $time_message = "🏎️ Too Fast! Your score is low because you are rushing ($avg_time_per_question sec/q). Slow down and read the questions carefully!";
        $time_color = "#e74c3c"; 
    } elseif ($is_score_low && ($is_time_perfect || $is_time_high)) {
        $time_message = "📚 Needs Work. You are taking your time, but the score is still low. Please review the formulae and master this chapter ASAP!";
        $time_color = "#e74c3c"; 
    } elseif ($is_score_avg && $is_time_low) {
        $time_message = "⏳ Rushing! You got an average score, but you definitely could have performed much better if you used your time more wisely.";
        $time_color = "#f39c12"; 
    } elseif ($is_score_avg && ($is_time_perfect || $is_time_high)) {
        $time_message = "📈 Good Effort! You have a solid grasp of the basics, but don't stop learning. Keep practicing to reach that top tier.";
        $time_color = "#f39c12"; 
    } elseif ($is_score_high && $is_time_perfect) {
        $time_message = "🎯 Perfect Harmony! High score and excellent pacing. Great job, keep this momentum going into the final exam!";
        $time_color = "#27ae60"; 
    } elseif ($is_score_high && $is_time_high) {
        $time_message = "🐢 High Accuracy, but slow. Your score is great, but you need to train your speed to ensure you don't run out of time on the real exam.";
        $time_color = "#3498db"; 
    } else {
        $time_message = "⚡ Incredible! You are answering both quickly and accurately. You have completely mastered this chapter!";
        $time_color = "#27ae60"; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathematical Universe - My Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; }
        .header-text h2 { color: #ffffff; font-family: 'Inter'; font-size: 32px; margin: 0 0 5px 0; font-weight: 700; letter-spacing: -1px;}
        
        /* Dropdown Styling */
        .filter-form select { padding: 12px 20px; font-size: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background-color: rgba(15,23,42,0.6); color: #fff; font-weight: 600; cursor: pointer; outline: none; }
        .filter-form select:focus { border-color: #00e5ff; }
        .filter-form select option { background-color: #0b0f19; color: #fff; }
        
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        
        .zone-card { margin-bottom: 30px; }
        .zone-title { margin-top: 0; font-size: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; margin-bottom: 20px; color: #ffffff; font-weight: 600;}
        
        /* Stats Boxes */
        .stat-box { text-align: center; padding: 25px; border-radius: 12px; background: rgba(0,0,0,0.2) }
        .stat-box h4 { margin: 0; color: #94a3b8; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
        .stat-box .number { font-size: 42px; font-weight: bold; color: #fff; margin-top: 10px; font-family: 'Inter'; line-height: 1.2; text-shadow: 0 0 15px rgba(255,255,255,0.1);}
        
        .time-box { background: rgba(15,23,42,0.6); border-left: 4px solid; padding: 20px; border-radius: 12px; font-size: 16px; font-weight: 500; margin-bottom: 30px; color: #e2e8f0; }
        .empty-state { color: #64748b; font-style: italic; font-size: 16px; text-align: center; padding: 40px; }

        @media (max-width: 900px) {
            .header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .filter-form select { width: 100%; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <button class="menu-btn" onclick="toggleSidebar()">☰ Menu</button>
    
    <div class="header">
        <div class="header-text">
            <h2>Performance Analytics</h2>
            <p>See how you stack up against other students in Chapter <?php echo htmlspecialchars($selected_chapter); ?>.</p>
        </div>
        
        <div class="filter-form">
            <?php if (count($available_chapters) > 0): ?>
                <form method="GET" action="">
                    <select name="chapter" onchange="this.form.submit()">
                        <?php foreach ($available_chapters as $chap): ?>
                            <option value="<?php echo htmlspecialchars($chap); ?>" <?php if ($chap == $selected_chapter) echo 'selected'; ?>>
                                📁 Chapter <?php echo htmlspecialchars($chap); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php else: ?>
                <span style="color: #95a5a6; font-style: italic;">No quiz data available yet.</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($selected_chapter): ?>
        
        <div class="time-box glass-panel" style="border-color: <?php echo $time_color; ?>;">
            <strong style="color: <?php echo $time_color; ?>;">💬 Comments:</strong> <?php echo $time_message; ?>
        </div>

        <div class="zone-card glass-panel">
            <h3 class="zone-title">📈 Progress Over Time (Last 5 Quizzes)</h3>
            <?php if (empty($graph_data)): ?>
                <p class="empty-state">No data to display for this chapter.</p>
            <?php else: ?>
                <canvas id="progressChart" height="80"></canvas>
            <?php endif; ?>
        </div>

        <div class="grid-container">
            <div class="stat-box" style="border-top: 4px solid #3498db;">
                <h4>Your Average Score</h4>
                <div class="number"><?php echo $avg_score_pct; ?>%</div>
            </div>
            <div class="stat-box" style="border-top: 4px solid #9b59b6;">
                <h4>National Average</h4>
                <div class="number"><?php echo $global_avg_pct; ?>%</div>
            </div>
            <div class="stat-box" style="border-top: 4px solid <?php echo $comp_color; ?>;">
                <h4>Your Standing</h4>
                <div class="number" style="color: <?php echo $comp_color; ?>; font-size: 28px; line-height: 42px;">
                    <?php echo $comp_text; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="zone-card glass-panel">
            <p class="empty-state">It looks like you haven't taken any quizzes yet! Head over to the Dashboard to get started.</p>
        </div>
    <?php endif; ?>

</div>

<script>
    <?php if (!empty($graph_data)): ?>
        const ctx = document.getElementById('progressChart').getContext('2d');
        const progressChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($graph_labels); ?>,
                datasets: [{
                    label: 'Your Score (%)',
                    data: <?php echo json_encode($graph_data); ?>,
                    borderColor: '#00e5ff', 
                    backgroundColor: 'rgba(0, 229, 255, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#00e5ff',
                    pointBorderColor: '#0b0f19',
                    color: '#94a3b8',
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    fill: true,
                    tension: 0.4 
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: { display: true, text: 'Percentage (%)' }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    <?php endif; ?>
</script>

</body>
</html>
