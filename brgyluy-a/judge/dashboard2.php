<?php
require_once '../includes/auth.php';
redirectIfNotJudge();
require_once '../includes/db.php';

$judge_id = $_SESSION['judge_id'];

// Get events for this judge
$event_stmt = $conn->prepare("SELECT DISTINCT e.id, e.name, e.event_date FROM events e 
                              JOIN categories c ON e.id = c.event_id 
                              JOIN judge_category jc ON c.id = jc.category_id 
                              WHERE jc.judge_id = ?
                              ORDER BY e.event_date DESC");
$event_stmt->bind_param('i', $judge_id);
$event_stmt->execute();
$events = $event_stmt->get_result();

// Get ALL categories for this judge, grouped by event
$stmt = $conn->prepare("SELECT c.id, c.name, c.is_locked, c.event_id, e.name AS event_name FROM categories c 
                        JOIN events e ON c.event_id = e.id
                        JOIN judge_category jc ON c.id = jc.category_id
                        WHERE jc.judge_id = ?
                        ORDER BY e.event_date DESC, c.sort_order");
$stmt->bind_param('i', $judge_id);
$stmt->execute();
$result = $stmt->get_result();
$categoriesByEvent = [];
while ($cat = $result->fetch_assoc()) {
    $categoriesByEvent[$cat['event_id']][] = $cat;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Judge Dashboard</title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        </xai:function_call name="edit_file"> 

        <xai:function_call name="edit_file">
        <parameter name="path">judge/dashboard2.php
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .judge-name {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .logo-container img {
            max-width: 200px;
            height: 100px;
            object-fit: cover;
            border-radius: 12px;
        }
        .judge-name {
            color: #fff;
            font-size: clamp(1.25rem, 4vw, 1.75rem);
            font-weight: 700;
            text-align: center;
        }
        .dashboard-container {
            background: white;
            padding: clamp(1.5rem, 6vw, 2.5rem);
            position: relative;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0);
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }
        h1 {
            color: #333;
            font-size: clamp(1.6rem, 6vw, 2.2rem);
        }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
            background-color: #ff4444;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 14px;
            transition: background 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .logout-btn:hover {
            background: #c0392b;
        }
        h2, h3, h4 {
            color: #555;
            margin-bottom: 1rem;
        }
        .event-selector {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 10px rgba(102,126,234,0.1);
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(clamp(160px, 45vw, 250px), 1fr));
            gap: clamp(10px, 4vw, 16px);
        }
        .event-btn {
            display: block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: clamp(12px, 5vw, 16px) clamp(14px, 6vw, 20px);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            font-size: clamp(14px, 5vw, 16px);
            cursor: pointer;
        }
        .event-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.4);
            border-color: rgba(255,255,255,0.3);
        }
        .event-btn .date {
            opacity: 0.85;
            font-size: 0.85em;
            display: block;
            margin-top: 2px;
        }
        .instructions {
            text-align: center;
            color: #666;
            padding: 30px;
            font-style: italic;
        }
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal {
            background: white;
            margin: 10%;
            padding: 0;
            border-radius: 12px;
            width: 80%;
            max-width: 500px;
            max-height: 80vh;
            position: relative;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .modal-header {
            padding: 25px 30px 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        .modal-title {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }
        .modal-close {
            position: absolute;
            right: 20px;
            top: 20px;
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .modal-close:hover {
            background: #f5f5f5;
            color: #666;
        }
        .modal-body {
            padding: 0 30px 30px;
            max-height: 400px;
            overflow-y: auto;
        }
        .modal-categories {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .modal-categories li {
            margin-bottom: 12px;
        }
        .modal-category-link {
            display: block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .modal-category-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.4);
        }
        @media (max-width: 768px) {
            .modal {
                width: 95%;
                margin: 5%;
            }
            .modal-header {
                padding: 20px;
            }
            .modal-body {
                padding: 0 20px 20px;
            }
        }
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .dashboard-container {
                margin: 10px;
                padding: 1.5rem;
            }
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
        .footer-wrapper {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 0.4rem 0;
            text-align: center;
            color: #333;
        }
        .footer {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .footer a {
                color: #ffbb00;
                text-decoration: none;
                font-weight: 600;
            }
            .footer a:hover {
                text-decoration: underline;
            }
    </style>
</head>
<header>
    <h1><a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?');"> [Logout]</a> </h1>
</header>
<body>
            <div class="logo-container">
                <img src="../assets/images/sk_logo.png" alt="SK Logo">
                <img src="../assets/images/luy-a_seal.png" alt="LUY-A Logo">
            </div>
            <div class="judge-name">
                Welcome, <?= htmlspecialchars($_SESSION['judge_name']) ?>
            </div>
    <div class="dashboard-container">
        <main>
            <h2></h2>

                <?php if ($events->num_rows > 0): ?>
                <div class="events-grid">
                    <?php $events->data_seek(0); while($event = $events->fetch_assoc()): ?>
                    <a href="#" onclick="showCategoriesModal(<?= $event['id'] ?>, '<?= addslashes(htmlspecialchars($event['name'])) ?>')" class="event-btn">
                        <?= htmlspecialchars($event['name']) ?>
                        <span class="date"><?= date('M d, Y', strtotime($event['event_date'])) ?></span>
                    </a>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p style="color: #888; text-align: center; padding: 20px;">No events assigned yet.</p>
                <?php endif; ?>
        </main>
    </div>
       
    <!-- Categories Modal -->
    <div id="categoriesModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalEventTitle" class="modal-title">Loading...</h3>
                <button class="modal-close" onclick="closeModal()" title="Close"> </button>
            </div>
            <div class="modal-body">
                <ul id="modalCategoriesList" class="modal-categories">
                    <!-- Categories populated by JS -->
                </ul>
            </div>
        </div>
    </div>
    
    <div class="footer-wrapper">
        <div class="footer">
            &copy; <?= date('Y') ?> Brgy. Luy-A SKystem. Developed by <a href="https://www.facebook.com/krstnjhngngra623/" target="_blank">Krstn Jhn Gngr</a>
        </div>
    </div>

    <script>
        var categoriesByEvent = <?= json_encode($categoriesByEvent, JSON_HEX_QUOT | JSON_HEX_APOS) ?>;

        function showCategoriesModal(eventId, eventName) {
            var modal = document.getElementById('categoriesModal');
            var title = document.getElementById('modalEventTitle');
            var list = document.getElementById('modalCategoriesList');
            var catList = categoriesByEvent[eventId] || [];
            
            title.textContent = eventName + ' Categories';
            
            if (catList.length === 0) {
                list.innerHTML = '<li style="padding: 30px; text-align: center; color: #888; font-style: italic;">No categories for this event.</li>';
            } else {
                var html = '';
                catList.forEach(function(cat) {
                    var lockIcon = cat.is_locked ? '🔒' : '🔓';
                    var titleText = cat.is_locked ? 'Locked - View Only' : 'Unlocked - Can Edit';
                    html += '<li><a href="score.php?category_id=' + cat.id + '" class="modal-category-link" title="' + titleText + '">' + 
                            cat.name + ' ' + lockIcon + '</a></li>';
                });
                list.innerHTML = html;
            }
            
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('categoriesModal').classList.remove('active');
        }

        // Close on overlay click
        document.getElementById('categoriesModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>
