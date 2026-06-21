<?php
session_start();
require 'config.php'; // Load your database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Grab the data from the login form
    $username = trim($_POST['username']);
    $password = $_POST['pass'];

    try {
        // Query the database to find this exact user
        $sql = "SELECT * FROM student WHERE username = :username AND pass = :pass";
        $stmt = $pdo->prepare($sql);
        
        // Execute with the user's inputs
        $stmt->execute([
            'username' => $username,
            'pass' => $password
        ]);

        // If we find exactly 1 matching row, the login is successful!
        if ($stmt->rowCount() == 1) {
            
            // Fetch the user's data from the database
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Store their info in the session so other pages know who is logged in
            $_SESSION['username'] = $user['username'];
            $_SESSION['NoMatric'] = $user['NoMatric'];
            $_SESSION['role'] = $user['role'];
            
            // ROUTING LOGIC: Admin goes to admin dashboard, students go to regular dashboard!
            // I used strtolower() just in case 'Admin' is capitalized in your database
            if (strtolower($user['role']) === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
            
        } else {
            // Login failed! Send them back to login.php with an error message in the URL
            $error_message = urlencode("Invalid Username or Password.");
            header("Location: login.php?error=" . $error_message);
            exit();
        }

    } catch (PDOException $e) {
        // If the database crashes, show a safe error
        $error_message = urlencode("Database connection error.");
        header("Location: login.php?error=" . $error_message);
        exit();
    }
} else {
    // If someone tries to access this file directly without submitting the form, kick them out
    header("Location: login.php");
    exit();
}
?>
