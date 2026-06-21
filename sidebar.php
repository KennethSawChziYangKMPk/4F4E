<?php 
// Grab the current page name so we can highlight the active link
$current_page = basename($_SERVER['PHP_SELF']); 
// Make sure username is set, otherwise default to Student
$username = $_SESSION['username'] ?? 'Student';
?>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="sidebar glass-panel" id="sidebar" style="border-radius: 0; border-top: none; border-bottom: none; border-left: none;">
    <button class="close-sidebar" onclick="toggleSidebar()">×</button>
    
    <div class="brand">
        <span class="logo-icon">∞</span>
        <h2>Ken Math App</h2>
    </div>

    <div class="sidebar-label">Navigation</div>
    <div class="sidebar-nav">
        <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active-link' : ''; ?>">🏠 Dashboard</a>
        <a href="chapters.php" class="<?php echo ($current_page == 'chapters.php') ? 'active-link' : ''; ?>">📝 Take a Quiz</a>
        <a href="formulae.php" class="<?php echo ($current_page == 'formulae.php') ? 'active-link' : ''; ?>">📐 Formulae</a>
        <a href="instructions.php" class="<?php echo ($current_page == 'instructions.php') ? 'active-link' : ''; ?>">📖 User Instructions</a>
        <a href="student_analytics.php" class="<?php echo ($current_page == 'student_analytics.php') ? 'active-link' : ''; ?>">📈 My Analytics</a>
    </div>

    <div class="sidebar-label" style="margin-top: 10px;">Support</div>
    <div class="bottom-links sidebar-nav">
        <a href="rate.php" class="<?php echo ($current_page == 'rate.php') ? 'active-link' : ''; ?>">⭐ Rate Me</a>
        <a href="credit.php" class="<?php echo ($current_page == 'credit.php') ? 'active-link' : ''; ?>">🏆 Credits</a>
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('overlay').classList.toggle('active');
    }
</script>
