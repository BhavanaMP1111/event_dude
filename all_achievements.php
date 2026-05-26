<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'db.php';

$is_super_admin = ($_SESSION['admin'] == 'admin');

if($is_super_admin){
    $dept_where = "";
} else {
    $dept = $conn->real_escape_string($_SESSION['department']);
    $dept_where = " WHERE department = '$dept' ";
}

if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $folder = "uploads/achievements/$id/";
    if(is_dir($folder)){
        array_map('unlink', glob("$folder/*.*"));
        rmdir($folder);
    }
    $conn->query("DELETE FROM achievements WHERE id = $id");
    header("Location: all_achievements.php");
    exit;
}

$sql = "SELECT * FROM achievements $dept_where ORDER BY id DESC";
$result = $conn->query($sql);
if(!$result){
    die("SQL Error: " . $conn->error);
}

$user_dept = $_SESSION['department'] ?? 'CSE';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Achievements | EventHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .sidebar { width: 280px; position: fixed; left: 0; top: 0; height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); z-index: 1000; transition: all 0.3s; box-shadow: 10px 0 30px rgba(0,0,0,0.1); overflow-y: auto; }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h3 { color: white; font-weight: 700; font-size: 1.5rem; margin: 0; }
        .sidebar-header p { color: rgba(255,255,255,0.6); font-size: 0.75rem; margin: 5px 0 0; }
        .sidebar-menu { padding: 0 15px; }
        .sidebar-menu .nav-link { color: rgba(255,255,255,0.7); padding: 12px 16px; border-radius: 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; margin-bottom: 8px; transition: all 0.3s; }
        .sidebar-menu .nav-link:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(5px); }
        .sidebar-menu .nav-link.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .main-content { margin-left: 280px; padding: 30px; }
        .top-bar { background: white; border-radius: 20px; padding: 15px 25px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: #1a1a2e; margin: 0; }
        .premium-card { background: white; border-radius: 20px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 30px; }
        .premium-card .card-header { background: white; border-bottom: 2px solid #f0f0f0; padding: 20px 25px; font-weight: 700; font-size: 1.2rem; }
        .premium-card .card-body { padding: 25px; }
        .btn-outline-premium { border: 2px solid #667eea; background: transparent; color: #667eea; padding: 10px 25px; border-radius: 12px; font-weight: 600; transition: all 0.3s; }
        .btn-outline-premium:hover { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: transparent; }
        .btn-premium { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 12px 30px; border-radius: 12px; font-weight: 600; transition: all 0.3s; }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); color: white; }
        table.dataTable thead th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; border: none; padding: 15px; }
        .btn-zip { background: #6c757d; color: white; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-zip:hover { background: #198754; color: white; }
        .mobile-menu-btn { display: none; position: fixed; top: 20px; left: 20px; z-index: 1001; background: #667eea; border: none; color: white; padding: 10px 15px; border-radius: 10px; cursor: pointer; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 15px; }
            .mobile-menu-btn { display: block; }
            .top-bar { margin-top: 50px; }
        }
    </style>
</head>
<body>
<button class="mobile-menu-btn" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i> Menu</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header"><h3>📋 EventHub</h3><p>Event Management System</p></div>
    <div class="sidebar-menu">
        <a href="admin_dashboard.php?tab=dashboard" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
        <a href="admin_dashboard.php?tab=addEvent" class="nav-link"><i class="fas fa-plus-circle"></i><span>Add Event</span></a>
        <a href="admin_dashboard.php?tab=allEvents" class="nav-link"><i class="fas fa-calendar-alt"></i><span>All Events</span></a>
        <a href="add_achievement.php" class="nav-link"><i class="fas fa-plus-circle"></i><span>Add Achievement</span></a>
        <a href="all_achievements.php" class="nav-link active"><i class="fas fa-trophy"></i><span>All Achievements</span></a>
        <a href="resource_info.php" class="nav-link"><i class="fas fa-users"></i><span>Resource Information</span></a>
        <a href="calendar.php" class="nav-link"><i class="fas fa-calendar-week"></i><span>Calendar</span></a>
        <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i><span>Settings</span></a>
        <a href="logout.php" class="nav-link" style="margin-top:20px;"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h4 class="page-title">All Achievements</h4>
        <div><span class="badge bg-primary rounded-pill px-3 py-2"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin'] ?? 'Admin') ?> (<?= htmlspecialchars($user_dept) ?>)</span></div>
    </div>

    <div class="premium-card">
        <div class="card-header">
            <i class="fas fa-trophy me-2"></i> Achievements List
            <div class="float-end">
                <button class="btn btn-outline-premium btn-sm me-2" data-bs-toggle="modal" data-bs-target="#importAchievementModal"><i class="fas fa-file-import"></i> Import</button>
                <a href="export_achievements.php" class="btn btn-outline-premium btn-sm me-2"><i class="fas fa-file-excel"></i> Export</a>
                <a href="add_achievement.php" class="btn btn-premium btn-sm">+ Add New</a>
            </div>
        </div>
        <div class="card-body">
            <?php if($result->num_rows == 0): ?>
                <div class="alert alert-info text-center">No achievements found. Click "Add New" to create one.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="achievementsTable" class="table table-bordered table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th><th>Student Name</th><th>Event Name</th><th>Department</th>
                            <th>Semester</th><th>Dates</th><th>Coordinator</th><th>Photos</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <?php
                            $gallery_path = "uploads/achievements/{$row['id']}/gallery/";
                            $photos_html = '<span class="text-muted">—</span>';
                            if(is_dir($gallery_path)){
                                $images = glob($gallery_path . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
                                if(!empty($images)){
                                    $photos_html = '<a href="download_achievement_zip.php?id=' . $row['id'] . '" class="btn-zip"><i class="fas fa-file-archive"></i> Download ZIP</a>';
                                }
                            }
                        ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= htmlspecialchars($row['event_name']) ?></td>
                            <td><?= htmlspecialchars($row['department']) ?></td>
                            <td><?= htmlspecialchars($row['semester']) ?></td>
                            <td><?= $row['start_date'] ?> → <?= $row['end_date'] ?></td>
                            <td><?= htmlspecialchars($row['coordinator']) ?></td>
                            <td><?= $photos_html ?></td>
                            <td>
                                <a href="edit_achievement.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this achievement?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importAchievementModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                <h5 class="modal-title"><i class="fas fa-file-excel me-2"></i> Import Achievements from Excel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="import_achievements.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Excel File (.xlsx or .xls)</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                        <div class="form-text">Format: Student Name, Event Name, Semester, Coordinator, Description, Start Date (DD-MM-YYYY), End Date (DD-MM-YYYY), Department</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="import_excel" class="btn-premium">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleMobileMenu(){ document.getElementById('sidebar').classList.toggle('mobile-open'); }
$(document).ready(function(){
    $('#achievementsTable').DataTable({ pageLength:10, order:[[0,'desc']], language: { search: "Search:" } });
});
document.addEventListener('click',function(e){
    if(window.innerWidth<=768){
        var sidebar=document.getElementById('sidebar');
        var btn=document.querySelector('.mobile-menu-btn');
        if(sidebar.classList.contains('mobile-open') && !sidebar.contains(e.target) && !btn.contains(e.target))
            sidebar.classList.remove('mobile-open');
    }
});
</script>
</body>
</html>