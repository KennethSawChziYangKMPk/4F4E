<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mathematical Universe - Login</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- KaTeX CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css" crossorigin="anonymous">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/login.css">
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
            <div class="logo-area">
                <span class="logo-icon">∞</span>
                <h2>Ken Math App</h2>
                <p>Welcome back. Please sign in to continue.</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <form method="POST" action="login_process.php" novalidate id="loginForm">
                
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter your username" autocomplete="off">
                </div>

                <div class="input-group">
                    <label for="pass">Password</label>
                    <input type="password" id="pass" name="pass" required placeholder="Enter your password">
                </div>

                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn">Sign In</button>

            </form>

            <div class="register-link">
                Don't have an account? <a href="register.php">Create one here</a>
            </div>
        </div>
    </div>

    <!-- KaTeX JS (Deferred to ensure non-blocking) -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script defer src="/assets/js/login-ui.js"></script>
    <script defer src="/assets/js/login-background.js"></script>

</body>
</html>
