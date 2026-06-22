<?php
session_start();

// SECURITY: Kick out anyone who isn't logged in OR isn't an admin
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

require 'config.php'; // Using your standard Supabase connection config

// Fetch and group all questions by chapter
$questions_by_chapter = [];
try {
    $sql = "SELECT * FROM question ORDER BY chapter ASC, question_id ASC";
    $stmt = $pdo->query($sql);
    $all_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_questions as $q) {
        $chapter = $q['chapter'];
        $questions_by_chapter[$chapter][] = $q;
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathematical Universe - Item Analysis</title>
    
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script>
      window.MathJax = { tex: { inlineMath: [['\\(', '\\)'], ['$$', '$$']] } };
    </script>
    <link rel="stylesheet" href="/assets/css/interior.css">

    <style>
        .main-content { max-width: 1200px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;}
        .header h1 { margin: 0; color: #fff; font-family: 'Inter'; font-size: 32px; font-weight: 700; letter-spacing: -1px;}
        
        .card { padding: 30px; margin-bottom: 30px; border-top: 4px solid #00e5ff;}
        .card h3 { margin-top: 0; color: #fff; font-family: 'Inter'; font-size: 22px; margin-bottom: 20px;}

        /* Categorized Dropbar/Accordion Styles */
        .chapter-section { margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden; background: rgba(0,0,0,0.2); }
        .chapter-title { 
            background: rgba(255,255,255,0.02); 
            padding: 20px; 
            margin: 0; 
            color: #fff; 
            font-size: 16px; 
            font-weight: 600;
            font-family: 'Inter';
            cursor: pointer; 
            list-style: none;
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            transition: background 0.2s;
        }
        .chapter-title:hover { background: rgba(255,255,255,0.05); }
        .chapter-title::-webkit-details-marker { display: none; } 
        
        .chapter-title::after { content: '▼'; font-size: 12px; transition: transform 0.3s ease; color: #00e5ff; }
        details[open] .chapter-title::after { transform: rotate(180deg); }
        details[open] .chapter-title { border-bottom: 1px solid rgba(255,255,255,0.1); }

        /* Styling the Stats Table */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 0; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1;}
        th { background-color: rgba(255,255,255,0.05); color: #e2e8f0; font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 1px;}
        tr:hover td { background-color: rgba(255,255,255,0.02); }
        
        .question-col { width: 45%; font-size: 15px; color: #fff; }
        
        /* Stat Badges */
        .stat-badge { padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 14px; text-align: center; display: inline-block; min-width: 40px; }
        .bg-gray { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; }
        .bg-green { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #10b981; }
        .bg-red { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; }
        
        /* Percentage Colors based on difficulty */
        .pct-high { color: #10b981; font-weight: 700; font-size: 16px; text-shadow: 0 0 10px rgba(16,185,129,0.3); }
        .pct-med { color: #f59e0b; font-weight: 700; font-size: 16px; text-shadow: 0 0 10px rgba(245,158,11,0.3); }
        .pct-low { color: #ef4444; font-weight: 700; font-size: 16px; text-shadow: 0 0 10px rgba(239,68,68,0.3); }

        .sidebar-bottom { margin-top: auto; padding: 20px 0; border-top: 1px solid rgba(255,255,255,0.05); }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div style="padding: 0 30px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 20px;">
        <h2 style="color: #fff; font-size: 20px; margin: 0 0 5px; font-family: 'Inter'; font-weight: 700;">Admin Panel</h2>
        <p style="color: #94a3b8; margin: 0; font-size: 14px;"><strong><?php echo htmlspecialchars($username); ?></strong></p>
    </div>
    
    <div class="sidebar-nav">
        <a href="admin_dashboard.php">🛡️ Admin Home</a>
        <a href="admin_create_account.php">➕ Create Admin</a>
        <a href="import.php">📝 Manage Questions</a>
        <a href="admin_stats.php" class="active-link">📈 Item Analysis</a>
        <a href="admin_reviews.php">⭐ View Reviews</a>
        <a href="admin_manage_students.php">👥 Manage Students</a>
        <a href="admin_quiz_logs.php">📊 All Quiz Logs</a>
    </div>
    
    <div class="bottom-links sidebar-nav" style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; margin-top: auto;">
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="header">
        <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('active')">☰ Menu</button>
        <h1>📈 Item Analysis</h1>
    </div>

    <div class="card glass-panel" style="padding: 0; border: none; background: transparent;">
        
        <?php if (count($questions_by_chapter) > 0): ?>
            <?php foreach ($questions_by_chapter as $chapter => $questions): ?>
            
                <details class="chapter-section">
                    <summary class="chapter-title">Chapter <?php echo htmlspecialchars($chapter); ?> 
                        <span style="font-size: 12px; font-weight: normal; color: #7f8c8d;">(<?php echo count($questions); ?> questions)</span>
                    </summary>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Question Preview</th>
                                    <th style="text-align: center;">Total Attempts</th>
                                    <th style="text-align: center;">Correct</th>
                                    <th style="text-align: center;">Wrong</th>
                                    <th style="text-align: center;">Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $q): 
                                    
                                    // Safely calculate percentage to avoid dividing by zero
                                    $total = $q['total_attempts'] ?? 0;
                                    $correct = $q['correct_attempts'] ?? 0;
                                    $wrong = $q['wrong_attempts'] ?? 0;
                                    
                                    $percentage = ($total > 0) ? round(($correct / $total) * 100) : 0;
                                    
                                    // Determine color based on how many people got it right
                                    $pct_class = "pct-low"; // Default red (Hard)
                                    if ($percentage >= 75) $pct_class = "pct-high"; // Green (Easy)
                                    elseif ($percentage >= 40) $pct_class = "pct-med"; // Yellow (Medium)
                                    if ($total == 0) $pct_class = ""; // Gray if no one has taken it yet
                                ?>
                                    <tr>
                                        <td><strong><?php echo $q['question_id']; ?></strong></td>
                                        <td class="question-col">
                                            <?php echo mb_strimwidth($q['question_text'], 0, 100, "..."); ?>
                                        </td>
                                        <td style="text-align: center;"><span class="stat-badge bg-gray"><?php echo $total; ?></span></td>
                                        <td style="text-align: center;"><span class="stat-badge bg-green"><?php echo $correct; ?></span></td>
                                        <td style="text-align: center;"><span class="stat-badge bg-red"><?php echo $wrong; ?></span></td>
                                        <td style="text-align: center;">
                                            <span class="<?php echo $pct_class; ?>">
                                                <?php echo ($total > 0) ? $percentage . "%" : "N/A"; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
                
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; color: #888; padding: 20px; background: white; border-radius: 10px;">
                No questions found in the database.
            </div>
        <?php endif; ?>
        
    </div>
</div>

</body>
</html>
