<?php
session_start();
if(!isset($_SESSION['admin'])){
    exit('Unauthorized access.');
}
include 'db.php';

// Determine if user is super admin (principal)
$is_super_admin = ($_SESSION['admin'] == 'admin');

// Get filter parameters from URL (sent by export button)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Base query
$sql = "SELECT * FROM events WHERE 1=1";
$params = [];
$types = "";

// ----- 1. Department restriction (based on login) -----
if(!$is_super_admin){
    $user_dept = $_SESSION['department'] ?? 'CSE';
    $sql .= " AND department = ?";
    $params[] = $user_dept;
    $types .= "s";
}

// ----- 2. Global search (event_name, event_type, resource_person, department, sem) -----
if(!empty($search)){
    $sql .= " AND (event_name LIKE ? OR event_type LIKE ? OR resource_person LIKE ? OR department LIKE ? OR sem LIKE ?)";
    $like = "%$search%";
    for($i=0; $i<5; $i++) $params[] = $like;
    $types .= "sssss";
}

// ----- 3. Status filter -----
if(!empty($status_filter)){
    $today = date('Y-m-d');
    if($status_filter == 'upcoming'){
        $sql .= " AND event_start_date > ?";
        $params[] = $today;
        $types .= "s";
    } elseif($status_filter == 'ongoing'){
        $sql .= " AND event_start_date <= ? AND event_end_date >= ?";
        $params[] = $today;
        $params[] = $today;
        $types .= "ss";
    } elseif($status_filter == 'completed'){
        $sql .= " AND event_end_date < ?";
        $params[] = $today;
        $types .= "s";
    }
}

// Prepare and execute
$stmt = $conn->prepare($sql);
if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set Excel headers
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="filtered_events_'.date('Y-m-d').'.xls"');

// Output column headers
echo "ID\tEvent Type\tSemester\tDepartment\tEvent Name\tStart Date\tEnd Date\tResource Person\tRemuneration\tDocument Path\tGallery Folder\n";

// Output data rows
while($row = $result->fetch_assoc()){
    $gallery_folder = "uploads/events/{$row['id']}/gallery/";
    $doc_path = !empty($row['file_path']) ? $row['file_path'] : 'No document';
    $gallery_exists = is_dir($gallery_folder) ? $gallery_folder : 'No gallery';
    
    echo implode("\t", [
        $row['id'],
        $row['event_type'],
        $row['sem'],
        $row['department'],
        $row['event_name'],
        $row['event_start_date'],
        $row['event_end_date'],
        $row['resource_person'],
        $row['remuneration'],
        $doc_path,
        $gallery_exists
    ]) . "\n";
}
exit;
?>