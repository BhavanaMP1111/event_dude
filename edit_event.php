<?php
session_start();
if(!isset($_SESSION['admin'])){ header("Location: login.php"); exit(); }
include 'db.php';

if(!isset($_GET['id']) || empty($_GET['id'])) die("Invalid event ID.");
$event_id = (int)$_GET['id'];
$event = $conn->query("SELECT * FROM events WHERE id = $event_id")->fetch_assoc();
if(!$event) die("Event not found.");

function ensure_dir($dir) { if (!is_dir($dir)) mkdir($dir, 0777, true); }

$msg = '';
if(isset($_POST['update_files'])){
    $upload_dir = "uploads/events/$event_id/";
    ensure_dir($upload_dir);
    
    // Document upload
    if(!empty($_FILES['document']['name'])){
        $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
        $allowed_doc = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','zip'];
        if(in_array($ext, $allowed_doc)){
            $doc_name = "document_" . time() . ".$ext";
            move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . $doc_name);
            $conn->query("UPDATE events SET file_path = '$upload_dir$doc_name' WHERE id = $event_id");
            $msg .= "<div class='alert alert-success'>Document updated.</div>";
        } else { $msg .= "<div class='alert alert-danger'>Invalid document type.</div>"; }
    }
    
    // ZIP upload – save original and extract
    if(!empty($_FILES['photos_zip']['name']) && $_FILES['photos_zip']['error']==0){
        $zip_ext = strtolower(pathinfo($_FILES['photos_zip']['name'], PATHINFO_EXTENSION));
        if($zip_ext == 'zip'){
            $original_zip = $upload_dir . "original.zip";
            if(move_uploaded_file($_FILES['photos_zip']['tmp_name'], $original_zip)){
                $zip = new ZipArchive;
                if($zip->open($original_zip) === TRUE){
                    $extract_to = $upload_dir . "gallery/";
                    ensure_dir($extract_to);
                    $zip->extractTo($extract_to);
                    $zip->close();
                    $msg .= "<div class='alert alert-success'>ZIP uploaded and extracted.</div>";
                } else { $msg .= "<div class='alert alert-danger'>Failed to extract ZIP.</div>"; }
            } else { $msg .= "<div class='alert alert-danger'>Failed to save ZIP.</div>"; }
        } else { $msg .= "<div class='alert alert-danger'>Only ZIP files allowed for photos.</div>"; }
    }
    
    if(empty($_FILES['document']['name']) && empty($_FILES['photos_zip']['name']))
        $msg .= "<div class='alert alert-warning'>No files selected to update.</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event (Files Only)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{font-family:'Inter',sans-serif}
        body{background:linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%);padding:30px}
        .premium-card{background:white;border-radius:20px;border:none;box-shadow:0 5px 20px rgba(0,0,0,0.1);max-width:800px;margin:auto}
        .card-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px 25px;border-radius:20px 20px 0 0;font-weight:700}
        .card-body{padding:30px}
        .form-control,.form-select{border:2px solid #e0e0e0;border-radius:12px;padding:12px 16px}
        .form-control:focus,.form-select:focus{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1)}
        .btn-premium{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none;color:white;padding:12px;border-radius:12px;font-weight:600;width:100%}
        .btn-premium:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(102,126,234,0.4);color:white}
        .info-box{background:#f8f9fa;border-left:4px solid #667eea;padding:15px;border-radius:12px;margin-bottom:20px}
        .gallery-thumb{width:80px;height:80px;object-fit:cover;border-radius:8px;margin:5px;cursor:pointer}
        @media(max-width:768px){body{padding:15px}}
    </style>
</head>
<body>
<div class="premium-card">
    <div class="card-header"><i class="fas fa-edit me-2"></i> Edit Event – Only Files</div>
    <div class="card-body">
        <?= $msg ?>
        <div class="info-box">
            <strong><i class="fas fa-info-circle"></i> Event Details (Read Only)</strong><br>
            <strong>Name:</strong> <?= htmlspecialchars($event['event_name']) ?><br>
            <strong>Type:</strong> <?= htmlspecialchars($event['event_type']) ?><br>
            <strong>Department:</strong> <?= htmlspecialchars($event['department']) ?><br>
            <strong>Dates:</strong> <?= $event['event_start_date'] ?> to <?= $event['event_end_date'] ?>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Update Document (PDF, DOC, etc.)</label>
                <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip">
                <?php if($event['file_path']): ?>
                    <small class="text-muted">Current: <a href="<?= $event['file_path'] ?>" target="_blank">View</a></small>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Upload Photos (ZIP only)</label>
                <input type="file" name="photos_zip" class="form-control" accept=".zip">
                <small class="text-muted">Upload a ZIP containing all event photos. It will be saved as original.zip and extracted.</small>
            </div>
            <button type="submit" name="update_files" class="btn-premium"><i class="fas fa-upload me-2"></i> Update Files</button>
            <a href="admin_dashboard.php?tab=allEvents" class="btn btn-secondary w-100 mt-2">Back to All Events</a>
        </form>
        <hr>
        <h6>Current Gallery</h6>
        <div class="row">
            <?php
            $gallery_path = "uploads/events/$event_id/gallery/";
            if(is_dir($gallery_path)){
                $images = glob($gallery_path . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
                if(count($images)>0){
                    foreach($images as $img){
                        echo "<div class='col-3 mb-2'><a href='$img' target='_blank'><img src='$img' class='gallery-thumb'></a></div>";
                    }
                } else { echo "<p class='text-muted'>No photos yet.</p>"; }
            } else { echo "<p class='text-muted'>No gallery folder found.</p>"; }
            ?>
        </div>
        <?php if(file_exists("uploads/events/$event_id/original.zip")): ?>
            <hr>
            <h6>Download Original ZIP</h6>
            <a href="uploads/events/<?= $event_id ?>/original.zip" class="btn btn-info">Download ZIP</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>