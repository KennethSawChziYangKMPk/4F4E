<?php
session_start();
require 'config.php'; // Load the Supabase connection!

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Grab the form data
    $username = trim($_POST['username']);
    $password = $_POST['pass']; 
    $matric = trim($_POST['NoMatric']);
    
    // FIX 1: Look for 'sem1_grade' to match your HTML form!
    $s1_grade = isset($_POST['sem1_grade']) ? (float)$_POST['sem1_grade'] : 0.00;
    
    $college = isset($_POST['college']) ? $_POST['college'] : 'Not Specified';
    $role = 'student'; // Default role for new signups

    try {
        // 2. Prepare the INSERT statement using PDO
        $sql = "INSERT INTO student (username, pass, s1_grade, NoMatric, role, college) 
                VALUES (:username, :pass, :s1_grade, :NoMatric, :role, :college)";
        
        $stmt = $pdo->prepare($sql);
        
        // 3. Execute and pass the variables into the database
        $stmt->execute([
            'username' => $username,
            'pass' => $password, 
            's1_grade' => $s1_grade,
            'NoMatric' => $matric,
            'role' => $role,
            'college' => $college
        ]);

        // FIX 2: Activate the redirect to login page!
        echo "<script>
                alert('Registration successful! You can now log in.');
                window.location.href = 'login.php';
              </script>";
        exit();

    } catch (PDOException $e) {
        // Handle Duplicate errors
        if ($e->getCode() == 23505) { 
            $error = "That Username or Matric Number is already registered!";
        } else {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4 Flat 4 Everyone - Sign Up</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- KaTeX CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css" crossorigin="anonymous">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/login.css">
    <style>
        /* Modify the generic UI layout specifically for the larger register card */
        .login-container {
            max-width: 500px; /* Slightly wider */
        }
        
        .login-card {
            padding: 30px 40px; 
            max-height: 85vh; /* Prevents the card from ever being taller than 85% of the screen */
            overflow-y: auto; /* Adds a scrollbar ONLY if the content is taller than 85vh */
        }
        
        .input-group {
            margin-bottom: 12px; /* Compress margin slightly */
        }

        /* --- Custom Scrollbar for the Card --- */
        .login-card::-webkit-scrollbar {
            width: 8px;
        }
        .login-card::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05); /* Very faint track */
            border-radius: 10px;
        }
        .login-card::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2); /* Semi-transparent thumb */
            border-radius: 10px;
        }
        .login-card::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4); /* Brightens when hovered */
        }
    </style>
</head>
<body>

    <!-- Interactive Background Layer -->
    <div id="universe-container">
        <canvas id="math-canvas"></canvas>
        <div id="formula-nodes"></div>
    </div>

    <!-- Foreground Register Layer -->
    <div class="login-container">
        <div class="login-card">
            <div class="logo-area" style="margin-bottom: 20px;">
                <span class="logo-icon">∞</span>
                <h2>Create an Account</h2>
                <p>Join the Mathematical Universe</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" id="registerForm" novalidate>
                
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Choose a Username" autocomplete="off">
                </div>

                <div class="input-group">
                    <label for="pass">Password</label>
                    <input type="password" id="pass" name="pass" required placeholder="Create a Password">
                </div>

                <div class="input-group">
                    <label for="NoMatric">Matric No</label>
                    <input type="text" id="NoMatric" name="NoMatric" required placeholder="e.g. MS23..." maxlength='12'>
                </div>

                <div class="input-group">
                    <label for="sem1_grade">SM015 Result</label>
                    <input type="number" id="sem1_grade" name="sem1_grade" required placeholder="e.g. 4.00" step="0.01">
                </div>

                <div class="input-group">
                    <label for="college">College</label>
                    <select id="college" name="college" required>
                        <option value="" disabled selected>Select your College</option>
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

                <button type="submit" class="login-btn" style="margin-top: 10px;">Register</button>

            </form>

            <div class="register-link">
                Already have an account? <a href="login.php">Sign In Here</a>
            </div>
        </div>
    </div>

    <!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script defer src="/assets/js/login-ui.js"></script>
    <script defer src="/assets/js/login-background.js"></script>
    
    <!-- Specific Register Validation extending login-ui.js -->
    <script defer>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById('registerForm');
            
            form.addEventListener('submit', function(event) {
                // Remove previous complex errors
                form.querySelectorAll('.custom-popup.complex-error').forEach(e => e.remove());
                
                let isComplexValid = true;

                const matricInput = document.getElementById('NoMatric');
                const gradeInput = document.getElementById('sem1_grade');

                // 1. Rules for Matric Number
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

                // 2. Rules for Grade
                if (gradeInput.value.trim()) {
                    const gradeValue = parseFloat(gradeInput.value);
                    const validDecimals = /^\d+(\.\d{1,2})?$/.test(gradeInput.value);

                    if (isNaN(gradeValue) || gradeValue < 0 || gradeValue > 4 || !validDecimals) {
                        isComplexValid = false;
                        gradeInput.style.borderColor = '#ff4d4f'; 
                        
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'custom-popup complex-error';
                        errorMsg.innerText = "Grade must be between 0.00 and 4.00 (max 2 decimals).";
                        gradeInput.parentNode.insertBefore(errorMsg, gradeInput.nextSibling);
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
