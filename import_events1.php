require 'vendor/autoload.php';
<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

include 'db.php';

require_once 'vendor/autoload.php'; // Requires PhpSpreadsheet via Composer

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$import_msg = "";

if(isset($_POST['import_excel']) && isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0){
    $file = $_FILES['excel_file']['tmp_name'];
    $extension = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
    
    try {
        if($extension == 'xlsx' || $extension == 'xls'){
            $reader = IOFactory::createReaderForFile($file);
            $spreadsheet = $reader->load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            $header = array_shift($rows);
            $success_count = 0;
            $error_count = 0;
            $errors = [];
            
            foreach($rows as $row_index => $row_data){
                if(empty(array_filter($row_data))) continue;
                
                // Map columns (adjust indices based on your Excel structure)
                // Assuming columns: Event Name, Event Type, Department, Start Date, End Date, Description, Resource Person, Remuneration
                $event_name = trim($row_data[0] ?? '');
                $event_type = trim($row_data[1] ?? '');
                $department = trim($row_data[2] ?? 'CSE');
                $start_date = trim($row_data[3] ?? '');
                $end_date = trim($row_data[4] ?? '');
                $description = trim($row_data[5] ?? '');
                $resource_person = trim($row_data[6] ?? '');
                $remuneration = floatval($row_data[7] ?? 0);
                $semester = trim($row_data[8] ?? 'Semester 1');
                
                if(empty($event_name) || empty($event_type)){
                    $error_count++;
                    $errors[] = "Row " . ($row_index + 2) . ": Missing required fields (Event Name or Type)";
                    continue;
                }
                
                // Convert Excel date if needed
                if(is_numeric($start_date)){
                    $start_date = date('Y-m-d', ($start_date - 25569) * 86400);
                }
                if(is_numeric($end_date)){
                    $end_date = date('Y-m-d', ($end_date - 25569) * 86400);
                }
                
                // Check if event exists (by name and start date)
                $check_sql = "SELECT id FROM events WHERE event_name = ? AND event_start_date = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ss", $event_name, $start_date);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if($check_result->num_rows > 0){
                    // Update existing event
                    $existing = $check_result->fetch_assoc();
                    $update_sql = "UPDATE events SET 
                        event_type = ?, department = ?, event_end_date = ?, 
                        event_description = ?, resource_person = ?, remuneration = ?, sem = ?
                        WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("sssssssi", 
                        $event_type, $department, $end_date, 
                        $description, $resource_person, $remuneration, $semester, 
                        $existing['id']
                    );
                    if($update_stmt->execute()){
                        $success_count++;
                    } else {
                        $error_count++;
                        $errors[] = "Row " . ($row_index + 2) . ": Update failed - " . $update_stmt->error;
                    }
                    $update_stmt->close();
                } else {
                    // Insert new event
                    $insert_sql = "INSERT INTO events 
                        (event_name, event_type, department, event_start_date, event_end_date, 
                         event_description, resource_person, remuneration, sem) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("sssssssds", 
                        $event_name, $event_type, $department, $start_date, $end_date,
                        $description, $resource_person, $remuneration, $semester
                    );
                    if($insert_stmt->execute()){
                        $success_count++;
                        
                        // Also add to resource_info if resource person exists
                        if(!empty($resource_person)){
                            $check_resource = $conn->prepare("SELECT id FROM resource_info WHERE name = ?");
                            $check_resource->bind_param("s", $resource_person);
                            $check_resource->execute();
                            if($check_resource->get_result()->num_rows == 0){
                                $insert_resource = $conn->prepare("INSERT INTO resource_info (name, payment) VALUES (?, ?)");
                                $insert_resource->bind_param("sd", $resource_person, $remuneration);
                                $insert_resource->execute();
                                $insert_resource->close();
                            }
                            $check_resource->close();
                        }
                    } else {
                        $error_count++;
                        $errors[] = "Row " . ($row_index + 2) . ": Insert failed - " . $insert_stmt->error;
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
            
            $import_msg = "<div class='alert alert-success'>
                <i class='fas fa-check-circle'></i> Import completed!<br>
                <strong>Success:</strong> $success_count records<br>
                <strong>Errors:</strong> $error_count records
            </div>";
            
            if(!empty($errors)){
                $import_msg .= "<div class='alert alert-warning mt-2'>";
                foreach(array_slice($errors, 0, 5) as $err){
                    $import_msg .= "• " . htmlspecialchars($err) . "<br>";
                }
                if(count($errors) > 5){
                    $import_msg .= "• And " . (count($errors) - 5) . " more errors...";
                }
                $import_msg .= "</div>";
            }
            
        } else {
            $import_msg = "<div class='alert alert-danger'>Please upload a valid Excel file (.xlsx or .xls)</div>";
        }
    } catch(Exception $e) {
        $import_msg = "<div class='alert alert-danger'>Error reading file: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Events | EventHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', sans-serif;
        }
        .premium-card {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .btn-premium {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
        }
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="premium-card p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-file-excel fa-3x" style="color: #28a745;"></i>
                        <h2 class="mt-3">Import Events from Excel</h2>
                        <p class="text-muted">Upload an Excel file to bulk import or update events</p>
                    </div>
                    
                    <?= $import_msg ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label">Excel File (.xlsx or .xls)</label>
                            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                            <div class="form-text">
                                <strong>Expected column order:</strong><br>
                                Event Name | Event Type | Department | Start Date | End Date | Description | Resource Person | Remuneration | Semester
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="import_excel" class="btn-premium">
                                <i class="fas fa-upload me-2"></i> Import Events
                            </button>
                            <a href="admin_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> If an event with the same name and start date exists, it will be updated. Otherwise, a new event will be created.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Excel Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                <h5 class="modal-title"><i class="fas fa-file-excel me-2"></i> Import Events from Excel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="import_events.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Excel File (.xlsx or .xls)</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                        <div class="form-text mt-2">
                            <strong>Expected column format (in order):</strong><br>
                            1. Event Name * | 2. Event Type * | 3. Department | 4. Start Date (YYYY-MM-DD) | 5. End Date (YYYY-MM-DD) | 6. Description | 7. Resource Person | 8. Remuneration | 9. Semester
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="import_excel" class="btn btn-premium">Import Events</button>
                </div>
            </form>
        </div>
    </div>
</div>


</body>
</html>