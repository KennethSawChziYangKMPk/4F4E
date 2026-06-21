<?php
session_start();

// SECURITY: Admins only!
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
require 'config.php'; 

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_admin'])) {
    
    $new_username = trim($_POST['new_username']);
    $new_password = $_POST['new_pass']; 
    $confirm_password = $_POST['confirm_pass'];
    
    $matric = isset($_POST['NoMatric']) && trim($_POST['NoMatric']) !== '' ? trim($_POST['NoMatric']) : 'N/A';
    $college = isset($_POST['college']) && $_POST['college'] !== '' ? $_POST['college'] : 'Not Specified';
    
    // Default values for fields required by the table but not relevant for admins
    $s1_grade = 0.00;
    $role = 'admin'; 

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            $sql = "INSERT INTO student (username, pass, s1_grade, NoMatric, role, college) 
                    VALUES (:username, :pass, :s1_grade, :NoMatric, :role, :college)";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                'username' => $new_username,
                'pass' => $new_password, 
                's1_grade' => $s1_grade,
                'NoMatric' => $matric,
                'role' => $role,
                'college' => $college
            ]);

            $message = "New admin account '{$new_username}' was created successfully!";
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23505) { 
                $error = "That Username or Matric Number is already registered!";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathematical Universe - Create Admin</title>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        .main-content { max-width: 800px; }
        .header { display: flex; flex-direction: column; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;}
        .header h1 { margin: 0; color: #fff; font-family: 'Inter'; font-size: 32px; font-weight: 700; letter-spacing: -1px;}
        .header p { color: #94a3b8; font-family: 'Inter'; font-size: 16px; margin-top: 5px;}
        
        .card { padding: 40px; margin-bottom: 30px; border-top: 4px solid #d946ef;}
        
        .alert-success { padding: 15px 20px; background: rgba(16,185,129,0.1); color: #10b981; border-radius: 8px; border-left: 4px solid #10b981; font-weight: 600; margin-bottom: 25px;}
        .alert-error { padding: 15px 20px; background: rgba(239,68,68,0.1); color: #ef4444; border-radius: 8px; border-left: 4px solid #ef4444; font-weight: 600; margin-bottom: 25px;}

        /* Form Controls */
        .form-group { margin-bottom: 20px;}
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #94a3b8; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        
        input[type="text"], input[type="password"], select { 
            width: 100%; padding: 14px 20px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); 
            border-radius: 8px; font-size: 15px; color: #fff; font-family: 'Inter'; outline: none; transition: border 0.3s; box-sizing: border-box;
        }
        select option { background: #0b0f19; color: #fff; }
        input:focus, select:focus { border-color: #d946ef; box-shadow: 0 0 15px rgba(217,70,239,0.1);}
        
        .btn-submit { background: rgba(217,70,239,0.1); color: #d946ef; border: 1px solid rgba(217,70,239,0.3); padding: 15px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px; font-family: 'Inter'; transition: 0.2s; white-space: nowrap; width: 100%; margin-top: 15px; text-transform: uppercase; letter-spacing: 1px;}
        .btn-submit:hover { background: #d946ef; color: #0b0f19; box-shadow: 0 0 20px rgba(217,70,239,0.4); }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .sidebar-bottom { margin-top: auto; padding: 20px 0; border-top: 1px solid rgba(255,255,255,0.05); }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; gap: 0;}
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
        <a href="admin_dashboard.php">🛡️ Admin Home</a>
        <a href="admin_create_account.php" class="active-link">➕ Create Admin</a>
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
    <div class="header" style="flex-direction: row; justify-content: flex-start; gap: 20px;"> 
        <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('active')">☰ Menu</button>
        <div>
            <h1>➕ Create Admin Account</h1>
            <p>Grant administrative access to a new user.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert-success">✅ <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card glass-panel">
        <form method="POST" action="admin_create_account.php" id="adminRegisterForm">
            
            <div class="form-group">
                <label for="new_username">Admin Username <span style="color: #ef4444;">*</span></label>
                <input type="text" id="new_username" name="new_username" required placeholder="Enter username" autocomplete="off">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="new_pass">Password <span style="color: #ef4444;">*</span></label>
                    <input type="password" id="new_pass" name="new_pass" required placeholder="Create a password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_pass">Confirm Password <span style="color: #ef4444;">*</span></label>
                    <input type="password" id="confirm_pass" name="confirm_pass" required placeholder="Re-type password">
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 30px 0;">

            <div class="form-group">
                <label for="NoMatric">Matric No <span style="color: #64748b; font-size: 12px; font-weight:normal;">(Optional)</span></label>
                <input type="text" id="NoMatric" name="NoMatric" placeholder="e.g. MS23...">
            </div>

            <div class="form-group">
                <label for="college">College <span style="color: #64748b; font-size: 12px; font-weight:normal;">(Optional)</span></label>
                <select id="college" name="college">
                    <option value="" selected>Select your College</option>
                    <option value="KMM">KMM</option>
                    <option value="KMNS">KMNS</option>
                    <option value="KMPP">KMPP</option>
                    <option value="KMP">KMP</option>
                    <option value="KML">KML</option>
                    <option value="KMJ">KMJ</option>
                    <option value="KMPK">KMPK</option>
                    <option value="KMK">KMK</option>
                    <option value="KMPH">KMPH</option>
                    <option value="KMTK">KMTK</option>
                    <option value="KMTPH">KMTPH</option>
                    <option value="KMTJ">KMTJ</option>
                    <option value="KMS">KMS</option>
                    <option value="KMKT">KMKT</option>
                    <option value="KMSW">KMSW</option>
                    <option value="KMKULIM">KMKULIM</option>
                    <option value="KMKN">KMKN</option>
                    <option value="Others">Others</option>
                </select>
            </div>

            <button type="submit" name="create_admin" class="btn-submit">➕ Register Admin</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('adminRegisterForm').addEventListener('submit', function(event) {
        var p1 = document.getElementById('new_pass').value;
        var p2 = document.getElementById('confirm_pass').value;
        if (p1 !== p2) {
            alert("Passwords do not match!");
            event.preventDefault();
        }
    });

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>
