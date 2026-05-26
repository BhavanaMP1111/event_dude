<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'db.php';

// File upload functions (same as before)
function ensure_dir($dir) { if (!is_dir($dir)) mkdir($dir, 0777, true); }
function handle_multiple_uploads($files, $event_id) {
    global $conn;
    $base = __DIR__ . "/uploads/photos/";
    ensure_dir($base);
    for($i=0; $i<count($files['name']); $i++) {
        if($files['error'][$i]==0) {
            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg','jpeg','png','gif'])) {
                $name = time()."_".rand(1000,9999)."_$i.$ext";
                move_uploaded_file($files['tmp_name'][$i], $base.$name);
                $stmt = $conn->prepare("INSERT INTO event_gallery (event_id, photo_path) VALUES (?, ?)");
                $stmt->bind_param("is", $event_id, "uploads/photos/$name");
                $stmt->execute();
            }
        }
    }
}
function handle_single_upload($field, $subdir, $allowed) {
    if(empty($_FILES[$field]['name'])) return "";
    $base = __DIR__ . "/uploads/$subdir/";
    ensure_dir($base);
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, $allowed)) return "";
    $name = time()."_".rand(1000,9999).".$ext";
    move_uploaded_file($_FILES[$field]['tmp_name'], $base.$name);
    return "uploads/$subdir/$name";
}

$msg = "";
if(isset($_POST['submit'])){
    $file = handle_single_upload('file','files',['pdf','doc','docx']);
    $sql = "INSERT INTO events (event_type, sem, department, event_name, event_description, event_start_date, event_end_date, resource_person, file_path, remuneration) VALUES (?,?,?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssd", $_POST['event_type'], $_POST['sem'], $_POST['department'], $_POST['event_name'], $_POST['event_description'], $_POST['event_start_date'], $_POST['event_end_date'], $_POST['resource_person'], $file, $_POST['remuneration']);
    if($stmt->execute()){
        $id = $stmt->insert_id;
        if(isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) handle_multiple_uploads($_FILES['photos'], $id);
        $msg = "<div class='alert alert-success'>Event added! <a href='all_events.php'>View All</a></div>";
    } else { $msg = "<div class='alert alert-danger'>Error: ".$stmt->error."</div>"; }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 260px; position: fixed; left: 0; top: 0; height: 100vh; background: #1a1a2e; color: white; padding: 20px; }
        .sidebar a { color: #ccc; display: block; padding: 10px; margin: 5px 0; text-decoration: none; border-radius: 8px; }
        .sidebar a:hover { background: #667eea; color: white; }
        .content { margin-left: 280px; padding: 20px; }
        .card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="sidebar">
    <h3>📋 EventHub</h3>
    <hr>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="add_event.php" class="active"><i class="fas fa-plus-circle"></i> Add Event</a>
    <a href="all_events.php"><i class="fas fa-calendar-alt"></i> All Events</a>
    <a href="resource_info.php"><i class="fas fa-users"></i> Resources</a>
    <a href="calendar.php"><i class="fas fa-calendar-week"></i> Calendar</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="content">
    <div class="card">
        <h3>Add New Event</h3>
        <?php echo $msg; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6"><label>Event Type</label><select name="event_type" class="form-control" required><?php $types=$conn->query("SELECT type_name FROM event_types"); while($t=$types->fetch_assoc()) echo "<option>{$t['type_name']}</option>"; ?></select></div>
                <div class="col-md-6"><label>Semester</label><select name="sem" class="form-control"><option>Semester 1</option><option>Semester 2</option><option>Semester 3</option><option>Semester 4</option><option>Semester 5</option><option>Semester 6</option><option>Semester 7</option><option>Semester 8</option></select></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6"><label>Department</label><select name="department" class="form-control"><option>CSE</option><option>ISE</option><option>ECE</option><option>MECH</option><option>CIVIL</option><option>AIML</option></select></div>
                <div class="col-md-6"><label>Event Name</label><input type="text" name="event_name" class="form-control" required></div>
            </div>
            <div class="mt-2"><label>Description</label><textarea name="event_description" class="form-control" rows="3"></textarea></div>
            <div class="row mt-2">
                <div class="col-md-6"><label>Start Date</label><input type="date" name="event_start_date" class="form-control" required></div>
                <div class="col-md-6"><label>End Date</label><input type="date" name="event_end_date" class="form-control" required></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6"><label>Resource Person</label><input type="text" name="resource_person" class="form-control"></div>
                <div class="col-md-6"><label>Remuneration (₹)</label><input type="number" name="remuneration" class="form-control" step="0.01" value="0"></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6"><label>Document</label><input type="file" name="file" class="form-control"></div>
                <div class="col-md-6"><label>Photos (multiple)</label><input type="file" name="photos[]" multiple class="form-control"></div>
            </div>
            <button type="submit" name="submit" class="btn btn-primary mt-3">Add Event</button>
        </form>
    </div>
</div>
</body>
</html>