<?php
include 'db.php';

$msg = "";
if (isset($_POST['add_event_type'])) {
    $type_name = trim($_POST['type_name'] ?? "");
    
    if (!empty($type_name)) {
        $check = $conn->query("SELECT id FROM event_types WHERE type_name = '$type_name'");
        if ($check->num_rows == 0) {
            if ($conn->query("INSERT INTO event_types (type_name) VALUES ('$type_name')")) {
                $msg = "<div class='alert alert-success'>Event type added successfully!</div>";
            } else {
                $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
            }
        } else {
            $msg = "<div class='alert alert-warning'>Event type already exists!</div>";
        }
    }
}

// Handle deletion with confirmation via GET (will use JS confirmation)
$delete_id = isset($_GET['delete_type']) ? (int)$_GET['delete_type'] : 0;
if ($delete_id > 0) {
    if ($conn->query("DELETE FROM event_types WHERE id = $delete_id")) {
        $msg = "<div class='alert alert-success'>Event type deleted successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Event Management</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            z-index: 1000;
            transition: all 0.3s;
            box-shadow: 10px 0 30px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h3 {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .sidebar-header p {
            color: rgba(255,255,255,0.6);
            font-size: 0.75rem;
        }
        
        .sidebar-menu {
            padding: 0 15px;
        }
        
        .sidebar-menu .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 12px 16px;
            border-radius: 12px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .sidebar-menu .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar-menu .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
        }
        
        .top-bar {
            background: white;
            border-radius: 20px;
            padding: 15px 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }
        
        .premium-card {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .premium-card .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            padding: 20px 25px;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .premium-card .card-body {
            padding: 25px;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .btn-premium {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
        }
        
        table.dataTable thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px;
        }
        
        .type-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        
        .type-item:hover {
            background: rgba(102,126,234,0.05);
        }
        
        .type-name {
            font-weight: 500;
            color: #1a1a2e;
        }
        
        .type-badge {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            color: #667eea;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: #667eea;
                border: none;
                color: white;
                padding: 10px;
                border-radius: 10px;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none;
            }
        }
        
        /* Modal styles */
        .modal-content {
            border-radius: 20px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
        }
        
        .modal-footer {
            border-top: none;
        }
    </style>
</head>
<body>

<button class="mobile-menu-btn" onclick="toggleMobileMenu()">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>📋 EventHub</h3>
        <p>Settings</p>
    </div>
    
    <div class="sidebar-menu">
        <a href="admin_dashboard.php" class="nav-link">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="admin_dashboard.php#addEvent" class="nav-link">
            <i class="fas fa-plus-circle"></i>
            <span>Add Event</span>
        </a>
        <a href="admin_dashboard.php#allEvents" class="nav-link">
            <i class="fas fa-calendar-alt"></i>
            <span>All Events</span>
        </a>
        <a href="resource_info.php" class="nav-link">
            <i class="fas fa-users"></i>
            <span>Resource Information</span>
        </a>
        <a href="calendar.php" class="nav-link">
            <i class="fas fa-calendar-week"></i>
            <span>Calendar</span>
        </a>
        <a href="#" class="nav-link active">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h4 class="page-title">Settings</h4>
        <div>
            <span class="badge bg-primary rounded-pill px-3 py-2">
                <i class="fas fa-user-shield"></i> Admin Panel
            </span>
        </div>
    </div>
    
    <?= $msg ?>
    
    <div class="premium-card">
        <div class="card-header">
            <i class="fas fa-plus-circle me-2"></i> Add New Event Type
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-9">
                        <input type="text" name="type_name" class="form-control" placeholder="Enter event type name (e.g., Conference, Workshop, Seminar)" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="add_event_type" class="btn btn-premium w-100">
                            <i class="fas fa-plus me-2"></i> Add Type
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="premium-card">
        <div class="card-header">
            <i class="fas fa-tags me-2"></i> Existing Event Types
        </div>
        <div class="card-body">
            <?php
            $result = $conn->query("SELECT * FROM event_types ORDER BY type_name ASC");
            if ($result->num_rows > 0):
            ?>
                <div class="list">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="type-item">
                            <div>
                                <span class="type-name"><?php echo htmlspecialchars($row['type_name']); ?></span>
                                <span class="type-badge ms-2">ID: <?php echo $row['id']; ?></span>
                            </div>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['type_name']); ?>')">
                                <i class="fas fa-trash me-1"></i> Delete
                            </button>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No event types found. Add your first event type above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete event type: <strong id="deleteTypeName"></strong>?</p>
                <p class="text-danger mb-0"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMobileMenu() {
    document.getElementById('sidebar').classList.toggle('mobile-open');
}

function confirmDelete(id, name) {
    document.getElementById('deleteTypeName').innerText = name;
    document.getElementById('confirmDeleteBtn').href = '?delete_type=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Close sidebar on outside click for mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuBtn = document.querySelector('.mobile-menu-btn');
    if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-open') && 
        !sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
        sidebar.classList.remove('mobile-open');
    }
});
</script>
</body>
</html>