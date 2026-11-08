<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Current events are shared in the database; the session only remembers the
// preferred event when the results page is opened without an explicit choice.
$current_events_result = $conn->query("SELECT id, name, event_date FROM events WHERE is_current = 1 ORDER BY event_date DESC, id DESC");
$current_events = $current_events_result->fetch_all(MYSQLI_ASSOC);
$current_event_ids = array_map('intval', array_column($current_events, 'id'));
$session_event_id = (int)($_SESSION['current_event_id'] ?? 0);
$requested_event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if (count($current_events) > 0) {
    $events = $current_events;
    if ($requested_event_id > 0 && in_array($requested_event_id, $current_event_ids, true)) {
        $selected_event_id = $requested_event_id;
    } elseif (in_array($session_event_id, $current_event_ids, true)) {
        $selected_event_id = $session_event_id;
    } else {
        $selected_event_id = (int)$current_events[0]['id'];
    }
} else {
    $all_events_result = $conn->query("SELECT DISTINCT id, name, event_date FROM events ORDER BY event_date DESC");
    $events = $all_events_result->fetch_all(MYSQLI_ASSOC);
    $selected_event_id = $requested_event_id;
}


// Get categories for selected event (all if no selection)
$categories_query = "SELECT c.*, e.name AS event_name FROM categories c JOIN events e ON c.event_id = e.id";
$params = [];
$types = '';
if ($selected_event_id > 0) {
    $categories_query .= " WHERE c.event_id = ?";
    $params = [$selected_event_id];
    $types = 'i';
}
$categories_query .= " ORDER BY e.event_date DESC, c.sort_order";


$stmt = $conn->prepare($categories_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$categories = $stmt->get_result();
$category_data = [];
while ($cat = $categories->fetch_assoc()) {
    // Get assigned judges for this category
    $judges_stmt = $conn->prepare("
        SELECT j.id, j.full_name 
        FROM judges j 
        JOIN judge_category jc ON j.id = jc.judge_id 
        WHERE jc.category_id = ?
        ORDER BY j.full_name
    ");
    $judges_stmt->bind_param('i', $cat['id']);
    $judges_stmt->execute();
    $assigned_judges = $judges_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get approved contestants only
    $cont_stmt = $conn->prepare("
        SELECT c.id, c.name, c.profile_image 
        FROM contestants c 
        JOIN contestant_category cc ON c.id = cc.contestant_id 
        WHERE cc.category_id = ? AND c.status = 'approved'
        ORDER BY c.name
    ");
    $cont_stmt->bind_param('i', $cat['id']);
    $cont_stmt->execute();
    $contestants = $cont_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $criteria = getCriteria($cat['id']);
    
    $contestants_data = [];
    foreach ($contestants as $cont) {
        $criterion_avgs = [];
        
        // Criterion averages (across judges)
        foreach ($criteria as $cri) {
            $avg_stmt = $conn->prepare("
                SELECT AVG(score) as avg_score 
                FROM scores 
                WHERE contestant_id = ? AND criterion_id = ?
            ");
            $avg_stmt->bind_param('ii', $cont['id'], $cri['id']);
            $avg_stmt->execute();
            $avg_res = $avg_stmt->get_result()->fetch_assoc();
            $criterion_avgs[$cri['id']] = $avg_res['avg_score'] ? number_format($avg_res['avg_score'], 2) : '0.00';
        }
        
        // Sum of all criterion averages
        $sum_criterion_avgs = 0;
        foreach ($criterion_avgs as $avg) {
            $sum_criterion_avgs += floatval($avg);
        }
        $final_score = number_format($sum_criterion_avgs, 2);
        
        $contestants_data[] = [
            'id' => $cont['id'],
            'name' => $cont['name'],
            'profile_image' => $cont['profile_image'],
            'criterion_avgs' => $criterion_avgs,
            'final_score' => $final_score
        ];
    }
    
    // Sort contestants by category_avg descending
    usort($contestants_data, function($a, $b) {
        return floatval($b['final_score']) <=> floatval($a['final_score']);
    });
    
    $category_data[$cat['id']] = [
        'info' => $cat,
        'criteria' => $criteria,
        'assigned_judges' => $assigned_judges,
        'contestants' => $contestants_data
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Tabulation Results</title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <!-- PDF Generation CDNs -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="results_pdf.js"></script>
<style>
        .contestant-img { width: 50px; height: 50px; object-fit: cover; border-radius: 50px; }
        .tabs { display: flex; border-bottom: 2px solid #ddd; margin-bottom: 1rem; }
        .tab-btn { padding: 12px 24px; background: none; border: none; cursor: pointer; font-size: 16px; }
        .tab-btn.active { background: #007bff; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .judge-col { background: #f8f9fa; font-weight: bold; }
        table { font-size: 0.9em; }
        th, td { padding: 8px; text-align: center; }
        .pdf-btn {
            display: inline-block; background: #28a745; color: white; padding: 8px 16px;
            margin-left: 10px; border-radius: 4px; text-decoration: none; font-weight: bold;
            transition: background 0.2s;
        }

        .pdf-btn:hover { background: #218838; }
        .event-selector {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 10px rgba(102,126,234,0.1);
        }
        .event-selector h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.1rem;
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        .event-btn {
            display: block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            font-size: 14px;
        }
        .event-btn:hover, .event-btn.selected {
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
        .category-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }

        /* Modal styles */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.5); 
        }
        .modal-content { 
            background-color: white; 
            margin: 5% auto; 
            padding: 0; 
            border-radius: 10px; 
            width: 90%; 
            max-width: 1200px; 
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            padding: 10px 20px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close-modal:hover { color: #000; }
        .categories-btn {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }
        .categories-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.4);
        }

        .print-page-header {
            display: none;
        }
        .print-page-footer {
            display: none;
        }

        @media print {
            @page {
                size: landscape;
                margin: 10mm 10mm 20mm;
            }
            body { background: white; }
            .container { max-width: none !important; padding: 0 !important; }
            .page-header, .event-selector, .tabs, .pdf-btn, .category-header button:not(.print-btn), .modal, .print-btn, .container > p {
                display: none !important;
            }
            .tab-content { display: none !important; }
            .tab-content.active { display: block !important; }
            .print-page-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                margin-bottom: 20px;
                text-align: center;
            }
            .print-page-footer {
                display: block;
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                padding-top: 6px;
                border-top: 1px solid #333;
                background: white;
                text-align: center;
                font-weight: bold;
            }
            .print-page-header img {
                width: 90px;
                height: 90px;
                object-fit: contain;
            }
            .print-page-heading {
                flex: 1;
                line-height: 1.5;
                margin-top: 15px;
            }
            .print-page-heading2 {
                flex: 1;
                font-weight: bold;
                line-height: 1.5;
            }
            .print-page-heading div:last-child {
                font-size: 1.2em;
                margin-top: 4px;
            }
            table { width: 100%; font-size: 0.8em; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="container" style="max-width: 1800px; margin: 0 auto; padding: 5px;">
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <h1>Results</h1>
    <a href="results.php" class="btn btn-secondary">Refresh</a>
</div>   
    <!-- Event Selector -->
    <?php if (count($current_events) >= 2): ?>
    <div class="event-selector">
        <div class="events-grid">
            <?php foreach ($events as $event): ?>
            <a href="?event_id=<?= $event['id'] ?>" class="event-btn <?= $selected_event_id == $event['id'] ? 'selected' : '' ?>">
                <?= htmlspecialchars($event['name']) ?>
                <span class="date"><?= date('M d, Y - D', strtotime($event['event_date'])) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (empty($category_data)): ?>
        <p class="form-messages">No categories or data available.</p>
    <?php else: ?>
        <!-- Categories Popup Button -->
 
        <div class="tabs">
            <?php foreach ($category_data as $cat_id => $data): ?>
                <button class="tab-btn <?= $cat_id == array_key_first($category_data) ? 'active' : '' ?>" 
                        onclick="openTab('<?= $cat_id ?>')">
                    <?= htmlspecialchars($data['info']['name']) ?>
                    
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($category_data as $cat_id => $data): ?>
                <div id="tab-<?= $cat_id ?>" class="tab-content <?= $cat_id == array_key_first($category_data) ? 'active' : '' ?>">
                <div class="print-page-header">
                    <img src="../assets/images/sk_logo.png" alt="SK Logo">
                    <div class="print-page-heading">
                        <div>REPUBLIC OF THE PHILIPPINES</div>
                        <div>MUNICIPALITY OF TUKURAN</div>
                        <div>BARANGAY LUY-A</div>
                        <div class="print-page-heading2">
                            <div><?= htmlspecialchars($data['info']['name']) ?> - RESULT</div>
                        </div>
                    </div>
                    <img src="../assets/images/luy-a_seal.png" alt="Luy-A Seal">
                </div>
               
                <?php if (empty($data['contestants'])): ?>
                    <p>No contestants or scores for this category.</p>
                <?php else: ?>
                    
                    <table border="1" cellpadding="4">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Image</th>
                                <th>Name</th>
                                <?php foreach ($data['criteria'] as $cri): ?>
                                    <th><?= htmlspecialchars($cri['name']) ?><br><small>(<?= $cri['max_score'] ?>%)</small></th>
                                <?php endforeach; ?>
                                <th>Final Score</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($data['contestants'] as $index => $cont_data): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <?php if (!empty($cont_data['profile_image'])): ?>
                                            <img src="../assets/images/contestants/<?= htmlspecialchars($cont_data['profile_image']) ?>" 
                                                 alt="Profile" class="contestant-img">
                                        <?php else: ?>
                                            <div style="width:50px;height:50px;background:#eee;border-radius:50px;display:flex;align-items:center;justify-content:center;color:#999;font-size:12px;">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($cont_data['name']) ?></td>
                                    <?php foreach ($data['criteria'] as $cri): ?>
                                        <td><?= $cont_data['criterion_avgs'][$cri['id']] ?? '0.00' ?></td>
                                    <?php endforeach; ?>
                                    <td><strong><?= $cont_data['final_score'] ?? '0.00' ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>
                <div class="print-page-footer">
                    <?= implode(', ', array_map(fn($judge) => htmlspecialchars($judge['full_name']), $data['assigned_judges'])) ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Categories Modal -->
        <div id="categoriesModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeCategoriesModal()">&times;</span>
                <h2 style="padding: 20px 20px 0; margin: 0;">Categories</h2>
                
                <div class="tabs" style="padding: 0 20px;">
                    <?php foreach ($category_data as $cat_id => $data): ?>
                        <button class="tab-btn <?= $cat_id == array_key_first($category_data) ? 'active' : '' ?>" 
                                onclick="openTab('<?= $cat_id ?>')">
                            <?= htmlspecialchars($data['info']['name']) ?>
                           
                        </button>
                    <?php endforeach; ?>
                </div>

                <div style="padding: 0 20px 20px;">
                    <?php foreach ($category_data as $cat_id => $data): ?>
                    <div id="modal-tab-<?= $cat_id ?>" class="tab-content <?= $cat_id == array_key_first($category_data) ? 'active' : '' ?>">
                        <?php if (empty($data['contestants'])): ?>
                            <p>No contestants or scores for this category.</p>
                        <?php else: ?>
                            <div class="category-header">
                                <div style="margin-bottom: 1rem;">
                                    <strong>Judges (<?= count($data['assigned_judges']) ?>):</strong>
                                    <?= implode(', ', array_map(fn($j) => htmlspecialchars($j['full_name']), $data['assigned_judges'])) ?>
                                </div>
                            </div>
                            
                            <table border="1" cellpadding="4">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Image</th>
                                        <th>Contestant Name</th>
                                        <?php foreach ($data['criteria'] as $cri): ?>
                                            <th><?= htmlspecialchars($cri['name']) ?><br><small>(<?= $cri['max_score'] ?>%)</small></th>
                                        <?php endforeach; ?>
                                        <th>Final Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['contestants'] as $index => $cont_data): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <?php if (!empty($cont_data['profile_image'])): ?>
                                                    <img src="../assets/images/contestants/<?= htmlspecialchars($cont_data['profile_image']) ?>" 
                                                         alt="Profile" class="contestant-img">
                                                <?php else: ?>
                                                     <div style="width:60px;height:60px;background:#eee;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#999;">No Image</div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($cont_data['name']) ?></td>
                                            <?php foreach ($data['criteria'] as $cri): ?>
                                                <td><?= $cont_data['criterion_avgs'][$cri['id']] ?? '0.00' ?></td>
                                            <?php endforeach; ?>
                                            <td><strong><?= $cont_data['final_score'] ?? '0.00' ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <p style="margin-top: 4rem;"><a href="dashboard.php"><?= implode(', ', array_map(fn($j) => htmlspecialchars($j['full_name']), $data['assigned_judges'])) ?></a></p>

    <div class="category-header">
        <div style="margin-bottom: 1rem;">
            <//strong> </?= count($data['assigned_judges']) ?> <//strong>
            
        </div>
        <div style="text-align:right; margin-bottom:1rem;">
            <button type="button" class="btn btn-primary print-btn" onclick="window.print()">PRINT</button>
        </div>
    </div>
    <script>
    // Modal functions
    function openCategoriesModal() {
        document.getElementById('categoriesModal').style.display = 'block';
    }

    function closeCategoriesModal() {
        document.getElementById('categoriesModal').style.display = 'none';
    }

    function openTab(catId) {
        // Hide all tabs
        const tabs = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        // Deactivate all buttons
        const btns = document.querySelectorAll('.tab-btn');
        btns.forEach(btn => btn.classList.remove('active'));
        
        // Show selected main tab
        const mainTab = document.getElementById('tab-' + catId);
        if (mainTab) mainTab.classList.add('active');
        
        // Show selected modal tab
        const modalTab = document.getElementById('modal-tab-' + catId);
        if (modalTab) modalTab.classList.add('active');
        
        if (event && event.target) {
            event.target.classList.add('active');
        }
    }

    // Close modal on overlay click
    window.onclick = function(event) {
        const modal = document.getElementById('categoriesModal');
        if (event.target === modal) {
            closeCategoriesModal();
        }
    }

    // Close on ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeCategoriesModal();
        }
    });
    </script>
</body>
</html>
