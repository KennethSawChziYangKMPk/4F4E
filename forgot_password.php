<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config.php'; 

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $matric = trim($_POST['matric']); 
    $new_pass = $_POST['new_pass']; 

    try {
        // SECURITY UPGRADE: Check for BOTH username AND NoMatric using PDO
        $check_sql = "SELECT * FROM student WHERE username = :user AND NoMatric = :matric";
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute(['user' => $user, 'matric' => $matric]);

        // If find exactly 1 row that matches BOTH...
        if ($stmt->rowCount() === 1) {
            $update_sql = "UPDATE student SET pass = :pass WHERE username = :user";
            $update_stmt = $pdo->prepare($update_sql);
            
            if ($update_stmt->execute(['pass' => $new_pass, 'user' => $user])) {
                echo "<script>
                        alert('Password reset successful! You can now login with your new password.');
                        window.location.href = 'login.php';
                      </script>";
                exit();
            } else {
                $message = "Error updating password.";
            }
        } else {
            // If they get either one wrong, show a generic error
            $message = "Invalid Username or Matrik No.";
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4 Flat 4 Everyone - Password Reset</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- KaTeX CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css" crossorigin="anonymous">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/login.css">
    <style>
        /* Modify the generic UI layout specifically for the reset card */
        .login-container {
            max-width: 500px; 
        }
        
        .login-card {
            padding: 30px 40px; 
            max-height: 85vh; /* Prevents the card from being taller than 85% of the screen */
            overflow-y: auto; /* Adds a scrollbar if the content is too tall */
        }
        
        .input-group {
            margin-bottom: 12px; 
        }

        /* --- Custom Scrollbar for the Card --- */
        .login-card::-webkit-scrollbar {
            width: 8px;
        }
        .login-card::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        .login-card::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }
        .login-card::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4); 
        }
    </style>
</head>
<body>

    <!-- Interactive Background Layer -->
    <div id="universe-container">
        <canvas id="math-canvas"></canvas>
        <div id="formula-nodes"></div>
    </div>

    <!-- Foreground Login Layer -->
    <div class="login-container">
        <div class="login-card">
            <div class="logo-area" style="margin-bottom: 20px;">
                <span class="logo-icon">∞</span>
                <h2>Password Reset</h2>
                <p>Verify your identity to reset your password.</p>
            </div>

            <?php if($message != ""): ?>
                <div class="error-msg"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST" id="resetForm" novalidate>
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter Username" required autocomplete="off">
                </div>
                <div class="input-group">
                    <label for="matric">Matric No</label>
                    <input type="text" id="matric" name="matric" placeholder="e.g. MS23..." required maxlength='12'>
                </div>
                <div class="input-group">
                    <label for="new_pass">New Password</label>
                    <input type="password" id="new_pass" name="new_pass" placeholder="Enter New Password" required>
                </div>
                
                <button type="submit" class="login-btn">Update Password</button>
            </form>

            <div class="register-link">
                <a href="login.php">← Back to Sign In</a>
            </div>
            
            <div style="text-align: center; margin-top: 20px; z-index: 50; position: relative;">
                <?php include 'footer.php'; ?>
            </div>
        </div>
    </div>

    <!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script defer src="/assets/js/login-ui.js"></script>
    <script defer src="/assets/js/login-background.js"></script>

    <!-- Specific Reset Validation extending login-ui.js -->
    <script defer>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById('resetForm');
            
            form.addEventListener('submit', function(event) {
                // Remove previous complex errors
                form.querySelectorAll('.custom-popup.complex-error').forEach(e => e.remove());
                
                let isComplexValid = true;
                const matricInput = document.getElementById('matric');

                // NEW: Rules for Matric Number!
                if (matricInput.value.trim()) {
                    const matricRegex = /^[A-Z]{2}\d{10}$/;
                    if (!matricRegex.test(matricInput.value)) {
                        isComplexValid = false;
                        matricInput.style.borderColor = '#ff4d4f'; 
                        
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'custom-popup complex-error';
                        errorMsg.innerText = "Must be 2 Capital letters followed by 10 numbers (e.g., MS23...1234).";
                        
                        matricInput.parentNode.insertBefore(errorMsg, matricInput.nextSibling);
                    }
                }
                
                if (!isComplexValid) {
                    event.preventDefault(); 
                }
            });
        });
    </script>
</body>
</html>
