<?php
session_start();
require 'config.php'; // Load Supabase connection

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// ==========================================
// 📊 UNIQUE DAILY ACTIVE STUDENTS TRACKER (DAU)
// ==========================================
$daily_views = 0;
$username = $_SESSION['username'];

try {
    $today = date('Y-m-d');
    
    // 1. Try to log this student for today. 
    $track_sql = "INSERT INTO daily_active_users (username, visit_date) VALUES (?, ?) 
                  ON CONFLICT (username, visit_date) DO NOTHING";
    $pdo->prepare($track_sql)->execute([$username, $today]);

    // 2. Count how many UNIQUE students are in the database for today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_active_users WHERE visit_date = ?");
    $stmt->execute([$today]);
    
    $fetched_views = $stmt->fetchColumn();
    if ($fetched_views) {
        $daily_views = $fetched_views;
    }
    
} catch (PDOException $e) {
    // Silently handle DB errors for tracking
}

// ==========================================
// 📚 FETCH DASHBOARD STATISTICS
// ==========================================
try {
    // 1. Fetch Quiz History using PDO
    $history_stmt = $pdo->prepare("SELECT chapter, score, total_questions, time_taken_seconds, quiz_date 
                                   FROM quiz_history 
                                   WHERE username = :username 
                                   ORDER BY quiz_date DESC");
    $history_stmt->execute(['username' => $username]);
    $quiz_history = $history_stmt->fetchAll();

    // 2. Calculate Total Quizzes Taken
    $total_quizzes = count($quiz_history);
    
    // Slice the array to only keep the top 10 most recent for the table!
    $recent_history = array_slice($quiz_history, 0, 10);

    // 3. Exam Countdown Logic
    $exam_date = strtotime("2026-05-04");
    $current_date = time();
    $days_left = floor(($exam_date - $current_date) / (60 * 60 * 24));
    if ($days_left < 0) $days_left = 0; // Prevent negative days

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathematical Universe - Dashboard</title>
    
    <!-- KaTeX CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css" crossorigin="anonymous">
    <!-- Interior Dashboard CSS -->
    <link rel="stylesheet" href="/assets/css/interior.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <button class="menu-btn" onclick="toggleSidebar()">☰ Menu</button>

    <div class="page-header">
        <div class="page-title">
            <h1>My Dashboard</h1>
            <p>Welcome back, <strong class="text-cyan"><?php echo htmlspecialchars($username); ?></strong>. Your mathematical universe awaits.</p>
        </div>
        <div class="dau-banner glass-panel" style="border-radius: 12px; height: fit-content;">
            🔥 <strong><?php echo $daily_views; ?></strong> students studying today
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card cyan glass-panel">
            <div class="stat-icon">🎓</div>
            <div class="stat-title">Final Exam Countdown</div>
            <div class="stat-value cyan"><?php echo $days_left; ?></div>
            <div class="stat-desc">Days Left</div>
        </div>
        <div class="stat-card purple glass-panel">
            <div class="stat-icon">🚀</div>
            <div class="stat-title">Quizzes Completed</div>
            <div class="stat-value purple"><?php echo $total_quizzes; ?></div>
            <div class="stat-desc">Total attempts</div>
        </div>
    </div>

    <div class="table-container glass-panel">
        <div class="table-header">
            <h3>Recent Activity Log</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Chapter</th>
                    <th>Result</th>
                    <th>Accuracy</th>
                    <th>Time Taken</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_quizzes > 0): ?>
                    <?php foreach ($recent_history as $row): ?>
                        <?php 
                        $percentage = ($row['total_questions'] > 0) ? round(($row['score'] / $row['total_questions']) * 100) : 0; 
                        
                        $seconds = $row['time_taken_seconds'];
                        $mins = floor($seconds / 60);
                        $secs = $seconds % 60;
                        $formatted_time = sprintf("%02d:%02d", $mins, $secs); 
                        
                        // Badge logic
                        $badge_class = 'badge-blue';
                        if ($percentage >= 80) $badge_class = 'badge-green';
                        elseif ($percentage < 50) $badge_class = 'badge-orange';
                        ?>
                        <tr>
                            <td class="text-slate"><?php echo date('d M Y, h:i A', strtotime($row['quiz_date'])); ?></td>
                            <td style="font-weight: 500;">Chapter <?php echo htmlspecialchars($row['chapter']); ?></td>
                            <td><?php echo htmlspecialchars($row['score']); ?> / <?php echo htmlspecialchars($row['total_questions']); ?></td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $percentage; ?>%</span></td>
                            <td class="text-slate" style="font-family: SFMono-Regular, Consolas, Monaco, monospace; letter-spacing: -0.5px;">⏱️ <?php echo $formatted_time; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8; font-style: italic;">No quizzes taken yet! Click "Take a Quiz" in the menu to enter the universe.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- KaTeX JS (Deferred) -->
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js" crossorigin="anonymous"></script>

</body>
</html>
