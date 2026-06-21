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
    <title>4 Flat 4 Everyone - Credits</title>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        .header-section { margin-bottom: 40px; text-align: center; }
        .header-section h1 { color: #ffffff; margin-bottom: 10px; font-size: 36px; font-family: 'Inter'; font-weight: 700; letter-spacing: -1px; }
        .header-section p { color: #94a3b8; font-size: 16px; line-height: 1.6; }

        .credits-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-top: 30px; }

        .credit-card {
            padding: 30px 20px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-top: 4px solid transparent;
            display: flex;
            flex-direction: column;
        }
        .credit-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 229, 255, 0.15); }

        .avatar {
            width: 80px; height: 80px; border-radius: 50%;
            background: rgba(0,0,0,0.3); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; margin: 0 auto 20px;
            border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 0 15px rgba(0,0,0,0.5) inset;
        }

        .credit-card h3 { margin: 0 0 10px 0; color: #fff; font-size: 22px; font-family: 'Inter'; font-weight: 600;}
        .credit-card p.role { margin: 0 0 15px 0; color: #00e5ff; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; }
        .credit-card p.desc { color: #cbd5e1; line-height: 1.6; font-size: 15px; margin: 0 0 20px 0; flex-grow: 1; }
        
        .team-list { list-style: none; padding: 0; margin: 0; color: #e2e8f0; font-weight: 500; font-size: 15px; }
        .team-list li { margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05);}
        .team-list li:last-child { border-bottom: 0;}

        /* Special thanks section */
        .special-thanks {
            margin-top: 50px;
            padding: 40px;
            text-align: center;
            border-left: 4px solid #f59e0b;
            border-right: 4px solid #f59e0b;
        }
        .special-thanks h3 { margin-top: 0; color: #fff; font-size: 26px; font-family: 'Inter'; letter-spacing: -0.5px; margin-bottom: 15px;}
        .special-thanks p { color: #cbd5e1; line-height: 1.6; margin-bottom: 0; font-size: 16px; }
        .special-thanks strong { font-size: 22px; color: #f59e0b; display: inline-block; margin-top: 15px; font-weight: 700;}

        @media (max-width: 900px) { .credits-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .header-section h1 { font-size: 28px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <button class="menu-btn" onclick="toggleSidebar()">☰ Menu</button>
    
    <div class="header-section">
        <h1>Meet the Team</h1>
        <p>4 Flat 4 Everyone was built with passion to help students excel in mathematics. <br> Here are the amazing people who made this project possible.</p>
    </div>

    <div class="credits-grid">
        <div class="credit-card glass-panel" style="border-top-color: #e74c3c;">
            <div class="avatar">👨‍💻</div>
            <h3>Kenneth Saw Chzi Yang</h3>
            <p class="role">Project Lead</p>
            <p class="desc">Architected the core framework, managed the project timeline, and led the development of the application.</p>
        </div>

        <div class="credit-card glass-panel" style="border-top-color: #9b59b6;">
            <div class="avatar">🎨</div>
            <h3>Wong Wen Hao</h3>
            <p class="role">Website Designer</p>
            <p class="desc">Designed the sleek user interface, intuitive layout, and overall visual identity of the platform.</p>
        </div>

        <div class="credit-card glass-panel" style="border-top-color: #2ecc71;">
            <div class="avatar">📚</div>
            <h3 style="display:none;">Content Writers</h3>
            <p class="role">Content Writers</p>
            <p class="desc">Compiled the mathematical formulas, structured the chapter quizzes, and ensured academic accuracy.</p>
            <ul class="team-list">
                <li>Kenneth Saw Chzi Yang</li>
                <li>Koay Yu Hang</li>
                <li>Puvanesvaran A/L Anbalagan</li>
                <li>Rachel Lee Yi Wen</li>
                <li>Swetta A/P Sundar</li>
            </ul>
        </div>

        <div class="credit-card glass-panel" style="border-top-color: #f39c12;">
            <div class="avatar">🤖</div>
            <h3 style="display:none;">AI & Solutions QA</h3>
            <p class="role">AI & Solutions QA</p>
            <p class="desc">Leveraged AI to verify answer accuracy, generate step-by-step solutions, and brainstorm innovative features.</p>
            <ul class="team-list">
                <li>Joseph Ting Ming Reng</li>
                <li>Tay Ee Zhe</li>
            </ul>
        </div>
    </div>

    <div class="special-thanks glass-panel">
        <h3>Special Acknowledgements</h3>
        <p>
            A massive thank you to our esteemed lecturer, <br>
            <strong>Sir Thavarajah A/L Selvarajah</strong><br>
            for his invaluable guidance and continuous support throughout the development of this project.
        </p>
    </div>

</div>

</body>
</html>

