<?php
session_start();
session_destroy(); // Clears the "I am logged in" status
header("Location: login.php");
exit();
?>
