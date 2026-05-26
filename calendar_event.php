<?php
include 'db.php';

$events = [];

$sql = "SELECT event_name, event_start_date, event_end_date FROM events";
$result = $conn->query($sql);

while($row = $result->fetch_assoc()){

    $start = $row['event_start_date'];
    $end = $row['event_end_date'];

    $today = date("Y-m-d");

    // If event is currently running
    if($today >= $start && $today <= $end){
        $color = "#28a745"; // green
    } else {
        $color = "#007bff"; // normal blue
    }

    $events[] = [
        "title" => $row['event_name'],
        "start" => $start,
        "end" => date('Y-m-d', strtotime($end . ' +1 day')),
        "color" => $color
    ];
}

echo json_encode($events);
?>