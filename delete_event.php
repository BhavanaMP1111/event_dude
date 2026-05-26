<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Get all photos for this event and delete them
    $photos = $conn->query("SELECT photo_path FROM event_gallery WHERE event_id = $id");
    while ($photo = $photos->fetch_assoc()) {
        if (!empty($photo['photo_path']) && file_exists($photo['photo_path'])) {
            unlink($photo['photo_path']);
        }
    }
    
    // Delete gallery entries
    $conn->query("DELETE FROM event_gallery WHERE event_id = $id");
    
    // Delete file if exists
    $res = $conn->query("SELECT file_path FROM events WHERE id=$id");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (!empty($row['file_path']) && file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
    }

    // Delete event
    if ($conn->query("DELETE FROM events WHERE id=$id") === TRUE) {
        $msg = "<div class='alert alert-success text-center'>🗑️ Event deleted successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger text-center'>❌ Error deleting event: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Event</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container { max-width: 500px; width: 100%; }
        .alert { border-radius: 15px; padding: 20px; margin-bottom: 20px; }
        .btn { padding: 12px 24px; border-radius: 12px; width: 100%; font-weight: 600; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center">
            <?= isset($msg) ? $msg : "" ?>
            <a href="admin_dashboard.php#allEvents" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <?php if(isset($msg) && strpos($msg, 'success') !== false): ?>
    <script>
        setTimeout(() => { window.location.href = 'admin_dashboard.php#allEvents'; }, 2000);
    </script>
    <?php endif; ?>
</body>
</html>