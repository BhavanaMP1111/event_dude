<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'db.php';

function ensure_dir($dir) {
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}

function extract_achievement_zip($file, $achievement_id) {
    if(empty($file['name'])) return false;
    $upload_dir = "uploads/achievements/$achievement_id/";
    ensure_dir($upload_dir);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if($ext != 'zip') return false;
    $zip_path = $upload_dir . "photos.zip";
    if(move_uploaded_file($file['tmp_name'], $zip_path)){
        $zip = new ZipArchive;
        if($zip->open($zip_path) === TRUE){
            $temp_dir = $upload_dir . "temp_extract/";
            ensure_dir($temp_dir);
            $zip->extractTo($temp_dir);
            $zip->close();
            $gallery_dir = $upload_dir . "gallery/";
            ensure_dir($gallery_dir);
            // Clear old gallery
            $old = glob($gallery_dir . "*.*");
            foreach($old as $f) if(is_file($f)) unlink($f);
            // Flatten and copy images
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS));
            $copied = 0;
            foreach($iterator as $fileObj) {
                if($fileObj->isFile() && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $fileObj->getFilename())) {
                    $dest = $gallery_dir . $fileObj->getFilename();
                    $counter = 1;
                    $info = pathinfo($dest);
                    while(file_exists($dest)) {
                        $dest = $info['dirname'] . '/' . $info['filename'] . "_$counter." . $info['extension'];
                        $counter++;
                    }
                    copy($fileObj->getPathname(), $dest);
                    $copied++;
                }
            }
            // Clean temp folder
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach($files as $f) {
                if($f->isDir()) rmdir($f->getRealPath());
                else unlink($f->getRealPath());
            }
            rmdir($temp_dir);
            unlink($zip_path);
            return $copied > 0;
        }
    }
    return false;
}

$msg = "";
$is_super_admin = ($_SESSION['admin'] == 'admin');

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
    
    $stmt = $conn->prepare("INSERT INTO achievements (student_name, event_name, semester, coordinator, description, start_date, end_date, department) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssss", $student_name, $event_name, $semester, $coordinator, $description, $start_date, $end_date, $department);
    if($stmt->execute()){
        $id = $stmt->insert_id;
        if(isset($_FILES['photos_zip']) && $_FILES['photos_zip']['error'] == 0){
            if(extract_achievement_zip($_FILES['photos_zip'], $id)){
                $msg = "<div class='alert alert-success'>✓ Achievement added! Photos uploaded.</div>";
            } else {
                $msg = "<div class='alert alert-warning'>✓ Achievement added, but photo upload failed. Ensure ZIP contains images (JPG, PNG, GIF, WEBP).</div>";
            }
        } else {
            $msg = "<div class='alert alert-success'>✓ Achievement added! (No photos)</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
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
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 30px; font-family: 'Inter', sans-serif; }
        .premium-card { background: white; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); max-width: 900px; margin: auto; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 20px 20px 0 0; font-weight: 700; }
        .card-body { padding: 30px; }
        .form-control, .form-select { border: 2px solid #e0e0e0; border-radius: 12px; padding: 12px; }
        .form-control:focus, .form-select:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .btn-premium { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 12px; border-radius: 12px; width: 100%; font-weight: 600; }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .btn-secondary { background: #6c757d; border: none; }
        .btn-secondary:hover { background: #5a6268; }
    </style>
</head>
<body>
<div class="container">
    <div class="premium-card">
        <div class="card-header">
            <i class="fas fa-trophy me-2"></i> Add New Achievement
        </div>
        <div class="card-body">
            <?= $msg ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Student Name *</label>
                        <input type="text" name="student_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Event Name *</label>
                        <input type="text" name="event_name" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select">
                            <option>Semester 1</option><option>Semester 2</option><option>Semester 3</option>
                            <option>Semester 4</option><option>Semester 5</option><option>Semester 6</option>
                            <option>Semester 7</option><option>Semester 8</option><option>All Semesters</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Coordinator</label>
                        <input type="text" name="coordinator" class="form-control">
                    </div>
                </div>
                <?php if($is_super_admin): ?>
                <div class="mb-3">
                    <label class="form-label">Department *</label>
                    <select name="department" class="form-select" required>
                        <option value="CSE">CSE</option><option value="ISE">ISE</option><option value="ECE">ECE</option>
                        <option value="MECH">MECH</option><option value="CIVIL">CIVIL</option><option value="AIML">AIML</option>
                        <option value="AIDS">AIDS</option><option value="EEE">EEE</option>
                    </select>
                </div>
                <?php else: ?>
                <div class="mb-3">
                    <label class="form-label">Department (Auto-assigned)</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['department'] ?? 'CSE') ?>" disabled>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Start Date *</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">End Date *</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Upload Photos (ZIP only)</label>
                    <input type="file" name="photos_zip" class="form-control" accept=".zip" required>
                    <small class="text-muted">Select a ZIP file containing all achievement photos (JPG, PNG, GIF, WEBP).</small>
                </div>
                <button type="submit" name="submit" class="btn-premium">
                    <i class="fas fa-save me-2"></i> Add Achievement
                </button>
                <a href="admin_dashboard.php" class="btn btn-secondary w-100 mt-2">Back to Dashboard</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>