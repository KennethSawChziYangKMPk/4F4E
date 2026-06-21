<?php
session_start();

// SECURITY: Kick out anyone who isn't logged in OR isn't an admin
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
require 'config.php'; // Using your standard Supabase connection config

$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$reviews = [];
$total_reviews = 0;
$avg_rating = 0;
$available_dates = [];

try {
    // 1. Get Overall Stats (All-time Total and Average)
    $stat_sql = "SELECT COUNT(*) as total, AVG(overall) as avg_overall FROM app_reviews";
    $stat_stmt = $pdo->query($stat_sql);
    $stats = $stat_stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_reviews = $stats['total'] ? $stats['total'] : 0;
    $avg_rating = $stats['avg_overall'] ? round($stats['avg_overall'], 1) : 0;

    // 2. Fetch distinct dates that actually have reviews for the dropdown
    // Note: DATE() works well in PostgreSQL to extract just the YYYY-MM-DD
    $date_sql = "SELECT DISTINCT DATE(submitted_at) as review_date FROM app_reviews ORDER BY review_date DESC";
    $date_stmt = $pdo->query($date_sql);
    $available_dates = $date_stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Fetch reviews ONLY if a date is selected
    if ($filter_date !== '') {
        $sql = "SELECT * FROM app_reviews WHERE DATE(submitted_at) = ? ORDER BY submitted_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$filter_date]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Mathematical Universe - User Reviews</title>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        .main-content { max-width: 1200px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;}
        .header h1 { margin: 0; color: #fff; font-family: 'Inter'; font-size: 32px; font-weight: 700; letter-spacing: -1px;}
        
        .card { padding: 30px; margin-bottom: 30px; border-top: 4px solid #00e5ff;}
        .card h3 { margin-top: 0; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; font-family: 'Inter'; font-size: 22px; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-box { padding: 25px; text-align: center; border-radius: 16px; border-top: 4px solid #f59e0b; }
        .stat-title { font-size: 14px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 1.5px; font-family: 'Inter';}
        .stat-value { font-size: 36px; color: #fff; font-weight: 700; margin-top: 10px; font-family: 'Inter'; text-shadow: 0 0 20px rgba(255,255,255,0.2);}
        .stat-stars { color: #f59e0b; font-size: 24px; margin-top: 8px; text-shadow: 0 0 15px rgba(245,158,11,0.5);}

        /* Form Controls */
        .form-group { display: flex; gap: 15px; align-items: center; margin-bottom: 25px;}
        .form-control { flex: 1; padding: 12px 20px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; font-size: 15px; color: #fff; font-family: 'Inter'; max-width: 300px; outline: none; transition: border 0.3s; }
        .form-control option { background: #0b0f19; color: #fff; }
        .form-control:focus { border-color: #00e5ff; box-shadow: 0 0 15px rgba(0,229,255,0.1);}
        
        .btn-primary { background: rgba(0,229,255,0.1); color: #00e5ff; border: 1px solid rgba(0,229,255,0.3); padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 15px; font-family: 'Inter'; transition: 0.2s; white-space: nowrap;}
        .btn-primary:hover { background: #00e5ff; color: #0b0f19; box-shadow: 0 0 15px rgba(0,229,255,0.4); }
        .btn-secondary { background: rgba(255,255,255,0.05); color: #e2e8f0; text-decoration: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; font-size: 15px; border: 1px solid rgba(255,255,255,0.1); display: inline-flex; align-items: center; transition: 0.2s; }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); color: #fff; }

        /* Styling the Stats Table */
        .alert-info { padding: 15px 20px; background: rgba(0,229,255,0.05); color: #00e5ff; border-radius: 8px; border-left: 4px solid #00e5ff; font-weight: 600; margin-bottom: 20px;}
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 0; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1;}
        th { background-color: rgba(255,255,255,0.05); color: #e2e8f0; font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; }
        tr:hover td { background-color: rgba(255,255,255,0.02); }
        
        .stars { color: #f59e0b; font-size: 18px; letter-spacing: 2px; text-shadow: 0 0 10px rgba(245,158,11,0.3);}
        .review-text { color: #fff; font-style: italic; font-size: 15px; line-height: 1.6; }
        .date-col { font-size: 14px; color: #94a3b8; white-space: nowrap; }

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
        <a href="admin_reviews.php" class="active-link">⭐ View Reviews</a> 
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
        <h1>⭐ User Feedback Dashboard</h1>
    </div>

    <div class="stats-grid">
        <div class="stat-box glass-panel">
            <div class="stat-title">Total Reviews Submitted</div>
            <div class="stat-value"><?php echo $total_reviews; ?></div>
        </div>
        <div class="stat-box glass-panel">
            <div class="stat-title">Average Overall Rating</div>
            <div class="stat-value"><?php echo $avg_rating; ?> <span style="font-size: 20px; color: #94a3b8;">/ 5.0</span></div>
            <div class="stat-stars"><?php echo str_repeat('★', round($avg_rating)); ?></div>
        </div>
    </div>

    <div class="card glass-panel">
        <h3>Filter by Date</h3>
        
        <?php if(count($available_dates) > 0): ?>
            <form method="GET" action="admin_reviews.php" class="form-group">
                <select name="filter_date" required class="form-control">
                    <option value="" disabled <?php echo $filter_date === '' ? 'selected' : ''; ?>>-- Select a Date --</option>
                    <?php foreach ($available_dates as $date): ?>
                        <option value="<?php echo htmlspecialchars($date); ?>" <?php echo $filter_date === $date ? 'selected' : ''; ?>>
                            <?php echo date('d M Y', strtotime($date)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-primary">🔍 Load Reviews</button>
                <?php if ($filter_date !== ''): ?>
                    <a href="admin_reviews.php" class="btn-secondary">✖ Clear</a>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="alert-info">No reviews have been submitted yet.</div>
        <?php endif; ?>

        <?php if ($filter_date !== ''): ?>
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 30px 0;">
            <h4 style="color: #fff; font-family: 'Inter'; font-size: 18px; margin-bottom: 20px;">Reviews for <?php echo date('d F Y', strtotime($filter_date)); ?></h4>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Overall</th>
                            <th>Difficulty</th>
                            <th>UI/UX</th>
                            <th>Written Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($reviews) > 0): ?>
                            <?php foreach($reviews as $row): ?>
                                <tr>
                                    <td class="date-col">
                                        <?php echo date('d M Y', strtotime($row['submitted_at'])); ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                    <td class="stars"><?php echo str_repeat('★', $row['overall']); ?></td>
                                    <td class="stars"><?php echo str_repeat('★', $row['difficulty']); ?></td>
                                    <td class="stars"><?php echo str_repeat('★', $row['ux']); ?></td>
                                    <td class="review-text">
                                        "<?php echo htmlspecialchars($row['review'] ? $row['review'] : 'No written review.'); ?>"
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #888; padding: 30px;">No reviews found for this specific date!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif(count($available_dates) > 0): ?>
            <div class="alert-info">👆 Please select a date from the dropdown above to view detailed reviews.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
