<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$is_super_admin = ($_SESSION['admin'] == 'admin');

// Build department filter
if($is_super_admin){
    $dept_where = "";
} else {
    $dept = $conn->real_escape_string($_SESSION['department'] ?? '');
    $dept_where = " WHERE department = '$dept' ";
}

// Handle delete
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

// Count total for debug
$total_all = $conn->query("SELECT COUNT(*) as cnt FROM achievements")->fetch_assoc()['cnt'];
$filtered_sql = "SELECT * FROM achievements $dept_where ORDER BY id DESC";
$result = $conn->query($filtered_sql);
if(!$result){
    die("SQL Error: " . $conn->error);
}
$filtered_count = $result->num_rows;
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
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 20px; }
        .premium-card { background: white; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 20px 20px 0 0; font-weight: 700; }
        .card-body { padding: 25px; }
        table.dataTable thead th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .photo-gallery { display: flex; gap: 5px; flex-wrap: wrap; }
        .gallery-thumb { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <div class="premium-card">
        <div class="card-header">
            <i class="fas fa-trophy me-2"></i> All Achievements
            <a href="add_achievement.php" class="btn btn-sm btn-light float-end">+ Add New</a>
        </div>
        <div class="card-body">
            <!-- Debug info -->
            <div class="alert alert-info">
                <strong>Debug:</strong> Total achievements in DB: <?= $total_all ?> | 
                Showing: <?= $filtered_count ?> (filter: <?= $is_super_admin ? 'Admin (all)' : ($_SESSION['department'] ?? 'none') ?>)<br>
                SQL: <?= htmlspecialchars($filtered_sql) ?>
            </div>
            <?php if($filtered_count == 0): ?>
                <div class="alert alert-warning text-center">No achievements match your department filter. Try adding one.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="achievementsTable" class="table table-bordered table-striped align-middle">
                    <thead><tr><th>ID</th><th>Student Name</th><th>Event Name</th><th>Department</th><th>Semester</th><th>Dates</th><th>Coordinator</th><th>Photos</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <?php
                            $gallery_path = "uploads/achievements/{$row['id']}/gallery/";
                            $photos_html = '<span class="text-muted">—</span>';
                            if(is_dir($gallery_path)){
                                $images = glob($gallery_path . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
                                if(!empty($images)){
                                    $photos_html = '<div class="photo-gallery">';
                                    foreach(array_slice($images,0,4) as $img){
                                        $photos_html .= "<a href='$img' data-lightbox='ach-{$row['id']}'><img src='$img' class='gallery-thumb'></a>";
                                    }
                                    if(count($images)>4) $photos_html .= "<span class='badge bg-secondary'>+".(count($images)-4)."</span>";
                                    $photos_html .= '</div>';
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
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
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
<script>
$(document).ready(function(){
    $('#achievementsTable').DataTable({ pageLength:10, order:[[0,'desc']] });
    lightbox.option({ resizeDuration:200, wrapAround:true });
});
</script>
</body>
</html>