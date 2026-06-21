<?php
// 1. Database Connection (Update 'your_database_name' to your actual DB name)
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'root';
$password = ''; // Default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

$message = "";

// 2. Process the form when it is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = $_POST['rating'];
    $feedback_text = trim($_POST['feedback_text']);

    // Securely insert into database using prepared statements
    $sql = "INSERT INTO chapter_feedback (rating, feedback_text) VALUES (:rating, :feedback_text)";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters and execute
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindParam(':feedback_text', $feedback_text, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        $message = "<p style='color: green;'>Thank you! Your feedback helps us improve the questions.</p>";
    } else {
        $message = "<p style='color: red;'>Oops! Something went wrong. Please try again.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chapter 1 Feedback</title>
    <link rel="stylesheet" href="/assets/css/interior.css">
    <style>
        body { margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px;}
        .feedback-container { width: 100%; max-width: 600px; padding: 40px; }
        .feedback-container h2 { margin-top: 0; color: #fff; font-family: 'Inter'; font-size: 32px; letter-spacing: -1px; margin-bottom: 10px; }
        .feedback-container p { color: #94a3b8; margin-bottom: 30px; font-size: 16px; line-height: 1.5;}
        textarea { width: 100%; height: 120px; margin-top: 15px; padding: 20px; box-sizing: border-box; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,229,255,0.2); border-radius: 12px; color: #fff; font-family: 'Inter'; font-size: 15px; resize: vertical; transition: all 0.2s;}
        textarea:focus { outline: none; border-color: #00e5ff; box-shadow: 0 0 15px rgba(0,229,255,0.1); }
        select { width: 100%; padding: 15px 20px; margin-top: 15px; background: rgba(0,0,0,0.3); border: 1px solid rgba(0,229,255,0.2); border-radius: 12px; color: #fff; font-family: 'Inter'; font-size: 15px; appearance: none; cursor: pointer; transition: all 0.2s;}
        select:focus { outline: none; border-color: #00e5ff; box-shadow: 0 0 15px rgba(0,229,255,0.1); }
        option { background: #0b0f19; color: #fff; }
        label { color: #e2e8f0; font-weight: 600; font-size: 16px; display: block; margin-top: 25px;}
        
        button { background: rgba(0,229,255,0.1); color: #00e5ff; border: 1px solid rgba(0,229,255,0.3); padding: 15px; margin-top: 30px; cursor: pointer; border-radius: 8px; width: 100%; font-size: 16px; font-weight: 600; font-family: 'Inter'; transition: all 0.2s; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(0,229,255,0.05);}
        button:hover { background: #00e5ff; color: #0b0f19; box-shadow: 0 0 20px rgba(0,229,255,0.4); }
        
        .alert { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 15px; border-radius: 8px; font-weight: 600; margin-bottom: 20px; text-align: center;}
    </style>
</head>
<body>

<div class="feedback-container glass-panel">
    <h2>Rate Chapter 1: Newton-Raphson</h2>
    <p>Did you spot any errors? How was the difficulty? Let us know!</p>
    
    <?php if($message) echo "<div class='alert'>$message</div>"; ?>

    <form action="feedback.php" method="post">
        <label for="rating"><strong>Rate this chapter:</strong></label><br>
        <select name="rating" id="rating" required>
            <option value="" disabled selected>Select a rating...</option>
            <option value="5">⭐⭐⭐⭐⭐ - Perfect, no errors</option>
            <option value="4">⭐⭐⭐⭐ - Great, but minor issues</option>
            <option value="3">⭐⭐⭐ - Good, but spotted a calculation error</option>
            <option value="2">⭐⭐ - Too hard / Confusing</option>
            <option value="1">⭐ - Needs a lot of fixing</option>
        </select>
        
        <br><br>
        
        <label for="feedback_text"><strong>Leave your comments (Optional):</strong></label>
        <textarea name="feedback_text" id="feedback_text" placeholder="e.g., Question 8's answer seems slightly off..."></textarea>
        
        <button type="submit">Submit Feedback</button>
    </form>
</div>

</body>
</html>
