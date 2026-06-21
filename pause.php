<?php
session_start();
require 'config.php';

if (isset($_SESSION['username']) && isset($_GET['chapter']) && isset($_GET['time'])) {
    $username = $_SESSION['username'];
    $chapter = (int)$_GET['chapter'];
    $elapsed_seconds = (int)$_GET['time'];

    // Save as negative to trigger the "paused" logic
    $paused_time_value = -$elapsed_seconds;

    $stmt = $pdo->prepare("UPDATE quiz_progress SET start_time = ? WHERE username = ? AND chapter = ?");
    $stmt->execute([$paused_time_value, $username, $chapter]);
}

// Redirect back to chapters (update this if your menu page has a different name!)
header("Location: chapters.php");
exit();
?>
