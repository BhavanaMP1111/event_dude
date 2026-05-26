<?php
include 'db.php';

// Handle form submission
$msg = "";
if (isset($_POST['submit'])) {
    $name = $_POST['name'] ?? "";
    $phone_number = $_POST['phone_number'] ?? "";
    $company = $_POST['company'] ?? "";
    $designation = $_POST['designation'] ?? "";
    $email_id = $_POST['email_id'] ?? "";
    $profile_link = trim($_POST['profile_link'] ?? "");
    $payment = $_POST['payment'] ?? 0;

    if ($profile_link !== "" && !preg_match('~^https?://~i', $profile_link)) {
        $profile_link = "https://" . $profile_link;
    }

    $sql = "INSERT INTO resource_info (name, phone_number, company, designation, email_id, profile_link, payment) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssssd", $name, $phone_number, $company, $designation, $email_id, $profile_link, $payment);
        if ($stmt->execute()) {
            $msg = "<div class='alert alert-success'>Resource added successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Error: ".$stmt->error."</div>";
        }
        $stmt->close();
    }
}

// Get expense stats
$total_payment = $conn->query("SELECT SUM(payment) as total FROM resource_info")->fetch_assoc()['total'] ?? 0;
$total_resources = $conn->query("SELECT COUNT(*) as count FROM resource_info")->fetch_assoc()['count'];
$top_resource = $conn->query("SELECT name, payment FROM resource_info ORDER BY payment DESC LIMIT 1")->fetch_assoc();
$event_expenses = $conn->query("SELECT event_type, SUM(remuneration) as total FROM events GROUP BY event_type ORDER BY total DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Information | Event Management</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
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
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 10px 0 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.85rem;
            font-weight: 500;
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
        
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
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
        
        table.dataTable thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px;
        }
        
        table.dataTable tbody tr:hover {
            background: rgba(102,126,234,0.05);
        }
        
        .section {
            display: none;
            animation: fadeInUp 0.5s ease;
        }
        
        .section.active {
            display: block;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
    </style>
</head>
<body>

<button class="mobile-menu-btn" onclick="toggleMobileMenu()">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>📋 EventHub</h3>
        <p>Resource Management</p>
    </div>
    
    <div class="sidebar-menu">
        <a href="admin_dashboard.php" class="nav-link">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="#" class="nav-link active" onclick="showSection('dashboard'); return false;">
            <i class="fas fa-chart-pie"></i>
            <span>Expense Overview</span>
        </a>
        <a href="#" class="nav-link" onclick="showSection('addResource'); return false;">
            <i class="fas fa-user-plus"></i>
            <span>Add Resource</span>
        </a>
        <a href="#" class="nav-link" onclick="showSection('allResources'); return false;">
            <i class="fas fa-users"></i>
            <span>All Resources</span>
        </a>
        <a href="admin_dashboard.php" class="nav-link">
            <i class="fas fa-calendar-alt"></i>
            <span>Back to Events</span>
        </a>
        <a href="logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <h4 class="page-title" id="pageTitle">Expense Overview</h4>
        <div>
            <span class="badge bg-primary rounded-pill px-3 py-2">
                <i class="fas fa-user-shield"></i> Admin Panel
            </span>
        </div>
    </div>
    
    <!-- Dashboard/Overview Section -->
    <div id="dashboard" class="section active">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220);">
                        <i class="fas fa-rupee-sign" style="color: #667eea;"></i>
                    </div>
                    <div class="stat-value">₹<?php echo number_format($total_payment, 2); ?></div>
                    <p class="stat-label">Total Remuneration Paid</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                        <i class="fas fa-users" style="color: #28a745;"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_resources; ?></div>
                    <p class="stat-label">Total Resources</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fd7e1420, #ffc10720);">
                        <i class="fas fa-trophy" style="color: #fd7e14;"></i>
                    </div>
                    <div class="stat-value"><?php echo $top_resource ? htmlspecialchars($top_resource['name']) : 'N/A'; ?></div>
                    <p class="stat-label">Highest Paid Resource</p>
                    <?php if($top_resource): ?>
                        <small class="text-muted">₹<?php echo number_format($top_resource['payment'], 2); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="premium-card">
            <div class="card-header">
                <i class="fas fa-chart-bar me-2"></i> Event-wise Expenses
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Event Type</th>
                                <th>Total Expenses (₹)</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $grand_total = $total_payment > 0 ? $total_payment : 1;
                            while($exp = $event_expenses->fetch_assoc()):
                                $percentage = ($exp['total'] / $grand_total) * 100;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exp['event_type']); ?></td>
                                <td>₹<?php echo number_format($exp['total'], 2); ?></td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%; background: linear-gradient(90deg, #667eea, #764ba2);"></div>
                                    </div>
                                    <small><?php echo round($percentage, 1); ?>%</small>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Resource Section -->
    <div id="addResource" class="section">
        <div class="premium-card">
            <div class="card-header">
                <i class="fas fa-user-plus me-2"></i> Add New Resource Person
            </div>
            <div class="card-body">
                <?= $msg ?>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone_number" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" name="company" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation</label>
                            <input type="text" name="designation" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email ID</label>
                            <input type="email" name="email_id" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Profile Link (LinkedIn/GitHub)</label>
                            <input type="url" name="profile_link" class="form-control" placeholder="https://">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment (₹)</label>
                        <input type="number" name="payment" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    
                    <button type="submit" name="submit" class="btn btn-premium w-100">
                        <i class="fas fa-save me-2"></i> Add Resource
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- All Resources Section -->
    <div id="allResources" class="section">
        <div class="premium-card">
            <div class="card-header">
                <i class="fas fa-users me-2"></i> All Resource Persons
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="resourcesTable" class="table table-bordered table-striped align-middle" style="width:100%">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Company</th>
                                <th>Designation</th>
                                <th>Email</th>
                                <th>Profile</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM resource_info ORDER BY created_at DESC");
                            while ($row = $result->fetch_assoc()):
                                $id = (int)$row['id'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['phone_number'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['company'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['designation'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['email_id'] ?? ''); ?></td>
                                <td class="text-center">
                                    <?php if(!empty($row['profile_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['profile_link']); ?>" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No link</span>
                                    <?php endif; ?>
                                 </td>
                                <td>₹<?php echo number_format($row['payment'] ?? 0, 2); ?></td>
                                <td class="text-center">
                                    <a href="edit_resource.php?id=<?php echo $id; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_resource.php?id=<?php echo $id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this resource?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
    document.getElementById(sectionId).classList.add('active');
    
    const titles = {
        'dashboard': 'Expense Overview',
        'addResource': 'Add Resource Person',
        'allResources': 'All Resources'
    };
    document.getElementById('pageTitle').innerText = titles[sectionId] || 'Resource Management';
}

function toggleMobileMenu() {
    document.getElementById('sidebar').classList.toggle('mobile-open');
}

$(document).ready(function() {
    $('#resourcesTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[6, 'desc']],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });
});

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