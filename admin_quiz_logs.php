<?php
session_start();

// SECURITY: Kick out anyone who isn't an admin
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
require 'config.php'; // Using your centralized DB connection

$student_search = isset($_GET['student']) ? trim($_GET['student']) : '';
$chapter_search = isset($_GET['chapter']) ? trim($_GET['chapter']) : '';

$student_found = false;
$available_chapters = [];
$quiz_logs = [];
$error_message = "";

// Analytics variables
$avg_score_pct = 0;
$avg_time = 0;
$last_attempt = "N/A";
$admin_insight = "";
$insight_class = "alert-info";

try {
    // STEP 1: If a student is searched, verify they exist
    if ($student_search !== '') {
        $stmt = $pdo->prepare("SELECT username FROM student WHERE username = ? AND role = 'student'");
        $stmt->execute([$student_search]);
        
        if ($stmt->fetch()) {
            $student_found = true;
            
            // Fetch ONLY the chapters this specific student has attempted
            $chap_stmt = $pdo->prepare("SELECT DISTINCT chapter FROM quiz_history WHERE username = ? ORDER BY chapter ASC");
            $chap_stmt->execute([$student_search]);
            $available_chapters = $chap_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // STEP 2 & 3: If a chapter is selected, fetch the specific logs
            if ($chapter_search !== '' && in_array($chapter_search, $available_chapters)) {
                $log_stmt = $pdo->prepare("SELECT * FROM quiz_history WHERE username = ? AND chapter = ? ORDER BY id DESC");
                $log_stmt->execute([$student_search, $chapter_search]);
                $quiz_logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // --- CALCULATE ANALYTICS ---
                if (count($quiz_logs) > 0) {
                    $total_pct = 0;
                    $total_time = 0;
                    $count = count($quiz_logs);
                    
                    foreach ($quiz_logs as $log) {
                        $total_pct += ($log['score'] / $log['total_questions']) * 100;
                        $total_time += $log['time_taken_seconds'];
                    }
                    
                    $avg_score_pct = round($total_pct / $count);
                    $avg_time = round($total_time / $count);
                    
                    // Assuming you have a 'created_at' column. If not, it defaults to Not Recorded.
                    // New code looking for 'submitted_at'
                    $last_attempt = isset($quiz_logs[0]['quiz_date']) 
                                    ? date("d M Y, h:i A", strtotime($quiz_logs[0]['quiz_date'])) 
                                    : "Not Recorded";
                                    
                    // --- GENERATE ADMIN INSIGHTS ---
                    if ($avg_score_pct >= 80) {
                        $admin_insight = "🌟 <strong>Excellent:</strong> Averaging {$avg_score_pct}%. This student has a strong grasp of Chapter {$chapter_search}.";
                        $insight_class = "alert-success";
                    } elseif ($avg_score_pct < 40) {
                        $admin_insight = "⚠️ <strong>Needs Attention:</strong> Averaging {$avg_score_pct}%. This student is struggling heavily with this chapter.";
                        $insight_class = "alert-error";
                    } else {
                        $admin_insight = "✅ <strong>On Track:</strong> Averaging {$avg_score_pct}%. Performing at an average level.";
                        $insight_class = "alert-info";
                    }
                    
                    // Flag if they are rushing (e.g., less than 30 seconds average)
                    if ($avg_time < 30) {
                        $admin_insight .= " <em>Note: They are completing quizzes very quickly ({$avg_time}s avg). They might be guessing or rushing.</em>";
                    }
                }
            }
        } else {
            $error_message = "Student '$student_search' not found in the database.";
        }
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
    <title>Mathematical Universe - Student Quiz Logs</title>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        .main-content { max-width: 1200px; }
        .header { display: flex; flex-direction: column; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;}
        .header h1 { margin: 0; color: #fff; font-family: 'Inter'; font-size: 32px; font-weight: 700; letter-spacing: -1px;}
        .header p { color: #94a3b8; font-family: 'Inter'; font-size: 16px; margin-top: 5px;}

        .card { padding: 30px; margin-bottom: 30px; border-top: 4px solid #00e5ff;}
        .card h3 { margin-top: 0; color: #fff; font-family: 'Inter'; font-size: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px;}

        /* Alerts */
        .alert-error { padding: 15px 20px; background: rgba(239,68,68,0.1); color: #ef4444; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-weight: 600;}
        .alert-info { padding: 15px 20px; background: rgba(0,229,255,0.05); color: #00e5ff; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #00e5ff; font-weight: 600;}
        .alert-success { padding: 15px 20px; background: rgba(16,185,129,0.1); color: #10b981; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #10b981; font-weight: 600;}

        /* Form Controls */
        .form-group { display: flex; gap: 15px; align-items: center; }
        .form-control { flex: 1; padding: 12px 20px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; font-size: 15px; color: #fff; font-family: 'Inter'; max-width: 400px; outline: none; transition: border 0.3s; }
        .form-control option { background: #0b0f19; color: #fff; }
        .form-control:focus { border-color: #00e5ff; box-shadow: 0 0 15px rgba(0,229,255,0.1);}
        
        .btn-primary { background: rgba(0,229,255,0.1); color: #00e5ff; border: 1px solid rgba(0,229,255,0.3); padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 15px; font-family: 'Inter'; transition: 0.2s; white-space: nowrap;}
        .btn-primary:hover { background: #00e5ff; color: #0b0f19; box-shadow: 0 0 15px rgba(0,229,255,0.4); }
        .btn-secondary { background: rgba(255,255,255,0.05); color: #e2e8f0; text-decoration: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; font-size: 15px; border: 1px solid rgba(255,255,255,0.1); display: inline-flex; align-items: center; transition: 0.2s; }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); color: #fff; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-box { padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); text-align: center; }
        .stat-title { font-size: 13px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 1px; font-family: 'Inter';}
        .stat-value { font-size: 28px; color: #fff; font-weight: 700; margin-top: 10px; font-family: 'Inter';}

        /* Table Styles */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 0; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1;}
        th { background-color: rgba(255,255,255,0.05); color: #e2e8f0; font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; }
        tr:hover td { background-color: rgba(255,255,255,0.02); }
        .highlight { font-weight: 700; color: #00e5ff; }

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
        <a href="admin_stats.php">📈 Item Analysis</a>
        <a href="admin_reviews.php">⭐ View Reviews</a>
        <a href="admin_manage_students.php">👥 Manage Students</a>
        <a href="admin_quiz_logs.php" class="active-link">📊 All Quiz Logs</a>
    </div>
    
    <div class="bottom-links sidebar-nav" style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; margin-top: auto;">
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="header" style="flex-direction: row; justify-content: flex-start; gap: 20px;">
        <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('active')">☰ Menu</button>
        <div>
            <h1>📊 Student Quiz Logs</h1>
            <p>Query specific students and drill down into their chapter performance.</p>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert-error">❌ <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="card glass-panel">
        <h3>Step 1: Query a Student</h3>
        <form method="GET" action="admin_quiz_logs.php" class="form-group">
            <input type="text" name="student" class="form-control" placeholder="Enter student username..." value="<?php echo htmlspecialchars($student_search); ?>" required>
            <button type="submit" class="btn-primary">🔍 Search</button>
            <?php if ($student_search !== ''): ?>
                <a href="admin_quiz_logs.php" class="btn-secondary">✖ Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($student_found): ?>
        <div class="card glass-panel">
            <h3>Step 2: Select Chapter for <span style="color: #e74c3c;">@<?php echo htmlspecialchars($student_search); ?></span></h3>
            
            <?php if (count($available_chapters) > 0): ?>
                <form method="GET" action="admin_quiz_logs.php" class="form-group">
                    <input type="hidden" name="student" value="<?php echo htmlspecialchars($student_search); ?>">
                    <select name="chapter" class="form-control" required>
                        <option value="" disabled <?php echo $chapter_search === '' ? 'selected' : ''; ?>>-- Choose a Chapter --</option>
                        <?php foreach ($available_chapters as $chap): ?>
                            <option value="<?php echo htmlspecialchars($chap); ?>" <?php echo $chapter_search == $chap ? 'selected' : ''; ?>>
                                Chapter <?php echo htmlspecialchars($chap); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary">📄 View Logs</button>
                </form>
            <?php else: ?>
                <div class="alert-info">ℹ️ This student has not attempted any quizzes yet.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($student_found && $chapter_search !== '' && count($quiz_logs) > 0): ?>
        
        <div class="<?php echo $insight_class; ?>">
            <?php echo $admin_insight; ?>
        </div>

        <div class="card glass-panel">
            <h3>Performance Summary: Chapter <?php echo htmlspecialchars($chapter_search); ?></h3>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-title">Average Score</div>
                    <div class="stat-value"><?php echo $avg_score_pct; ?>%</div>
                </div>
                <div class="stat-box">
                    <div class="stat-title">Average Time Taken</div>
                    <div class="stat-value"><?php echo $avg_time; ?>s</div>
                </div>
                <div class="stat-box">
                    <div class="stat-title">Last Attempt</div>
                    <div class="stat-value" style="font-size: 18px; margin-top: 10px;"><?php echo $last_attempt; ?></div>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Attempt No.</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Time Taken</th>
                            <th>Log ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reverse loop counter to show attempt numbers correctly if ordered DESC
                        $attempt_num = count($quiz_logs); 
                        foreach($quiz_logs as $row): 
                            $pct = round(($row['score'] / $row['total_questions']) * 100);
                        ?>
                            <tr>
                                <td>Attempt <?php echo $attempt_num--; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['score']) . " / " . htmlspecialchars($row['total_questions']); ?></strong></td>
                                <td><?php echo $pct; ?>%</td>
                                <td><?php echo htmlspecialchars($row['time_taken_seconds']); ?>s</td>
                                <td style="font-size: 14px; color: #888;">#<?php echo $row['id']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($student_found && $chapter_search !== '' && count($quiz_logs) === 0): ?>
        <div class="alert-error">No logs found for this chapter.</div>
    <?php endif; ?>

</div>

</body>
</html>
