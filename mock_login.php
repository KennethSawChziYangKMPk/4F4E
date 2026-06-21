<?php
session_start();
$_SESSION['username'] = 'Test Student';
header("Location: dashboard.php");
exit;
?>
