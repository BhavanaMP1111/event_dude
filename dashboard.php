<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'db.php';

$total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$upcoming_events = $conn->query("SELECT COUNT(*) as count FROM events WHERE event_start_date > CURDATE() AND event_start_date != '0000-00-00'")->fetch_assoc()['count'];
$total_expenses = $conn->query("SELECT SUM(remuneration) as total FROM events")->fetch_assoc()['total'] ?? 0;
$departments = $conn->query("SELECT COUNT(DISTINCT department) as count FROM events WHERE department IS NOT NULL AND department != ''")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 260px; position: fixed; left: 0; top: 0; height: 100vh; background: #1a1a2e; color: white; padding: 20px; }
        .sidebar a { color: #ccc; display: block; padding: 10px; margin: 5px 0; text-decoration: none; border-radius: 8px; }
        .sidebar a:hover { background: #667eea; color: white; }
        .sidebar a.active { background: #667eea; color: white; }
        .content { margin-left: 280px; padding: 20px; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-value { font-size: 2rem; font-weight: bold; }
    </style>
</head>
<body>
<div class="sidebar">
    <h3>📋 EventHub</h3>
    <hr>
    <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="add_event.php"><i class="fas fa-plus-circle"></i> Add Event</a>
    <a href="all_events.php"><i class="fas fa-calendar-alt"></i> All Events</a>
    <a href="resource_info.php"><i class="fas fa-users"></i> Resources</a>
    <a href="calendar.php"><i class="fas fa-calendar-week"></i> Calendar</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="content">
    <div class="row">
        <div class="col-md-3"><div class="stat-card"><div class="stat-value"><?php echo $total_events; ?></div><div>Total Events</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value"><?php echo $upcoming_events; ?></div><div>Upcoming</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value"><?php echo $departments; ?></div><div>Departments</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-value">₹<?php echo number_format($total_expenses, 2); ?></div><div>Expenses</div></div></div>
    </div>
    <div class="card"><div class="card-header">Recent Events</div><div class="card-body"><?php
    $recent = $conn->query("SELECT * FROM events ORDER BY created_at DESC LIMIT 5");
    echo "<table class='table'><tr><th>Event</th><th>Type</th><th>Date</th></tr>";
    while($r = $recent->fetch_assoc()) {
        echo "<tr><td>{$r['event_name']}</td><td>{$r['event_type']}</td><td>{$r['event_start_date']}</td></tr>";
    }
    echo "</table>";
    ?></div></div>
</div>
</body>
</html>