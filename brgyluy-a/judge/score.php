<?php
require_once '../includes/auth.php';
redirectIfNotJudge();
require_once '../includes/db.php';

$judge_id = $_SESSION['judge_id'];
$category_id = $_GET['category_id'] ?? 0;

// Verify judge is assigned to this category
$check = $conn->prepare("SELECT * FROM judge_category WHERE judge_id = ? AND category_id = ?");
$check->bind_param('ii', $judge_id, $category_id);
$check->execute();
if ($check->get_result()->num_rows == 0) {
    die("You are not authorized to score this category.");
}

// Get category info
$catInfo = $conn->query("SELECT c.*, e.name AS event_name FROM categories c JOIN events e ON c.event_id = e.id WHERE c.id = $category_id")->fetch_assoc();

$is_locked = isset($catInfo['is_locked']) ? (int)$catInfo['is_locked'] : 0;
$input_disabled = $is_locked ? 'readonly disabled' : '';
$save_disabled = $is_locked ? 'disabled' : '';
$save_text = $is_locked ? 'View Only (Scoring Locked)' : 'Save Scores';

// Get criteria
$criteria = $conn->prepare("SELECT * FROM criteria WHERE category_id = ? ORDER BY sort_order");
$criteria->bind_param('i', $category_id);
$criteria->execute();
$criteria = $criteria->get_result()->fetch_all(MYSQLI_ASSOC);

// Get contestants in this category
$contestants = $conn->prepare("SELECT c.id, c.name, c.profile_image FROM contestants c JOIN contestant_category cc ON c.id = cc.contestant_id WHERE cc.category_id = ? AND c.status = 'approved' ORDER BY c.name");
$contestants->bind_param('i', $category_id);
$contestants->execute();
$contestants = $contestants->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['score'] as $contestant_id => $critScores) {
        foreach ($critScores as $criterion_id => $score) {
            // Validate score doesn't exceed max
            $max = $conn->query("SELECT max_score FROM criteria WHERE id = $criterion_id")->fetch_assoc()['max_score'];
            if ($score > $max) {
                $error = "Score for criterion ID $criterion_id cannot exceed $max";
                break 2;
            }
            // Insert or update
            $stmt = $conn->prepare("INSERT INTO scores (judge_id, contestant_id, criterion_id, score) 
                                    VALUES (?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE score = VALUES(score)");
            $stmt->bind_param('iiid', $judge_id, $contestant_id, $criterion_id, $score);
            $stmt->execute();
        }
    }
    if (!isset($error)) {
        $success = "Scores saved successfully!";
    }
}

// Fetch existing scores for this judge
$existing = [];
$res = $conn->prepare("SELECT contestant_id, criterion_id, score FROM scores WHERE judge_id = ?");
$res->bind_param('i', $judge_id);
$res->execute();
$res = $res->get_result();
while ($row = $res->fetch_assoc()) {
    $existing[$row['contestant_id']][$row['criterion_id']] = $row['score'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title> <?= htmlspecialchars($catInfo['event_name'] . ' - ' . $catInfo['name']) ?></title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .score-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        h2 {
            color: #000000;
            font-size: 1.6rem;
        }
        .back-btn {
            background: #f8a408;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
        }
        .back-btn:hover {
            background: #ff6600;
        }
        .success {
            background: #cdffd4;
            color: #155724;
            padding: 12px 20px;
            border-radius: 6px;
            border: 1px solid #c3e6cb;
            margin-bottom: 1rem;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 6px;
            border: 1px solid #f5c6cb;
            margin-bottom: 1rem;
        }
        .table-wrapper {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .score-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 800px;
        }
        .score-table th {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
            padding: 16px 12px;
            text-align: left;
            font-weight: 650;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .score-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #eee;
        }
        .contestant-name {
            font-weight: 600;
            color: #000000;
            background: #f8f9fa;
        }
        .score-input {
            width: 90px;
            padding: 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.2s;
        }
        .score-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .save-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .save-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        .save-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #7f92a8;
        }
        /* Photo modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90vw;
            max-height: 90vh;
            width: auto;
            height: auto;
            max-width: 700px;
            object-fit: contain;
            border-radius: 8px;
        }
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #ddd;
        }
        .caption {
            margin: auto;
            padding: 10px;
            width: 90%;
            text-align: center;
            color: #ccc;
            font-size: 18px;
        }
        .clickable {
            transition: transform 0.2s;
        }
        .clickable:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .score-container {
                margin: 10px;
                padding: 1.5rem;
                max-height: 95vh;
            }
            header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            h2 {
                text-align: center;
            }
            .score-table {
                font-size: 14px;
            }
            .score-input {
                width: 70px;
            }
        }
@media (max-width: 480px) {
            .score-container {
                padding: 1rem;
            }
            .score-table th,
            .score-table td {
                padding: 10px 8px;
            }
        }

        .footer-wrapper {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            text-align: center;
            color: white;
            z-index: 100;
        }
        .footer {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
    </style>
</head>

<body>
    <div class="score-container">
        <header>
            <h2> <?= htmlspecialchars($catInfo['name']) ?></h2>
            <a href="dashboard2.php" class="back-btn">< Back</a>
        </header>
        
        <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>

        <form method="post">
            <div class="table-wrapper">
                <table class="score-table">
                    <thead>
                        <tr>
                                <th>Photo</th>
                                <th>Contestant Name</th>
                            <?php foreach($criteria as $c): ?>
                                <th><?= htmlspecialchars($c['name']) ?><br><small> <?= $c['max_score'] ?> %</small></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $contestants->data_seek(0); while($cont = $contestants->fetch_assoc()): ?>
                            <tr>
<td>
  <?php if ($cont['profile_image']): ?>
    <img src="../assets/images/contestants/<?= htmlspecialchars($cont['profile_image']) ?>" 
         alt="<?= htmlspecialchars($cont['name']) ?>" 
         class="contestant-photo clickable" 
         style="width:50px;height:50px;object-fit:cover;border-radius:50px;cursor:pointer;border:1px solid #ddd;"
         data-fullsrc="../assets/images/contestants/<?= htmlspecialchars($cont['profile_image']) ?>"
         data-name="<?= htmlspecialchars($cont['name']) ?>">
  <?php else: ?>
    <div style="width:50px;height:50px;background:#eee;border-radius:50px;display:flex;align-items:center;justify-content:center;color:#999;font-size:12px;">No Image</div>
  <?php endif; ?>
</td>
<td class="contestant-name"><?= htmlspecialchars($cont['name']) ?></td>
                                <?php foreach($criteria as $c): 
                                    $current = $existing[$cont['id']][$c['id']] ?? '';
                                ?>
                                    <td>
                                        <input type="number" 
                                               name="score[<?= $cont['id'] ?>][<?= $c['id'] ?>]" 
                                               value="<?= htmlspecialchars($current) ?>" 
                                               step="0.01" min="0" max="<?= $c['max_score'] ?>" 
                                               class="score-input"
                                               <?= $input_disabled ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="save-btn" <?= $save_disabled ?>> <?= $save_text ?></button>
        </form>

        <!-- Photo Modal -->
        <div id="photoModal" class="modal">
            <span class="close">&times;</span>
            <img class="modal-content" id="modalImg">
            <div id="caption" class="caption"></div>
        </div>
    </div>

    <script>
    // Modal functionality
    const modal = document.getElementById('photoModal');
    const modalImg = document.getElementById('modalImg');
    const captionText = document.getElementById('caption');
    const closeBtn = document.querySelector('.close');

    // Open modal on image click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('clickable')) {
            modal.style.display = 'block';
            modalImg.src = e.target.dataset.fullsrc;
            captionText.innerHTML = e.target.dataset.name;
        }
    });

    // Close modal
    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>
    
</html>
</div>
