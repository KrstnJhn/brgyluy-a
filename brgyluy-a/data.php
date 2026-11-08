<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Judges Database Status</title>
    <link rel="icon" type="image/png" href="assets/images/sk_logo.png">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
<?php
$conn = new mysqli('localhost', 'root', '', 'tabulation_system');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo '<div class="dashboard-container">';
echo '<h2 class="page-title">Judge Database</h2>';

$tables = $conn->query('SHOW TABLES LIKE "judges"');
if ($table = $tables->fetch_array()) {
    echo "<p>✅ Judges table exists.</p>";
    
    $judges = $conn->query('SELECT id, username, password, full_name FROM judges');
    echo "<h3>Current Judges:</h3>";
    if ($judges->num_rows == 0) {
        echo "<p>No judges in database.</p>";
    } else {
        echo '<div class="table-responsive">';
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Username</th>";
        echo "<th>Password</th>";
        echo "<th>Name</th>";
        echo "</tr>";
        while($row = $judges->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['password'], 0, 20)) . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
} else {
    echo '<p class="form-messages error">❌ judges table missing. Import <a href="tabualtion_system.sql" class="btn btn-primary">tabualtion_system.sql</a> first.</p>';
}

$conn->close();
echo '</div>';  
?>
</div>
</body>
</html>
