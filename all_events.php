<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'db.php';

if(isset($_POST['export_excel'])){
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="events.xls"');
    $result = $conn->query("SELECT * FROM events");
    echo "ID\tType\tSemester\tDepartment\tName\tStart\tEnd\tResource\tRemuneration\n";
    while($r=$result->fetch_assoc()) echo implode("\t",[$r['id'],$r['event_type'],$r['sem'],$r['department'],$r['event_name'],$r['event_start_date'],$r['event_end_date'],$r['resource_person'],$r['remuneration']])."\n";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 260px; position: fixed; left: 0; top: 0; height: 100vh; background: #1a1a2e; color: white; padding: 20px; }
        .sidebar a { color: #ccc; display: block; padding: 10px; margin: 5px 0; text-decoration: none; border-radius: 8px; }
        .sidebar a:hover { background: #667eea; color: white; }
        .content { margin-left: 280px; padding: 20px; }
        .card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table.dataTable thead th { background: #667eea; color: white; }
    </style>
</head>
<body>
<div class="sidebar">
    <h3>📋 EventHub</h3>
    <hr>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="add_event.php"><i class="fas fa-plus-circle"></i> Add Event</a>
    <a href="all_events.php" class="active"><i class="fas fa-calendar-alt"></i> All Events</a>
    <a href="resource_info.php"><i class="fas fa-users"></i> Resources</a>
    <a href="calendar.php"><i class="fas fa-calendar-week"></i> Calendar</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="content">
    <div class="card">
        <h3>All Events</h3>
        <form method="POST" class="mb-3"><button type="submit" name="export_excel" class="btn btn-success">Export to Excel</button></form>
        <table id="eventsTable" class="table table-bordered">
            <thead><tr><th>ID</th><th>Type</th><th>Semester</th><th>Department</th><th>Name</th><th>Start</th><th>End</th><th>Resource</th><th>Remuneration</th><th>Actions</th></tr></thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM events ORDER BY id DESC");
                while($row=$result->fetch_assoc()){
                    echo "<tr>";
                    echo "<td>{$row['id']}</td><td>{$row['event_type']}</td><td>{$row['sem']}</td><td>{$row['department']}</td><td>{$row['event_name']}</td>";
                    echo "<td>{$row['event_start_date']}</td><td>{$row['event_end_date']}</td><td>{$row['resource_person']}</td><td>{$row['remuneration']}</td>";
                    echo "<td><a href='edit_event.php?id={$row['id']}' class='btn btn-sm btn-warning'>Edit</a> <a href='delete_event.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Delete?\")'>Delete</a></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<script>$(document).ready(function(){$('#eventsTable').DataTable();});</script>
</body>
</html>