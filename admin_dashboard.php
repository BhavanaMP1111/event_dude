<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'db.php';

// Helper functions (unchanged)
function ensure_dir($dir) { if (!is_dir($dir)) mkdir($dir, 0777, true); }

function handle_zip_upload($file, $event_id) {
    if(empty($file['name'])) return false;
    $upload_dir = "uploads/events/$event_id/";
    ensure_dir($upload_dir);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if($ext != 'zip') return false;
    $original_zip = $upload_dir . "original.zip";
    if(move_uploaded_file($file['tmp_name'], $original_zip)){
        $zip = new ZipArchive;
        if($zip->open($original_zip) === TRUE){
            $extract_to = $upload_dir . "gallery/";
            ensure_dir($extract_to);
            $zip->extractTo($extract_to);
            $zip->close();
            return true;
        }
    }
    return false;
}

function handle_doc_upload($field, $subdir, $allowed) {
    if(empty($_FILES[$field]['name'])) return "";
    $base = __DIR__ . "/uploads/$subdir/";
    ensure_dir($base);
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, $allowed)) return "";
    $name = time()."_".bin2hex(random_bytes(4)).".$ext";
    move_uploaded_file($_FILES[$field]['tmp_name'], $base.$name);
    return "uploads/$subdir/$name";
}

// Handle Add Event
$msg = "";
if(isset($_POST['submit'])){
    $department = $_SESSION['department'] ?? 'CSE';
    $coordinator = $_POST['coordinator'] ?? '';
    $file = handle_doc_upload('document','files',['pdf','doc','docx','ppt','xls','txt','zip']);
    $stmt = $conn->prepare("INSERT INTO events (event_type, sem, department, coordinator, event_name, event_description, event_start_date, event_end_date, resource_person, file_path, remuneration) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssssssd", 
        $_POST['event_type'], 
        $_POST['sem'], 
        $department, 
        $coordinator,
        $_POST['event_name'], 
        $_POST['event_description'], 
        $_POST['event_start_date'], 
        $_POST['event_end_date'], 
        $_POST['resource_person'], 
        $file, 
        $_POST['remuneration']
    );
    if($stmt->execute()){
        $event_id = $stmt->insert_id;
        if(isset($_FILES['photos_zip']) && $_FILES['photos_zip']['error']==0){
            handle_zip_upload($_FILES['photos_zip'], $event_id);
        }
        $msg = "<div class='alert alert-success'>✓ Event added successfully!</div>";
    } else { $msg = "<div class='alert alert-danger'>Error: ".$stmt->error."</div>"; }
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$user_dept = $_SESSION['department'] ?? 'CSE';

// ---------- DEPARTMENT FILTERING ----------
$is_super_admin = ($_SESSION['admin'] == 'admin'); // Principal sees all

// Build WHERE clause for department filter (only if not super admin)
$dept_where = "";
$dept_where_and = "";
if(!$is_super_admin){
    $dept = $conn->real_escape_string($user_dept);
    $dept_where = " WHERE department = '$dept' ";
    $dept_where_and = " AND department = '$dept' ";
}

// Dashboard stats (filtered for department users)
$total_events = $conn->query("SELECT COUNT(*) as c FROM events $dept_where")->fetch_assoc()['c'];

// For upcoming events: combine department filter with date condition
if($is_super_admin){
    $upcoming = $conn->query("SELECT COUNT(*) as c FROM events WHERE event_start_date > CURDATE()")->fetch_assoc()['c'];
} else {
    $upcoming = $conn->query("SELECT COUNT(*) as c FROM events WHERE department = '$dept' AND event_start_date > CURDATE()")->fetch_assoc()['c'];
}

// Total expenses
$expenses = $conn->query("SELECT SUM(remuneration) as t FROM events $dept_where")->fetch_assoc()['t'] ?? 0;

// Total distinct departments (global, not filtered)
$depts = $conn->query("SELECT COUNT(DISTINCT department) as c FROM events WHERE department IS NOT NULL")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | EventHub</title>
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
        /* All styles remain exactly the same as your premium version */
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
        .stat-card { background: white; border-radius: 20px; padding: 20px; transition: all 0.3s; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: 100%; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .stat-icon { width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 15px; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #1a1a2e; margin: 10px 0 5px; }
        .stat-label { color: #666; font-size: 0.85rem; font-weight: 500; margin: 0; }
        .premium-card { background: white; border-radius: 20px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 30px; }
        .premium-card .card-header { background: white; border-bottom: 2px solid #f0f0f0; padding: 20px 25px; font-weight: 700; font-size: 1.2rem; }
        .premium-card .card-body { padding: 25px; }
        .form-control, .form-select { border: 2px solid #e0e0e0; border-radius: 12px; padding: 12px 16px; transition: all 0.3s; }
        .form-control:focus, .form-select:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .form-label { font-weight: 600; color: #1a1a2e; margin-bottom: 8px; }
        .btn-premium { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 12px 30px; border-radius: 12px; font-weight: 600; transition: all 0.3s; }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); color: white; }
        .btn-outline-premium { border: 2px solid #667eea; background: transparent; color: #667eea; padding: 10px 25px; border-radius: 12px; font-weight: 600; transition: all 0.3s; }
        .btn-outline-premium:hover { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: transparent; }
        table.dataTable thead th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; border: none; padding: 15px; }
        table.dataTable tbody tr:hover { background: rgba(102,126,234,0.05); }
        .photo-gallery { display: flex; gap: 8px; flex-wrap: wrap; }
        .gallery-thumb { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.2s; }
        .gallery-thumb:hover { transform: scale(1.1); }
        .mobile-menu-btn { display: none; position: fixed; top: 20px; left: 20px; z-index: 1001; background: #667eea; border: none; color: white; padding: 10px 15px; border-radius: 10px; cursor: pointer; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 15px; }
            .mobile-menu-btn { display: block; }
            .top-bar { margin-top: 50px; }
        }
        .alert { border-radius: 12px; margin-bottom: 20px; }
    </style>
</head>
<body>
<button class="mobile-menu-btn" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i> Menu</button>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header"><h3>📋 EventHub</h3><p>Event Management System</p></div>
    <div class="sidebar-menu">
        <a href="?tab=dashboard" class="nav-link <?= $tab=='dashboard'?'active':'' ?>"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
        <a href="?tab=addEvent" class="nav-link <?= $tab=='addEvent'?'active':'' ?>"><i class="fas fa-plus-circle"></i><span>Add Event</span></a>
        <a href="?tab=allEvents" class="nav-link <?= $tab=='allEvents'?'active':'' ?>"><i class="fas fa-calendar-alt"></i><span>All Events</span></a>
    
    
        <!-- Inside sidebar-menu -->
<a href="add_achievement.php" class="nav-link">
    <i class="fas fa-plus-circle"></i><span>Add Achievement</span>
</a>
<a href="all_achievements.php" class="nav-link">
    <i class="fas fa-trophy"></i><span>All Achievements</span>
</a>


        <a href="resource_info.php" class="nav-link"><i class="fas fa-users"></i><span>Resource Information</span></a>
        <a href="calendar.php" class="nav-link"><i class="fas fa-calendar-week"></i><span>Calendar</span></a>
        <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i><span>Settings</span></a>
        <a href="logout.php" class="nav-link" style="margin-top:20px;"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>
<div class="main-content">
    <div class="top-bar">
        <h4 class="page-title"><?php if($tab=='dashboard') echo 'Dashboard'; elseif($tab=='addEvent') echo 'Add New Event'; else echo 'All Events'; ?></h4>
        <div><span class="badge bg-primary rounded-pill px-3 py-2"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin'] ?? 'Admin') ?> (<?= htmlspecialchars($user_dept) ?>)</span></div>
    </div>

    <!-- DASHBOARD (filtered stats) -->
    <?php if($tab == 'dashboard'): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6"><div class="stat-card"><div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220);"><i class="fas fa-calendar-alt" style="color: #667eea;"></i></div><div class="stat-value"><?= $total_events ?></div><p class="stat-label">Total Events</p></div></div>
        <div class="col-md-3 col-sm-6"><div class="stat-card"><div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);"><i class="fas fa-hourglass-half" style="color: #28a745;"></i></div><div class="stat-value"><?= $upcoming ?></div><p class="stat-label">Upcoming Events</p></div></div>
        <div class="col-md-3 col-sm-6"><div class="stat-card"><div class="stat-icon" style="background: linear-gradient(135deg, #fd7e1420, #ffc10720);"><i class="fas fa-building" style="color: #fd7e14;"></i></div><div class="stat-value"><?= $depts ?></div><p class="stat-label">Departments</p></div></div>
        <div class="col-md-3 col-sm-6"><div class="stat-card"><div class="stat-icon" style="background: linear-gradient(135deg, #dc354520, #e83e8c20);"><i class="fas fa-rupee-sign" style="color: #dc3545;"></i></div><div class="stat-value">₹<?= number_format($expenses,2) ?></div><p class="stat-label">Total Expenses</p></div></div>
    </div>
    <div class="premium-card"><div class="card-header"><i class="fas fa-chart-line me-2"></i> Recent Activities</div><div class="card-body"><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Event Name</th><th>Type</th><th>Department</th><th>Date</th><th>Status</th></tr></thead><tbody>
        <?php 
        // Recent activities query
        if($is_super_admin){
            $recent_sql = "SELECT * FROM events WHERE event_start_date IS NOT NULL AND event_start_date != '0000-00-00' ORDER BY created_at DESC LIMIT 5";
        } else {
            $recent_sql = "SELECT * FROM events WHERE department = '$dept' AND event_start_date IS NOT NULL AND event_start_date != '0000-00-00' ORDER BY created_at DESC LIMIT 5";
        }
        $recent = $conn->query($recent_sql);
        while($r=$recent->fetch_assoc()):
            $today = date('Y-m-d');
            if($today < $r['event_start_date']) { $status='Upcoming'; $badge='primary'; }
            elseif($today > $r['event_end_date']) { $status='Completed'; $badge='secondary'; }
            else { $status='Ongoing'; $badge='success'; }
        ?>
        <tr>
            <td><?= htmlspecialchars($r['event_name']) ?></td>
            <td><?= htmlspecialchars($r['event_type']) ?></td>
            <td><?= htmlspecialchars($r['department']??'N/A') ?></td>
            <td><?= date('M d, Y', strtotime($r['event_start_date'])) ?></td>
            <td><span class="badge bg-<?= $badge ?>"><?= $status ?></span></td>
        </tr>
        <?php endwhile; ?>
    </tbody><tr></div></div></div>
    <?php endif; ?>

    <!-- ADD EVENT (unchanged) -->
    <?php if($tab == 'addEvent'): ?>
    <div class="premium-card"><div class="card-header"><i class="fas fa-plus-circle me-2"></i> Add New Event</div><div class="card-body">
        <?= $msg ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="department" value="<?= htmlspecialchars($user_dept) ?>">
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Event Type *</label><select name="event_type" class="form-select" required><option value="">-- Select --</option><?php $types=$conn->query("SELECT type_name FROM event_types"); while($t=$types->fetch_assoc()) echo "<option>".htmlspecialchars($t['type_name'])."</option>"; ?></select></div>
                <div class="col-md-6 mb-3"><label class="form-label">Semester *</label><select name="sem" class="form-select"><option>Semester 1</option><option>Semester 2</option><option>Semester 3</option><option>Semester 4</option><option>Semester 5</option><option>Semester 6</option><option>Semester 7</option><option>Semester 8</option><option>All Semesters</option></select></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Event Name *</label><input type="text" name="event_name" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Coordinator *</label><input type="text" name="coordinator" class="form-control" placeholder="Enter coordinator name" required></div>
            </div>
            <div class="mb-3"><label class="form-label">Event Description</label><textarea name="event_description" class="form-control" rows="4"></textarea></div>
            <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Start Date *</label><input type="date" name="event_start_date" class="form-control" required></div><div class="col-md-6 mb-3"><label class="form-label">End Date *</label><input type="date" name="event_end_date" class="form-control" required></div></div>
            <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Resource Person</label><input type="text" name="resource_person" class="form-control"></div><div class="col-md-6 mb-3"><label class="form-label">Remuneration (₹)</label><input type="number" name="remuneration" class="form-control" step="0.01" value="0"></div></div>
            <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Upload Document</label><input type="file" name="document" class="form-control"></div><div class="col-md-6 mb-3"><label class="form-label">Upload Photos (ZIP only)</label><input type="file" name="photos_zip" class="form-control" accept=".zip"><small class="text-muted">Select a ZIP file containing all event photos</small></div></div>
            <button type="submit" name="submit" class="btn-premium w-100"><i class="fas fa-save me-2"></i> Add Event</button>
        </form>
    </div></div>
    <?php endif; ?>

    <!-- ALL EVENTS (filtered by department) -->
    <?php if($tab == 'allEvents'): ?>
    <div class="premium-card"><div class="card-header"><i class="fas fa-calendar-alt me-2"></i> All Events
        <div class="float-end">
            <button class="btn btn-outline-premium btn-sm me-2" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-file-import"></i> Import</button>
            <button class="btn btn-outline-premium btn-sm" id="exportBtn"><i class="fas fa-file-excel"></i> Export</button>
        </div>
    </div><div class="card-body">
        <div class="table-responsive">
            <table id="eventsTable" class="table table-bordered table-striped align-middle" style="width:100%">
                <thead><tr>
                    <th>ID</th><th>Type</th><th>Semester</th><th>Department</th><th>Event Name</th>
                    <th>Coordinator</th><th>Start Date</th><th>End Date</th><th>Resource Person</th>
                    <th>Remuneration</th><th>Document</th><th>Photos & ZIP</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php 
                // ----- DEPARTMENT FILTER FOR ALL EVENTS -----
                if($is_super_admin){
                    $events_sql = "SELECT * FROM events ORDER BY id DESC";
                } else {
                    $dept = $conn->real_escape_string($user_dept);
                    $events_sql = "SELECT * FROM events WHERE department = '$dept' ORDER BY id DESC";
                }
                $all = $conn->query($events_sql);
                while($e=$all->fetch_assoc()):
                    $doc_link = !empty($e['file_path']) ? "<a href='".htmlspecialchars($e['file_path'])."' target='_blank' class='btn btn-sm btn-primary'><i class='fas fa-download'></i></a>" : "—";
                    $gallery_path = "uploads/events/{$e['id']}/gallery/";
                    $original_zip = "uploads/events/{$e['id']}/original.zip";
                    $photos_html = "—";
                    $zip_download = "";
                    if(is_dir($gallery_path)){
                        $images = glob($gallery_path . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
                        if(count($images)>0){
                            $photos_html = "<div class='photo-gallery'>";
                            foreach(array_slice($images,0,3) as $img){
                                $photos_html .= "<a href='".htmlspecialchars($img)."' data-lightbox='event-{$e['id']}'><img src='".htmlspecialchars($img)."' class='gallery-thumb'></a>";
                            }
                            if(count($images)>3) $photos_html .= "<span class='badge bg-secondary ms-1'>+".(count($images)-3)."</span>";
                            $photos_html .= "</div>";
                        }
                    }
                    if(file_exists($original_zip)){
                        $zip_download = "<a href='".htmlspecialchars($original_zip)."' class='btn btn-sm btn-info mt-1'><i class='fas fa-download'></i> ZIP</a>";
                    }
                ?>
                <tr>
                    <td><?= $e['id'] ?></td>
                    <td><?= htmlspecialchars($e['event_type']) ?></td>
                    <td><?= htmlspecialchars($e['sem']) ?></td>
                    <td><?= htmlspecialchars($e['department']) ?></td>
                    <td><?= htmlspecialchars($e['event_name']) ?></td>
                    <td><?= htmlspecialchars($e['coordinator'] ?? '—') ?></td>
                    <td><?= $e['event_start_date'] ?></td>
                    <td><?= $e['event_end_date'] ?></td>
                    <td><?= htmlspecialchars($e['resource_person']) ?></td>
                    <td>₹<?= number_format($e['remuneration'],2) ?></td>
                    <td class="text-center"><?= $doc_link ?></td>
                    <td class="text-center"><?= $photos_html . "<br>" . $zip_download ?></td>
                    <td class="text-center"><a href="edit_event.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a> <a href="delete_event.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this event?')"><i class="fas fa-trash"></i></a></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div></div>
    <?php endif; ?>
</div>

<!-- Import Modal (unchanged) -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                <h5 class="modal-title"><i class="fas fa-file-excel me-2"></i> Import Events</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="import_events.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Excel File (.xlsx or .xls)</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                        <div class="form-text">Format: Event Name, Event Type, Start Date (DD-MM-YYYY), End Date (DD-MM-YYYY), Semester, Department, Coordinator, Resource Person, Remuneration, Description</div>
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
    var table = $('#eventsTable').DataTable({ pageLength:10, order:[[0,'desc']] });
    $('#exportBtn').on('click', function(){
        var search = table.search();
        var dept = $('#filterDepartment').val() || '';
        var status = $('#filterStatus').val() || '';
        window.location.href = 'export_events.php?search='+encodeURIComponent(search)+'&department='+encodeURIComponent(dept)+'&status='+encodeURIComponent(status);
    });
    lightbox.option({ resizeDuration:200, wrapAround:true });
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