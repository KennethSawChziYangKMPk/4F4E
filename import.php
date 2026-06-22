<?php
session_start();
require 'config.php'; 

// SECURITY: Kick out anyone who isn't logged in OR isn't an admin
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// ==========================================
// 1. HANDLE CSV EXPORT
// ==========================================
if (isset($_POST['export_csv'])) {
    // Force download headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ken_math_questions_' . date('Y-m-d') . '.csv"');
    
    // Open the output stream
    $output = fopen('php://output', 'w');
    
    // Write the header row (Now 9 columns)
    fputcsv($output, ['Chapter', 'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Option', 'Method', 'Hint']);
    
    // Fetch all questions to export (FIXED: Added 'hint' to the SELECT statement)
    try {
        $stmt = $pdo->query("SELECT chapter, question_text, option_a, option_b, option_c, option_d, correct_option, method, hint FROM question ORDER BY chapter ASC, question_id ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    } catch (PDOException $e) {
        die("Error exporting data: " . $e->getMessage());
    }
    
    fclose($output);
    exit(); // Stop script execution so the HTML below doesn't get appended to your CSV
}

// ==========================================
// 2. HANDLE CSV FILE UPLOAD
// ==========================================
$message = "";
if (isset($_POST['upload_csv']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if ($_FILES["csv_file"]["size"] > 0) {
        $handle = fopen($file, "r");
        fgetcsv($handle); // Skip the first row (Header row)
        
        // FIXED: Prepare the statement ONCE outside the loop for massive performance boost. Added 'hint' and the 9th '?'.
        $insert_stmt = $pdo->prepare("INSERT INTO question (chapter, question_text, option_a, option_b, option_c, option_d, correct_option, method, hint) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (!is_array($data) || count($data) < 8) {
                continue; 
            }
            $data = array_pad($data, 9, '');
            
            try {
                $chapter = $data[0] ?? '';
                $question_text = $data[1] ?? '';
                $option_a = $data[2] ?? '';
                $option_b = $data[3] ?? '';
                $option_c = $data[4] ?? '';
                $option_d = $data[5] ?? '';
                $correct_option = $data[6] ?? '';
                $method_data = $data[7] ?? '';
                $hint_data = $data[8] ?? ''; // NEW HINT COLUMN
                
                // Just execute the already-prepared statement
                $insert_stmt->execute([
                    $chapter, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option, $method_data, $hint_data
                ]);
                $count++;
            } catch (PDOException $e) {
                // If one row fails (like a duplicate ID), it skips and keeps uploading the rest
            }
        }
        fclose($handle);
        $message = "<div class='alert success'>✅ Successfully imported $count questions!</div>";
    } else {
        $message = "<div class='alert error'>❌ Please upload a valid CSV file.</div>";
    }
}

// ==========================================
// 3. HANDLE QUESTION DELETION
// ==========================================
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $delete_stmt = $pdo->prepare("DELETE FROM question WHERE question_id = ?");
        $delete_stmt->execute([$delete_id]);
        $message = "<div class='alert success'>🗑️ Question deleted successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>❌ Error deleting question.</div>";
    }
}

// ==========================================
// 4. FETCH AND GROUP QUESTIONS
// ==========================================
$questions_by_chapter = [];
try {
    $stmt = $pdo->query("SELECT * FROM question ORDER BY chapter ASC, question_id DESC");
    $all_questions = $stmt->fetchAll();

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
    <title>4 Flat 4 Everyone - Manage Questions</title>
    
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

    <link rel="stylesheet" href="/Ken Project/assets/css/interior.css">
    <style>
        .main-content { max-width: 1200px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;}
        .header h1 { margin: 0; color: #fff; font-family: 'Inter'; font-size: 32px; font-weight: 700; letter-spacing: -1px;}
        
        .card { padding: 30px; margin-bottom: 30px; border-top: 4px solid #00e5ff;}
        .card h3 { margin-top: 0; color: #fff; font-family: 'Inter'; font-size: 22px; margin-bottom: 15px;}
        
        /* Form Styles */
        .upload-form { display: flex; gap: 15px; align-items: center; }
        input[type="file"] { border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px; color: #e2e8f0; font-family: 'Inter'; width: 100%; max-width: 300px;}
        .btn-upload { background: rgba(0,229,255,0.1); color: #00e5ff; border: 1px solid rgba(0,229,255,0.3); padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Inter'; transition: all 0.2s; white-space: nowrap;}
        .btn-upload:hover { background: #00e5ff; color: #0b0f19; box-shadow: 0 0 15px rgba(0,229,255,0.4); }

        .btn-export { background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Inter'; margin-left: auto; transition: all 0.2s;}
        .btn-export:hover { background: #10b981; color: #fff; box-shadow: 0 0 15px rgba(16,185,129,0.4); }
        .upload-container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }

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

        /* Table Styles */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 0; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1;}
        th { background-color: rgba(255,255,255,0.05); color: #e2e8f0; font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 1px;}
        tr:hover td { background-color: rgba(255,255,255,0.02); }
        
        /* Action Buttons */
        .btn-edit { background: rgba(245,158,11,0.1); color: #f59e0b; padding: 8px 15px; text-decoration: none; border-radius: 6px; font-size: 14px; margin-right: 5px; font-weight: 600; border: 1px solid rgba(245,158,11,0.3); transition: all 0.2s;}
        .btn-edit:hover { background: #f59e0b; color: #fff;}
        .btn-delete { background: rgba(239,68,68,0.1); color: #ef4444; padding: 8px 15px; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600; border: 1px solid rgba(239,68,68,0.3); transition: all 0.2s;}
        .btn-delete:hover { background: #ef4444; color: #fff;}
        
        /* Alerts */
        .alert { padding: 15px; margin-bottom: 25px; border-radius: 8px; font-weight: 600; }
        .success { background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
        .error { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }

        /* Specific sidebar override since Admin has different links */
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
        <a href="import.php" class="active-link">📝 Manage Questions</a>
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
        <h1>⚙️ Manage Questions Data</h1>
    </div>

    <?php echo $message; ?>

    <div class="card glass-panel">
        <h3>📂 Bulk Upload / Export Questions (CSV)</h3>
        <p style="font-size: 14px; color: #94a3b8; margin-bottom: 20px;">
            Your CSV file must have exactly 9 columns in this order: <strong>Chapter, Question Text, Option A, Option B, Option C, Option D, Correct Option (A/B/C/D), Method, Hint</strong>.
        </p>
        
        <div class="upload-container">
            <form class="upload-form" action="import.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" required>
                <button type="submit" name="upload_csv" class="btn-upload">Upload & Import</button>
            </form>

            <form action="import.php" method="POST">
                <button type="submit" name="export_csv" class="btn-export">📥 Export All to CSV</button>
            </form>
        </div>
    </div>

    <div class="card" style="padding: 0; border: none; background: transparent;">
        <h3 style="margin-bottom: 20px;">📝 Manage Existing Questions</h3>
        
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
                                    <th>Question Text</th>
                                    <th>Correct Answer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $q): ?>
                                    <tr>
                                        <td><?php echo $q['question_id']; ?></td>
                                        <td><?php echo htmlspecialchars(substr($q['question_text'], 0, 120)) . '...'; ?></td>
                                        <td><strong><?php echo htmlspecialchars($q['correct_option']); ?></strong></td>
                                        <td style="white-space: nowrap;">
                                            <a href="edit_question.php?question_id=<?php echo $q['question_id']; ?>" class="btn-edit">✏️ Edit</a>
                                            <a href="import.php?delete_id=<?php echo $q['question_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this question forever?');">🗑️ Delete</a>
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
                No questions found in the database. Upload a CSV to get started!
            </div>
        <?php endif; ?>
        
    </div>
</div>

</body>
</html>
