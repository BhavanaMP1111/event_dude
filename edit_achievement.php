<?php
session_start();
if(!isset($_SESSION['admin'])){ header("Location: login.php"); exit(); }
include 'db.php';

if(!isset($_GET['id']) || empty($_GET['id'])) die("Invalid ID.");
$id = (int)$_GET['id'];
$achievement = $conn->query("SELECT * FROM achievements WHERE id = $id")->fetch_assoc();
if(!$achievement) die("Achievement not found.");

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
            
            // Recursively find all images and copy to gallery root (flatten)
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS));
            $copied = 0;
            foreach($iterator as $fileObj) {
                if($fileObj->isFile() && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $fileObj->getFilename())) {
                    $dest = $gallery_dir . $fileObj->getFilename();
                    // Handle duplicate filenames
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
            
            // Delete temp folder recursively
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach($files as $f) {
                if($f->isDir()) rmdir($f->getRealPath());
                else unlink($f->getRealPath());
            }
            rmdir($temp_dir);
            unlink($zip_path); // optional: delete zip after extraction
            return $copied > 0;
        }
    }
    return false;
}

$msg = '';
if(isset($_POST['update_photos'])){
    if(isset($_FILES['photos_zip']) && $_FILES['photos_zip']['error']==0){
        if(extract_achievement_zip($_FILES['photos_zip'], $id)){
            $msg = "<div class='alert alert-success'>Photos updated successfully.</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Failed to process ZIP file. Ensure it contains images (JPG, PNG, GIF, WEBP).</div>";
        }
    } else {
        $msg = "<div class='alert alert-warning'>Please select a ZIP file.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Achievement</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body{background:linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%);padding:30px}
        .premium-card{background:white;border-radius:20px;max-width:700px;margin:auto}
        .card-header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:20px;border-radius:20px 20px 0 0}
        .card-body{padding:30px}
        .info-box{background:#f8f9fa;border-left:4px solid #667eea;padding:15px;border-radius:12px;margin-bottom:20px}
        .gallery-thumb{width:80px;height:80px;object-fit:cover;border-radius:8px;margin:5px}
    </style>
</head>
<body>
<div class="premium-card">
    <div class="card-header"><i class="fas fa-edit"></i> Edit Achievement – Only Photos</div>
    <div class="card-body">
        <?= $msg ?>
        <div class="info-box">
            <strong>Student:</strong> <?= htmlspecialchars($achievement['student_name']) ?><br>
            <strong>Event:</strong> <?= htmlspecialchars($achievement['event_name']) ?><br>
            <strong>Department:</strong> <?= htmlspecialchars($achievement['department']) ?><br>
            <strong>Dates:</strong> <?= $achievement['start_date'] ?> → <?= $achievement['end_date'] ?>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Upload New Photos (ZIP only)</label>
                <input type="file" name="photos_zip" class="form-control" accept=".zip" required>
                <small class="text-muted">Upload a ZIP containing all achievement photos (old gallery will be replaced).</small>
            </div>
            <button type="submit" name="update_photos" class="btn btn-primary w-100"><i class="fas fa-upload"></i> Update Photos</button>
            <a href="all_achievements.php" class="btn btn-secondary w-100 mt-2">Back</a>
        </form>
        <hr>
        <h6>Current Gallery</h6>
        <div class="row">
            <?php
            $gallery = "uploads/achievements/$id/gallery/";
            if(is_dir($gallery)){
                $images = glob($gallery . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
                if(count($images)>0){
                    foreach($images as $img){
                        echo "<div class='col-3 mb-2'><a href='$img' target='_blank'><img src='$img' class='gallery-thumb'></a></div>";
                    }
                } else { echo "<p class='text-muted'>No photos yet.</p>"; }
            } else { echo "<p class='text-muted'>No gallery folder found.</p>"; }
            ?>
        </div>
    </div>
</div>
</body>
</html>