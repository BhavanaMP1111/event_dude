


<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

function ensure_dir($dir) { if (!is_dir($dir)) mkdir($dir, 0777, true); }

function handle_achievement_zip_upload($file, $achievement_id) {
    if(empty($file['name'])) return false;
    $upload_dir = "uploads/achievements/$achievement_id/";
    ensure_dir($upload_dir);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if($ext != 'zip') return false;
    $original_zip = $upload_dir . "photos.zip";
    if(move_uploaded_file($file['tmp_name'], $original_zip)){
        $zip = new ZipArchive;
        if($zip->open($original_zip) === TRUE){
            $extract_to = $upload_dir . "gallery/";
            ensure_dir($extract_to);
            array_map('unlink', glob($extract_to . "*.*"));
            $zip->extractTo($extract_to);
            $zip->close();
            unlink($original_zip);
            return true;
        }
    }
    return false;
}

$msg = "";
$is_super_admin = ($_SESSION['admin'] == 'admin');

// Display current session department for debugging
$debug_info = "";
if(!$is_super_admin){
    $debug_info = "<div class='alert alert-info'>Your session department: <strong>" . htmlspecialchars($_SESSION['department'] ?? 'NOT SET') . "</strong></div>";
}

if(isset($_POST['submit'])){
    $student_name = trim($_POST['student_name']);
    $event_name = trim($_POST['event_name']);
    $semester = $_POST['semester'];
    $coordinator = trim($_POST['coordinator']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    if($is_super_admin){
        $department = $_POST['department'];
    } else {
        $department = $_SESSION['department'] ?? 'CSE';
    }
    
    $sql = "INSERT INTO achievements (student_name, event_name, semester, coordinator, description, start_date, end_date, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if($stmt){
        $stmt->bind_param("ssssssss", $student_name, $event_name, $semester, $coordinator, $description, $start_date, $end_date, $department);
        if($stmt->execute()){
            $id = $stmt->insert_id;
            if(isset($_FILES['photos_zip']) && $_FILES['photos_zip']['error'] == 0){
                handle_achievement_zip_upload($_FILES['photos_zip'], $id);
            }
            $msg = "<div class='alert alert-success'>✓ Achievement added successfully! ID: $id, Department: $department</div>";
        } else {
            $msg = "<div class='alert alert-danger'>SQL Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $msg = "<div class='alert alert-danger'>Prepare failed: " . $conn->error . "</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Achievement | EventHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 30px; }
        .premium-card { background: white; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); max-width: 900px; margin: auto; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 20px 20px 0 0; font-weight: 700; }
        .card-body { padding: 30px; }
        .form-control, .form-select { border: 2px solid #e0e0e0; border-radius: 12px; padding: 12px; }
        .btn-premium { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 12px; border-radius: 12px; width: 100%; font-weight: 600; }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
    </style>
</head>
<body>
<div class="container">
    <div class="premium-card">
        <div class="card-header"><i class="fas fa-trophy me-2"></i> Add New Achievement</div>
        <div class="card-body">
            <?php echo $debug_info; ?>
            <?php echo $msg; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Student Name *</label><input type="text" name="student_name" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Event Name *</label><input type="text" name="event_name" class="form-control" required></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Semester</label><select name="semester" class="form-select"><option>Semester 1</option><option>Semester 2</option><option>Semester 3</option><option>Semester 4</option><option>Semester 5</option><option>Semester 6</option><option>Semester 7</option><option>Semester 8</option><option>All Semesters</option></select></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Coordinator</label><input type="text" name="coordinator" class="form-control"></div>
                </div>
                <?php if($is_super_admin): ?>
                <div class="mb-3"><label class="form-label">Department *</label><select name="department" class="form-select" required><option value="CSE">CSE</option><option value="ISE">ISE</option><option value="ECE">ECE</option><option value="MECH">MECH</option><option value="CIVIL">CIVIL</option><option value="AIML">AIML</option><option value="AIDS">AIDS</option><option value="EEE">EEE</option></select></div>
                <?php else: ?>
                <div class="mb-3"><label class="form-label">Department (Auto-assigned)</label><input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['department'] ?? 'CSE') ?>" disabled></div>
                <?php endif; ?>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Start Date *</label><input type="date" name="start_date" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">End Date *</label><input type="date" name="end_date" class="form-control" required></div>
                </div>
                <div class="mb-3"><label class="form-label">Upload Photos (ZIP only)</label><input type="file" name="photos_zip" class="form-control" accept=".zip"></div>
                <button type="submit" name="submit" class="btn-premium"><i class="fas fa-save me-2"></i> Add Achievement</button>
                <a href="admin_dashboard.php" class="btn btn-secondary w-100 mt-2">Back to Dashboard</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>