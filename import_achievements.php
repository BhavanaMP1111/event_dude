<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'db.php';
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

$import_msg = "";

if(isset($_POST['import_excel']) && isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0){
    $file = $_FILES['excel_file']['tmp_name'];
    $extension = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
    
    try {
        if(!in_array($extension, ['xlsx', 'xls'])){
            throw new Exception("Only Excel files (.xlsx, .xls) are allowed.");
        }
        $reader = IOFactory::createReaderForFile($file);
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        if(count($rows) < 2){
            throw new Exception("File must contain header row and data.");
        }
        
        $headers = array_map('strtolower', array_map('trim', $rows[0]));
        $expected = ['student name', 'event name', 'semester', 'coordinator', 'description', 'start date', 'end date', 'department'];
        
        // For simplicity, assume columns are in order: Student Name, Event Name, Semester, Coordinator, Description, Start Date, End Date, Department
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        for($i = 1; $i < count($rows); $i++){
            $rowData = $rows[$i];
            if(empty(array_filter($rowData))) continue;
            
            $student_name = trim($rowData[0] ?? '');
            $event_name = trim($rowData[1] ?? '');
            $semester = trim($rowData[2] ?? '');
            $coordinator = trim($rowData[3] ?? '');
            $description = trim($rowData[4] ?? '');
            $start_raw = trim($rowData[5] ?? '');
            $end_raw = trim($rowData[6] ?? '');
            $department = trim($rowData[7] ?? ($_SESSION['department'] ?? 'CSE'));
            
            if(empty($student_name) || empty($event_name) || empty($start_raw) || empty($end_raw)){
                $errorCount++;
                $errors[] = "Row ".($i+1).": Missing required fields (Student Name, Event Name, Start Date, End Date)";
                continue;
            }
            
            // Parse dates
            $start_date = parse_date($start_raw);
            $end_date = parse_date($end_raw);
            if(!$start_date || !$end_date){
                $errorCount++;
                $errors[] = "Row ".($i+1).": Invalid date format (use DD-MM-YYYY)";
                continue;
            }
            
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO achievements (student_name, event_name, semester, coordinator, description, start_date, end_date, department) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssss", $student_name, $event_name, $semester, $coordinator, $description, $start_date, $end_date, $department);
            if($stmt->execute()){
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Row ".($i+1).": DB error - ".$stmt->error;
            }
            $stmt->close();
        }
        
        $import_msg = "<div class='alert alert-success'>Import completed! Success: $successCount, Errors: $errorCount</div>";
        if(!empty($errors)){
            $import_msg .= "<div class='alert alert-warning'>".implode('<br>', array_slice($errors,0,10))."</div>";
        }
    } catch(Exception $e){
        $import_msg = "<div class='alert alert-danger'>Error: ".$e->getMessage()."</div>";
    }
}

function parse_date($date_str){
    if(empty($date_str)) return null;
    if(is_numeric($date_str)){
        return date('Y-m-d', ExcelDate::excelToTimestamp($date_str));
    }
    $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d'];
    foreach($formats as $format){
        $date_obj = DateTime::createFromFormat($format, $date_str);
        if($date_obj && $date_obj->format($format) == $date_str){
            return $date_obj->format('Y-m-d');
        }
    }
    $timestamp = strtotime($date_str);
    return $timestamp ? date('Y-m-d', $timestamp) : null;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Import Achievements</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">Import Achievements</div>
        <div class="card-body">
            <?= $import_msg ?>
            <a href="all_achievements.php" class="btn btn-secondary">Back to Achievements</a>
        </div>
    </div>
</div>
</body>
</html>