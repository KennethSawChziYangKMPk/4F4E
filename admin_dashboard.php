<?php
session_start();
require 'config.php'; 

// SECURITY: Kick out anyone who isn't logged in OR isn't an admin
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
// Get Today's Daily Views
// Get Today's Unique Daily Active Users
try {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_active_users WHERE visit_date = ?");
    $stmt->execute([$today]);
    $daily_views = $stmt->fetchColumn();
    if (!$daily_views) $daily_views = 0;
} catch (PDOException $e) {
    $daily_views = 0;
}
// Get quick stats using PDO syntax (fetchColumn gets the count directly)
try {
    $total_users = $pdo->query("SELECT COUNT(*) FROM student WHERE role='student'")->fetchColumn();
} catch (PDOException $e) {
    $total_users = 0;
}

try {
    $total_reviews = $pdo->query("SELECT COUNT(*) FROM app_reviews")->fetchColumn();
} catch (PDOException $e) {
    $total_reviews = 0;
}

try {
    $total_quizzes = $pdo->query("SELECT COUNT(*) FROM quiz_history")->fetchColumn();
} catch (PDOException $e) {
    $total_quizzes = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathematical Universe - Admin Command Center</title>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        .main-content { max-width: 1200px; }
        .header h2 { color: #ffffff; font-family: 'Inter'; font-size: 36px; margin-bottom: 10px; margin-top: 0; font-weight: 700; letter-spacing: -1px;}
        .header p { color: #94a3b8; font-size: 18px; margin-top: 0; }
        
        .stats-container { display: flex; gap: 20px; margin-bottom: 40px; margin-top: 30px; flex-wrap: wrap; }
        .stat-card { padding: 30px; flex: 1; min-width: 200px; text-align: center; border-radius: 16px; border-top: 4px solid #00e5ff; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,229,255,0.1); }
        .stat-card h3 { margin-top: 0; color: #94a3b8; font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px; font-family: 'Inter'; font-weight: 600; }
        .stat-number { font-size: 3.5rem; color: #fff; font-weight: 700; font-family: 'Inter'; text-shadow: 0 0 20px rgba(255,255,255,0.2); margin-top: 15px;}
        
        /* Colorful accents for stat cards */
        .stat-card:nth-child(1) { border-top-color: #f59e0b; }
        .stat-card:nth-child(2) { border-top-color: #10b981; }
        .stat-card:nth-child(3) { border-top-color: #8b5cf6; }
        .stat-card:nth-child(4) { border-top-color: #ec4899; }
        
        .section-title { color: #fff; font-family: 'Inter'; font-size: 28px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 30px; margin-top: 50px; font-weight: 600; letter-spacing: -0.5px;}
        
        .admin-menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .admin-btn { padding: 40px 20px; text-align: center; text-decoration: none; color: #e2e8f0; font-size: 18px; font-weight: 600; transition: all 0.3s; display: flex; flex-direction: column; align-items: center; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; font-family: 'Inter'; }
        .admin-btn:hover { background: rgba(0,229,255,0.05); color: #00e5ff; border-color: rgba(0,229,255,0.3); transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,229,255,0.1); }
        .admin-icon { font-size: 50px; margin-bottom: 20px; filter: drop-shadow(0 0 10px rgba(255,255,255,0.2)); transition: all 0.3s;}
        .admin-btn:hover .admin-icon { filter: drop-shadow(0 0 15px rgba(0,229,255,0.6)); transform: scale(1.1); }

        /* Specific sidebar override since Admin has different links */
        .sidebar-bottom { margin-top: auto; padding: 20px 0; border-top: 1px solid rgba(255,255,255,0.05); }

        @media (max-width: 768px) {
            .stats-container { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div style="padding: 0 30px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 20px;">
        <h2 style="color: #fff; font-size: 20px; margin: 0 0 5px; font-family: 'Inter'; font-weight: 700;">Admin Panel</h2>
        <p style="color: #94a3b8; margin: 0; font-size: 14px;"><strong><?php echo htmlspecialchars($username); ?></strong></p>
    </div>
    
    <div class="sidebar-nav">
        <a href="admin_dashboard.php" class="active-link">🛡️ Admin Home</a>
        <a href="admin_create_account.php">➕ Create Admin</a>
        <a href="import.php">📝 Manage Questions</a>
        <a href="admin_stats.php">📈 Item Analysis</a>
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
        <h2>Welcome to the Command Center</h2>
        <p>Here is a bird's-eye view of how your app is performing.</p>
    </div>
    
    <div class="stats-container">
        <div class="stat-card glass-panel">
            <h3>Registered Students</h3>
            <div class="stat-number"><?php echo $total_users; ?></div>
        </div>
        <div class="stat-card glass-panel">
            <h3>Quizzes Completed</h3>
            <div class="stat-number"><?php echo $total_quizzes; ?></div>
        </div>
        <div class="stat-card glass-panel">
            <h3>Feedback Reviews</h3>
            <div class="stat-number"><?php echo $total_reviews; ?></div>
        </div>
        <div class="stat-card glass-panel">
            <h3>Today's Views</h3>
            <div class="stat-number"><?php echo $daily_views; ?></div>
        </div>
    </div>
    <h3 class="section-title">Admin Actions</h3>
    <div class="admin-menu-grid">
        
        <a href="import.php" class="admin-btn glass-panel">
            <div class="admin-icon">📝</div>
            Manage Questions
        </a>

        <a href="admin_stats.php" class="admin-btn glass-panel">
            <div class="admin-icon">📈</div>
            Item Analysis
        </a>

        <a href="admin_reviews.php" class="admin-btn glass-panel">
            <div class="admin-icon">⭐</div>
            Read Feedback
        </a>
        
        <a href="admin_manage_students.php" class="admin-btn glass-panel">
            <div class="admin-icon">👥</div>
            Manage Users
        </a>
        
    </div>
</div>

</body>
</html>
