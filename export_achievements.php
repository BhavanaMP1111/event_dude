<?php
session_start();
if(!isset($_SESSION['admin'])){
    exit('Unauthorized');
}
include 'db.php';

$is_super_admin = ($_SESSION['admin'] == 'admin');

if($is_super_admin){
    $dept_where = "";
} else {
    $dept = $conn->real_escape_string($_SESSION['department']);
    $dept_where = " WHERE department = '$dept' ";
}

$sql = "SELECT id, student_name, event_name, department, semester, start_date, end_date, coordinator, description FROM achievements $dept_where ORDER BY id DESC";
$result = $conn->query($sql);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="achievements_export_'.date('Y-m-d').'.xls"');

echo "ID\tStudent Name\tEvent Name\tDepartment\tSemester\tStart Date\tEnd Date\tCoordinator\tDescription\n";
while($row = $result->fetch_assoc()){
    echo implode("\t", [
        $row['id'],
        $row['student_name'],
        $row['event_name'],
        $row['department'],
        $row['semester'],
        $row['start_date'],
        $row['end_date'],
        $row['coordinator'],
        $row['description']
    ]) . "\n";
}
exit;
?>