<?php
session_start();
require 'config.php'; // 1. Swapped to Supabase connection!

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// 2. AUTO-CREATE TABLE: Updated for PostgreSQL syntax (SERIAL instead of AUTO_INCREMENT)
try {
    $table_sql = "CREATE TABLE IF NOT EXISTS app_reviews (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        overall INT NOT NULL,
        difficulty INT NOT NULL,
        ux INT NOT NULL,
        review TEXT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($table_sql);
} catch (PDOException $e) {
    // If it fails, we silently ignore it assuming the table already exists from your SQL dump
}

// 3. PROCESS THE FORM SUBMISSION
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $overall = isset($_POST['overall']) ? (int)$_POST['overall'] : 0;
    $difficulty = isset($_POST['difficulty']) ? (int)$_POST['difficulty'] : 0;
    $ux = isset($_POST['ux']) ? (int)$_POST['ux'] : 0;
    $review = isset($_POST['review']) ? trim($_POST['review']) : "";
    
    if ($overall > 0 && $difficulty > 0 && $ux > 0) {
        
        // Check if user already rated
        $check = $pdo->prepare("SELECT id FROM app_reviews WHERE username = :username");
        $check->execute(['username' => $username]);
        $result = $check->fetch();
        
        if ($result) {
            // Update existing rating
            $update = $pdo->prepare("UPDATE app_reviews SET overall=:overall, difficulty=:diff, ux=:ux, review=:rev WHERE username=:username");
            $update->execute([
                'overall' => $overall,
                'diff' => $difficulty,
                'ux' => $ux,
                'rev' => $review,
                'username' => $username
            ]);
            $message = "Your rating has been updated! Thank you!";
        } else {
            // Insert new rating
            $insert = $pdo->prepare("INSERT INTO app_reviews (username, overall, difficulty, ux, review) VALUES (:username, :overall, :diff, :ux, :rev)");
            $insert->execute([
                'username' => $username,
                'overall' => $overall,
                'diff' => $difficulty,
                'ux' => $ux,
                'rev' => $review
            ]);
            $message = "Thank you for your feedback!";
        }
    } else {
        $message = "Please provide a star rating for all three categories.";
    }
}

// 4. Fetch user's current rating to display the stars
$cur_ov = 0; $cur_diff = 0; $cur_ux = 0; $cur_rev = "";
$fetch = $pdo->prepare("SELECT overall, difficulty, ux, review FROM app_reviews WHERE username = :username");
$fetch->execute(['username' => $username]);
$row = $fetch->fetch();

if ($row) {
    $cur_ov = $row['overall'];
    $cur_diff = $row['difficulty'];
    $cur_ux = $row['ux'];
    $cur_rev = $row['review'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate This Website</title>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        .rate-container { padding: 40px; margin: 20px auto 0; max-width: 1000px; }
        .rate-container h2 { color: #ffffff; font-family: 'Inter'; font-weight: 700; letter-spacing: -1px; margin-top: 0; text-align: center; font-size: 32px; margin-bottom: 10px;}
        .rate-container p { color: #94a3b8; margin-bottom: 30px; text-align: center; font-size: 16px;}
        
        .rating-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 15px 25px; background: rgba(0,0,0,0.3); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
        .rating-label { font-size: 16px; font-weight: 600; color: #e2e8f0; }
        
        /* Interactive CSS Stars */
        .star-rating { display: flex; flex-direction: row-reverse; gap: 8px; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 36px; color: rgba(255,255,255,0.1); cursor: pointer; transition: color 0.2s, text-shadow 0.2s; }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #00e5ff; text-shadow: 0 0 15px rgba(0, 229, 255, 0.6); }
        
        textarea { width: 100%; height: 140px; padding: 20px; border: 1px solid rgba(0, 229, 255, 0.2); border-radius: 12px; font-family: 'Inter', sans-serif; font-size: 15px; resize: vertical; box-sizing: border-box; margin-top: 20px; margin-bottom: 20px; background: rgba(15, 23, 42, 0.4); color: #fff; transition: border-color 0.2s; }
        textarea:focus { outline: none; border-color: #00e5ff; box-shadow: 0 0 10px rgba(0, 229, 255, 0.1); }
        textarea::placeholder { color: #64748b; }
        
        .btn-submit { background: rgba(0,229,255,0.1); color: #00e5ff; border: 1px solid rgba(0,229,255,0.3); padding: 15px 30px; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; width: 100%; transition: all 0.2s; font-family: 'Inter'; box-shadow: 0 4px 15px rgba(0, 229, 255, 0.05); }
        .btn-submit:hover { background: #00e5ff; color: #0b0f19; box-shadow: 0 0 20px rgba(0, 229, 255, 0.4); }
        
        .alert { padding: 15px; background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 8px; margin-bottom: 25px; text-align: center; font-weight: 600; }

        @media (max-width: 768px) {
            .rating-row { flex-direction: column; align-items: flex-start; gap: 10px; }
            .star-rating { align-self: flex-start; }
            .rate-container h2 { font-size: 28px; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <button class="menu-btn" onclick="toggleSidebar()">☰ Menu</button>
    
    <div class="rate-container glass-panel">
        <h2>Rate Your Experience</h2>
        <p>Please grade the app on the following criteria!</p>

        <?php if ($message): ?>
            <div class="alert">✅ <?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="rate.php">
            
            <div class="rating-row">
                <div class="rating-label">Overall Experience</div>
                <div class="star-rating">
                    <?php for($i=5; $i>=1; $i--): ?>
                        <input type="radio" id="ov<?php echo $i; ?>" name="overall" value="<?php echo $i; ?>" <?php echo ($cur_ov == $i) ? 'checked' : ''; ?> />
                        <label for="ov<?php echo $i; ?>" title="<?php echo $i; ?> stars">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="rating-row">
                <div class="rating-label">Quiz Difficulty (1=Easy, 5=Hard)</div>
                <div class="star-rating">
                    <?php for($i=5; $i>=1; $i--): ?>
                        <input type="radio" id="df<?php echo $i; ?>" name="difficulty" value="<?php echo $i; ?>" <?php echo ($cur_diff == $i) ? 'checked' : ''; ?> />
                        <label for="df<?php echo $i; ?>" title="<?php echo $i; ?> stars">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="rating-row">
                <div class="rating-label">User Interface & Navigation</div>
                <div class="star-rating">
                    <?php for($i=5; $i>=1; $i--): ?>
                        <input type="radio" id="ux<?php echo $i; ?>" name="ux" value="<?php echo $i; ?>" <?php echo ($cur_ux == $i) ? 'checked' : ''; ?> />
                        <label for="ux<?php echo $i; ?>" title="<?php echo $i; ?> stars">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <textarea name="review" placeholder="Any extra feedback or feature requests? (Optional)"><?php echo htmlspecialchars($cur_rev); ?></textarea>
            
            <button type="submit" class="btn-submit">
                <?php echo ($cur_ov > 0) ? "Update My Ratings" : "Submit Ratings"; ?>
            </button>
        </form>
    </div>
</div>

</body>
</html>
