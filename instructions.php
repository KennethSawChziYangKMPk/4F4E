<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4 Flat 4 Everyone - User Instructions</title>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        .header-section { margin-bottom: 40px; }
        .header-section h1 { color: #ffffff; font-family: 'Inter'; font-weight: 700; letter-spacing: -1px; font-size: 36px; margin-bottom: 10px; }
        .header-section p { color: #94a3b8; font-size: 16px; line-height: 1.6; }

        /* Instruction Cards Layout */
        .step-card { margin-bottom: 25px; display: flex; gap: 20px; align-items: flex-start; padding: 30px; transition: transform 0.2s ease, box-shadow 0.2s ease; border-left: 4px solid transparent; }
        .step-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0, 229, 255, 0.15); }
        
        .step-1 { border-left-color: #00e5ff; } /* Cyan */
        .step-2 { border-left-color: #10b981; } /* Green */
        .step-3 { border-left-color: #d946ef; } /* Purple */
        .step-4 { border-left-color: #f59e0b; } /* Orange */
        .step-5 { border-left-color: #ef4444; } /* Red */
        .step-6 { border-left-color: #0ea5e9; } /* Teal */

        .step-icon { font-size: 40px; background: rgba(0,0,0,0.3); width: 75px; height: 75px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid rgba(255,255,255,0.05); flex-shrink: 0; box-shadow: 0 0 15px rgba(0,0,0,0.5) inset;}
        
        .step-content h3 { margin-top: 0; margin-bottom: 10px; color: #fff; font-size: 22px; font-family: 'Inter'; font-weight: 600;}
        .step-content p { color: #cbd5e1; line-height: 1.6; margin: 0; font-size: 15px;}
        .step-content ul { margin-top: 10px; margin-bottom: 0; color: #cbd5e1; padding-left: 20px; line-height: 1.6; font-size: 15px;}
        .step-content li { margin-bottom: 8px; }

        @media (max-width: 768px) {
            .step-card { flex-direction: column; align-items: center; text-align: center; gap: 15px; }
            .step-content ul { text-align: left; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <button class="menu-btn" onclick="toggleSidebar()">☰ Menu</button>
    
    <div class="header-section">
        <h1>How to Use 4F4E?</h1>
        <p>Welcome to your personal study companion! Follow this quick guide to get the most out of your practice sessions and track your progress effectively.</p>
    </div>

    <div class="step-card glass-panel step-1">
        <div class="step-icon">🏠</div>
        <div class="step-content">
            <h3>1. The Dashboard</h3>
            <p>Your main command center. Here you can keep an eye on the <strong>Final Exam Countdown</strong>, track your total completed quizzes, and quickly jump back into your recent chapters to keep your momentum going.</p>
        </div>
    </div>

    <div class="step-card glass-panel step-2">
        <div class="step-icon">📝</div>
        <div class="step-content">
            <h3>2. Taking a Quiz</h3>
            <p>Ready to drill some questions?</p>
            <ul>
                <li>Click <strong>Take a Quiz</strong> in the sidebar.</li>
                <li>Select a chapter. <strong>A timer will start automatically!</strong></li>
                <li>Answer the multiple-choice questions. If you get one wrong, the app will show you the step-by-step solution so you learn instantly.</li>
            </ul>
        </div>
    </div>

    <div class="step-card glass-panel step-3">
        <div class="step-icon">📈</div>
        <div class="step-content">
            <h3>3. My Analytics</h3>
            <p>Don't just guess how you are doing—track it. Use the <strong>My Analytics</strong> tab to review your past scores, see how much time you are spending per quiz, and identify exactly which chapters you need to improve before the final exam.</p>
        </div>
    </div>

    <div class="step-card glass-panel step-4">
        <div class="step-icon">📐</div>
        <div class="step-content">
            <h3>4. Using the Formulae Sheet</h3>
            <p>Forget a formula? Click on <strong>Formulae</strong> to access a beautifully formatted cheat sheet. It doesn't just list equations; it gives you the logic and tricks (like the Binomial Continuity Correction boundary rules) to solve problems faster.</p>
        </div>
    </div>

    <div class="step-card glass-panel step-5">
        <div class="step-icon">🏆</div>
        <div class="step-content">
            <h3>5. The Leaderboard</h3>
            <p>A little friendly competition never hurts! Check the <strong>Leaderboard</strong> to see how your scores stack up against your friends and other students. Grind those chapters, improve your accuracy, and claim the top spot.</p>
        </div>
    </div>

    <div class="step-card glass-panel step-6">
        <div class="step-icon">🧮</div>
        <div class="step-content">
            <h3>6. Maximize Your Learning</h3>
            <p>To get the most out of this app, grab a pen and paper! Always <strong>calculate the answer</strong> yourself instead of blindly guessing. When reviewing a wrong answer, focus on understanding the step-by-step logic rather than just memorizing the final letter.</p>
        </div>
    </div>

</div>

</body>
</html>
