<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();
require_once '../includes/db.php';


// Get all contestants with categories and image
$contestants = [];
$result = $conn->query("
    SELECT c.*, c.status, GROUP_CONCAT(cat.name SEPARATOR ', ') as categories 
    FROM contestants c 
    LEFT JOIN contestant_category cc ON c.id = cc.contestant_id 
    LEFT JOIN categories cat ON cc.category_id = cat.id 
    WHERE c.status = 'approved'
    GROUP BY c.id 
    ORDER BY c.name
");
while ($row = $result->fetch_assoc()) {
    $contestants[] = $row;
}
// Get pending contestants count
$pending_count = $conn->query("SELECT COUNT(*) as cnt FROM contestants WHERE status = 'pending'")->fetch_assoc()['cnt'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Brgy Admin Dashboard</title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="#" data-page="events.php" class="nav-link active">📅 Events</a>
            <//a href="#" data-page="categories.php" class="nav-link"><//a>
            <a href="#" data-page="judges.php" class="nav-link">👨‍⚖️ Judges</a>
            <a href="#" data-page="contestants.php" class="nav-link">👑 Contestants
                <span class="pending-badge" style="position:absolute; top:220px; right: 100px; background:#dc3545; color:#fff; font-size:.60rem; font-weight:700; padding:2px 5px; border-radius:999px; line-height:1.2; display: <?= ((int)$pending_count) > 0 ? 'inline-flex' : 'none'; ?>;">
                    <?= (int)$pending_count ?>
                </span>
            </a>
            <a href="#" data-page="dashboard.php" class="nav-link">🏆 Results</a>
            <a href="logout.php" onclick="return confirm('Are you sure you want to logout?');" class="nav-link logout">🚪 Logout</a>
        </nav>
    </aside>
    <main class="main-content">
        <iframe id="content-frame" src="events.php" frameborder="0"></iframe>
    </main>
    <button class="mobile-menu-toggle" id="mobileToggle">☰</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('.nav-link:not(.logout)');
    const frame = document.getElementById('content-frame');
    const toggle = document.getElementById('mobileToggle');
    const sidebar = document.querySelector('.sidebar');

    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            frame.src = page;
            
            // Update active
            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // Close mobile menu
            sidebar.classList.remove('mobile-open');
        });
    });

    toggle.addEventListener('click', function() {
        sidebar.classList.toggle('mobile-open');
    });
});
</script>
</body>
</html>