<?php
session_start();

// SECURITY: Admins only!
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
require 'config.php'; // Use your centralized database connection

$message = "";

try {
    // Handle the Deletion if the admin clicked the "Delete" button
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_username'])) {
        $del_user = $_POST['delete_username'];
        
        // 1. Delete the student from the database
        $stmt = $pdo->prepare("DELETE FROM student WHERE username = ? AND role = 'student'");
        
        if ($stmt->execute([$del_user])) {
            // 2. Clean up their messy room (Delete their quizzes and reviews too!)
            $clean_quizzes = $pdo->prepare("DELETE FROM quiz_history WHERE username = ?");
            $clean_quizzes->execute([$del_user]);
            
            $clean_reviews = $pdo->prepare("DELETE FROM app_reviews WHERE username = ?");
            $clean_reviews->execute([$del_user]);

            $message = "Student '$del_user' and all their records were successfully removed.";
        }
    }

    // Handle the Search Query
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    if ($search_query !== '') {
        // ILIKE is used in PostgreSQL for case-insensitive searching
        $sql = "SELECT * FROM student WHERE role = 'student' AND (username ILIKE :search OR nomatric ILIKE :search) ORDER BY username ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search_query%"]);
    } else {
        // Default: Fetch all students
        $sql = "SELECT * FROM student WHERE role = 'student' ORDER BY username ASC";
        $stmt = $pdo->query($sql);
    }
    
    // Store all students in an array
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathematical Universe - Manage Students</title>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        .main-content { max-width: 1200px; }
        .header { display: flex; flex-direction: column; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;}
        .header h1 { margin: 0; color: #fff; font-family: 'Inter'; font-size: 32px; font-weight: 700; letter-spacing: -1px;}
        .header p { color: #94a3b8; font-family: 'Inter'; font-size: 16px; margin-top: 5px;}
        
        .card { padding: 30px; margin-bottom: 30px; border-top: 4px solid #00e5ff;}
        .alert { padding: 15px 20px; background: rgba(16,185,129,0.1); color: #10b981; border-radius: 8px; border-left: 4px solid #10b981; font-weight: 600; margin-bottom: 20px;}

        /* Form Controls */
        .search-container { display: flex; gap: 15px; align-items: center; margin-bottom: 25px;}
        .search-input { flex: 1; padding: 12px 20px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; font-size: 15px; color: #fff; font-family: 'Inter'; max-width: 400px; outline: none; transition: border 0.3s; }
        .search-input:focus { border-color: #00e5ff; box-shadow: 0 0 15px rgba(0,229,255,0.1);}
        
        .btn-search { background: rgba(0,229,255,0.1); color: #00e5ff; border: 1px solid rgba(0,229,255,0.3); padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 15px; font-family: 'Inter'; transition: 0.2s; white-space: nowrap;}
        .btn-search:hover { background: #00e5ff; color: #0b0f19; box-shadow: 0 0 15px rgba(0,229,255,0.4); }
        .btn-clear { background: rgba(255,255,255,0.05); color: #e2e8f0; text-decoration: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; font-size: 15px; border: 1px solid rgba(255,255,255,0.1); display: inline-flex; align-items: center; transition: 0.2s; }
        .btn-clear:hover { background: rgba(255,255,255,0.1); color: #fff; }

        /* Styling the Stats Table */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 0; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1;}
        th { background-color: rgba(255,255,255,0.05); color: #e2e8f0; font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; }
        tr:hover td { background-color: rgba(255,255,255,0.02); }
        
        .btn-delete { background: rgba(239,68,68,0.1); color: #ef4444; padding: 8px 15px; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600; border: 1px solid rgba(239,68,68,0.3); transition: all 0.2s; cursor: pointer;}
        .btn-delete:hover { background: #ef4444; color: #fff; box-shadow: 0 0 10px rgba(239,68,68,0.3);}

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
        <a href="admin_manage_students.php" class="active-link">👥 Manage Students</a>
        <a href="admin_quiz_logs.php">📊 All Quiz Logs</a>
    </div>
    
    <div class="bottom-links sidebar-nav" style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; margin-top: auto;">
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="header" style="flex-direction: row; justify-content: flex-start; gap: 20px;"> 
        <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('active')">☰ Menu</button>
        <div>
            <h1>👥 Registered Students</h1>
            <p>View all student accounts and manage their access.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert">✅ <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card glass-panel">
        
        <form method="GET" action="admin_manage_students.php" class="search-container">
            <input type="text" name="search" class="search-input" placeholder="Search by username or matric no..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn-search">🔍 Search</button>
            <?php if ($search_query !== ''): ?>
                <a href="admin_manage_students.php" class="btn-clear">✖ Clear</a>
            <?php endif; ?>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Matric No.</th>
                        <th>Account Role</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($students) > 0): ?>
                        <?php foreach($students as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nomatric'] ?? 'N/A'); ?></td>
                                <td style="color: #94a3b8; font-style: italic;"><?php echo htmlspecialchars(ucfirst($row['role'])); ?></td>
                                <td style="text-align: right;">
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to completely delete <?php echo htmlspecialchars($row['username']); ?>? This cannot be undone!');" style="margin: 0;">
                                        <input type="hidden" name="delete_username" value="<?php echo htmlspecialchars($row['username']); ?>">
                                        <button type="submit" class="btn-delete">🗑️ Delete User</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #888; padding: 30px;">
                                <?php echo ($search_query !== '') ? 'No students found matching your search.' : 'No students registered yet!'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
