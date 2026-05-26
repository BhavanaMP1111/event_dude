<?php
include 'db.php';
header('Content-Type: application/json');

$date = isset($_GET['date']) ? $_GET['date'] : '';
if(empty($date)){
    echo json_encode([]);
    exit;
}

// Fetch events where the given date falls between start and end dates
$sql = "SELECT id, event_name, event_type, department, coordinator, 
               event_start_date, event_end_date, resource_person, remuneration,
               event_description
        FROM events 
        WHERE event_start_date <= ? AND event_end_date >= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date, $date);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while($row = $result->fetch_assoc()){
    // Determine status
    $today = date('Y-m-d');
    if($today < $row['event_start_date']){
        $status = 'upcoming';
    } elseif($today > $row['event_end_date']){
        $status = 'completed';
    } else {
        $status = 'ongoing';
    }
    $row['status'] = $status;
    $events[] = $row;
}
echo json_encode($events);
?>