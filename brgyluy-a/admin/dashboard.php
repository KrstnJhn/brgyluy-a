<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get contestant statistics by category
$stats_query = "
    SELECT 
        c.id, c.name as category_name, c.description,
        e.name as event_name, e.event_date,
        COUNT(cc.contestant_id) as contestant_count
    FROM categories c
    LEFT JOIN contestant_category cc ON c.id = cc.category_id
    JOIN events e ON c.event_id = e.id
    GROUP BY c.id, e.id
    ORDER BY e.event_date DESC, c.sort_order
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_all(MYSQLI_ASSOC) : [];

// Overall totals
$total_events = $conn->query("SELECT COUNT(*) as cnt FROM events")->fetch_assoc()['cnt'];
$total_categories = $conn->query("SELECT COUNT(*) as cnt FROM categories")->fetch_assoc()['cnt'];
$total_contestants = $conn->query("SELECT COUNT(*) as cnt FROM contestants WHERE status = 'approved'")->fetch_assoc()['cnt'];
$pending_count = $conn->query("SELECT COUNT(*) as cnt FROM contestants WHERE status = 'pending'")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Tabulation Statistics</title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        .stat-label {
            font-size: 0.95rem;
            opacity: 0.95;
            margin: 0;
        }
        .category-stats {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        .category-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1rem;
        }
        .category-item {
            background: white;
            padding: 1.2rem;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        .category-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
        }
        .contestant-count {
            background: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .event-name {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }
        @media (max-width: 768px) {
            .stats-grid, .category-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 1400px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>📊 Dashboard</h1>
            <a href="results.php" class="btn btn-secondary" style="padding:.7rem 1rem; font-size:1.2rem;">Result</a>
        </div>

        <!-- Overall Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_events ?></div>
                <p class="stat-label">Total Events</p>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_categories ?></div>
                <p class="stat-label">Total Categories</p>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_contestants ?></div>
                <p class="stat-label">Total Contestants</p>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= array_sum(array_column($stats, 'contestant_count')) ?></div>
                <p class="stat-label">Active in Categories</p>
            </div>
        </div>

        <!-- Category Stats -->
        <div class="category-stats">
            <h2>👑 Contestants by Category <span style="font-size: 0.9rem; color: #64748b;">(<?= count($stats) ?> categories)</span></h2>
            <?php if (empty($stats)): ?>
                <div class="empty-state">
                    <h3>No contestant statistics yet</h3>
                    <p>Add events, categories, and contestants to see statistics here.</p>
                    <div style="margin-top: 1.5rem;">
                        <a href="events.php" style="background: #667eea; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; margin-right: 1rem;">📅 Setup Events</a>
                        <a href="categories.php" style="background: #10b981; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px;">🏷️ Add Categories</a>
                        <a href="contestants.php" style="background: #f59e0b; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px;">👑 Add Contestants</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="category-list">
                    <?php foreach ($stats as $stat): ?>
                    <div class="category-item">
                        <div class="category-header">
                            <div>
                                <div class="event-name"><?= htmlspecialchars($stat['event_name']) ?></div>
                                <div class="category-title"><?= htmlspecialchars($stat['category_name']) ?></div>
                            </div>
                            <div class="contestant-count"><?= $stat['contestant_count'] ?> contestant<?= $stat['contestant_count'] != 1 ? 's' : '' ?></div>
                        </div>
                        <?php if ($stat['description']): ?>
                            <div style="color: #64748b; font-size: 0.9rem; margin-top: 0.5rem;"><?= htmlspecialchars($stat['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
