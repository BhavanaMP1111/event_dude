<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

include 'db.php';

require_once 'vendor/autoload.php'; // Composer autoload for PhpSpreadsheet
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
            throw new Exception("The Excel file must contain at least a header row and one data row.");
        }
        
        // Get headers from first row and normalize them
        $rawHeaders = array_map('trim', $rows[0]);
        $headers = [];
        foreach ($rawHeaders as $h) {
            // Convert to lowercase, replace underscores with spaces, collapse multiple spaces
            $normalized = strtolower($h);
            $normalized = str_replace('_', ' ', $normalized);
            $normalized = preg_replace('/\s+/', ' ', $normalized);
            $headers[] = $normalized;
        }
        
        // Define expected column variants (all lowercase, no underscores)
        $columnMapping = [
            'event_name'        => ['event name', 'event_name', 'name'],
            'event_type'        => ['event type', 'type'],
            'semester'          => ['semester', 'sem'],
            'department'        => ['department', 'dept'],
            'coordinator'       => ['coordinator', 'coordinator name'],
            'event_start_date'  => ['start date', 'event start date', 'start'],
            'event_end_date'    => ['end date', 'event end date', 'end'],
            'resource_person'   => ['resource person', 'resource', 'speaker'],
            'remuneration'      => ['remuneration', 'payment', 'amount'],
            'event_description' => ['description', 'event description', 'desc']
        ];
        
        // Build mapping: database column => column index in Excel
        $dbToExcelIndex = [];
        foreach ($columnMapping as $dbField => $possibleHeaders) {
            foreach ($possibleHeaders as $variant) {
                $index = array_search($variant, $headers);
                if ($index !== false) {
                    $dbToExcelIndex[$dbField] = $index;
                    break;
                }
            }
        }
        
        // Check mandatory fields
        $mandatory = ['event_name', 'event_type', 'event_start_date', 'event_end_date'];
        $missing = [];
        foreach ($mandatory as $field) {
            if (!isset($dbToExcelIndex[$field])) {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            throw new Exception("Missing required columns in Excel: " . implode(', ', $missing));
        }
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        // Process data rows (skip header)
        for ($i = 1; $i < count($rows); $i++) {
            $rowData = $rows[$i];
            
            // Skip completely empty rows
            if (empty(array_filter($rowData))) continue;
            
            // Extract values
            $event_name     = trim($rowData[$dbToExcelIndex['event_name']] ?? '');
            $event_type     = trim($rowData[$dbToExcelIndex['event_type']] ?? '');
            $start_raw      = trim($rowData[$dbToExcelIndex['event_start_date']] ?? '');
            $end_raw        = trim($rowData[$dbToExcelIndex['event_end_date']] ?? '');
            
            // Validate mandatory fields
            if (empty($event_name) || empty($event_type) || empty($start_raw) || empty($end_raw)) {
                $errorCount++;
                $errors[] = "Row " . ($i+1) . ": Missing mandatory fields (Name, Type, Start, End)";
                continue;
            }
            
            // Parse dates
            $start_date = parse_date($start_raw);
            $end_date   = parse_date($end_raw);
            
            if (!$start_date) {
                $errorCount++;
                $errors[] = "Row " . ($i+1) . ": Invalid Start Date format (use DD-MM-YYYY)";
                continue;
            }
            if (!$end_date) {
                $errorCount++;
                $errors[] = "Row " . ($i+1) . ": Invalid End Date format (use DD-MM-YYYY)";
                continue;
            }
            
            // Optional fields
            $semester       = isset($dbToExcelIndex['semester']) ? trim($rowData[$dbToExcelIndex['semester']] ?? '') : '';
            $department     = isset($dbToExcelIndex['department']) ? trim($rowData[$dbToExcelIndex['department']] ?? '') : ($_SESSION['department'] ?? 'CSE');
            $coordinator    = isset($dbToExcelIndex['coordinator']) ? trim($rowData[$dbToExcelIndex['coordinator']] ?? '') : '';
            $resource_person= isset($dbToExcelIndex['resource_person']) ? trim($rowData[$dbToExcelIndex['resource_person']] ?? '') : '';
            $remuneration   = isset($dbToExcelIndex['remuneration']) ? floatval(trim($rowData[$dbToExcelIndex['remuneration']] ?? 0)) : 0;
            $description    = isset($dbToExcelIndex['event_description']) ? trim($rowData[$dbToExcelIndex['event_description']] ?? '') : '';
            
            // Insert into database (ID auto-increment)
            $sql = "INSERT INTO events 
                    (event_name, event_type, sem, department, coordinator, 
                     event_start_date, event_end_date, resource_person, 
                     remuneration, event_description, file_path, photo_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '')";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssds", 
                $event_name, $event_type, $semester, $department, $coordinator,
                $start_date, $end_date, $resource_person,
                $remuneration, $description
            );
            
            if ($stmt->execute()) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Row " . ($i+1) . ": DB error - " . $stmt->error;
            }
            $stmt->close();
        }
        
        $import_msg = "<div class='alert alert-success'>✓ Import completed!<br>Success: $successCount records<br>Errors: $errorCount records</div>";
        if (!empty($errors)) {
            $import_msg .= "<div class='alert alert-warning mt-2'><strong>Details:</strong><br>" . implode('<br>', array_slice($errors, 0, 10)) . "</div>";
        }
        
    } catch(Exception $e){
        $import_msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

/**
 * Parse date from various formats (DD-MM-YYYY, DD/MM/YYYY, Excel serial number, YYYY-MM-DD)
 */
function parse_date($date_str){
    if (empty($date_str)) return null;
    
    // If it's a number (Excel serial date)
    if (is_numeric($date_str)) {
        $timestamp = ExcelDate::excelToTimestamp($date_str);
        return date('Y-m-d', $timestamp);
    }
    
    // Try DD-MM-YYYY or DD/MM/YYYY
    $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'm/d/Y', 'd.m.Y'];
    foreach ($formats as $format) {
        $date_obj = DateTime::createFromFormat($format, $date_str);
        if ($date_obj && $date_obj->format($format) == $date_str) {
            return $date_obj->format('Y-m-d');
        }
    }
    
    // Fallback: try strtotime
    $timestamp = strtotime($date_str);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Events | EventHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 30px; }
        .premium-card { background: white; border-radius: 20px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.1); max-width: 800px; margin: auto; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 25px; border-radius: 20px 20px 0 0; font-weight: 700; }
        .card-body { padding: 30px; }
        .form-control, .form-select { border: 2px solid #e0e0e0; border-radius: 12px; padding: 12px 16px; }
        .btn-premium { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 12px 25px; border-radius: 12px; font-weight: 600; width: 100%; }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); color: white; }
        .alert { border-radius: 12px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="premium-card">
        <div class="card-header">
            <i class="fas fa-file-import me-2"></i> Import Events from Excel
        </div>
        <div class="card-body">
            <?php echo $import_msg; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label">Excel File (.xlsx or .xls)</label>
                    <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                    <div class="form-text mt-2">
                        <strong>Expected columns (case‑insensitive, supports underscores/spaces):</strong><br>
                        - <strong>Event Name</strong> (mandatory)<br>
                        - <strong>Event Type</strong> (mandatory)<br>
                        - <strong>Start Date</strong> (mandatory, format DD-MM-YYYY or DD/MM/YYYY)<br>
                        - <strong>End Date</strong> (mandatory)<br>
                        - Semester, Department, Coordinator, Resource Person, Remuneration, Description (optional)<br>
                        <br>
                        <em>Note: ID, file paths, and photo ZIP are ignored (auto-generated). Dates will be converted to YYYY-MM-DD automatically.</em>
                    </div>
                </div>
                <button type="submit" name="import_excel" class="btn-premium">
                    <i class="fas fa-upload me-2"></i> Import Events
                </button>
                <a href="admin_dashboard.php?tab=allEvents" class="btn btn-secondary w-100 mt-2">Back to All Events</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>