<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $event_type = $_POST['event_type'];
    $event_name = $_POST['event_name'];
    $event_description = $_POST['event_description'];
    $event_date = $_POST['event_date'];
    $resource_person = $_POST['resource_person'];
    $resource_link = $_POST['resource_link'] ?? ""; // NEW

    // File upload handling (document file)
    $file_path = $_POST['existing_file'] ?? ""; // keep old file if no new file
    if (!empty($_FILES['file']['name'])) {
        $target_dir = "uploads/files/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_path = $target_dir . basename($_FILES['file']['name']);
        move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
    }

    // Photo upload handling (JPG/PNG/PDF)
    $photo_path = $_POST['existing_photo'] ?? ""; // keep old photo if no new upload
    if (!empty($_FILES['photo']['name'])) {
        $target_dir = "uploads/photos/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $photo_path = $target_dir . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
    }

    // Update query with new columns
    $sql = "UPDATE events 
            SET event_type=?, event_name=?, event_description=?, event_date=?, resource_person=?, resource_link=?, file_path=?, photo_path=? 
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", $event_type, $event_name, $event_description, $event_date, $resource_person, $resource_link, $file_path, $photo_path, $id);

    if ($stmt->execute()) {
        $msg = "<div class='alert alert-success text-center'>✅ Event updated successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger text-center'>❌ Error updating event: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Event</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { 
            background: #f8f9fa; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
        }
        
        .alert {
            font-size: 1rem;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: slideIn 0.5s ease-out;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1e7dd 0%, #a3cfbb 100%);
            color: #0f5132;
            border-left: 4px solid #198754;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f1aeb5 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .btn {
            padding: 12px 24px;
            font-size: 1rem;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            max-width: 300px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(13, 110, 253, 0.3);
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .container {
                max-width: 100%;
            }
            
            .alert {
                font-size: 0.95rem;
                padding: 12px;
                margin-bottom: 15px;
            }
            
            .btn {
                padding: 14px 20px;
                font-size: 1rem;
                max-width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 10px;
            }
            
            .alert {
                font-size: 0.9rem;
                padding: 10px;
                margin-bottom: 15px;
            }
            
            .btn {
                padding: 12px 16px;
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 360px) {
            body {
                padding: 8px;
            }
            
            .alert {
                font-size: 0.85rem;
                padding: 8px;
                margin-bottom: 12px;
            }
            
            .btn {
                padding: 10px 14px;
                font-size: 0.9rem;
            }
        }
        
        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Loading state for button */
        .btn-loading {
            position: relative;
            color: transparent;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Success message enhancements */
        .alert .btn-link {
            color: inherit;
            text-decoration: underline;
            font-weight: 600;
        }
        
        .alert .btn-link:hover {
            color: inherit;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center">
            <?= isset($msg) ? $msg : "" ?>
            
            <a href="student_dashboard.php" class="btn btn-primary">
                <i class="bi bi-arrow-left-circle"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        // Add loading state and auto-redirect functionality
        document.addEventListener('DOMContentLoaded', function() {
            const backButton = document.querySelector('.btn');
            const successAlert = document.querySelector('.alert-success');
            
            // Add loading state to button
            backButton.addEventListener('click', function(e) {
                this.classList.add('btn-loading');
                this.disabled = true;
                
                // Remove loading state after a timeout (fallback)
                setTimeout(() => {
                    this.classList.remove('btn-loading');
                    this.disabled = false;
                }, 3000);
            });
            
            // Auto-redirect after 3 seconds if update was successful
            if (successAlert) {
                setTimeout(() => {
                    window.location.href = 'student_dashboard.php';
                }, 3000);
                
                // Add countdown indicator
                let countdown = 3;
                const originalContent = successAlert.innerHTML;
                
                const countdownInterval = setInterval(() => {
                    countdown--;
                    successAlert.innerHTML = originalContent + 
                        ' <small class="fw-bold">Redirecting in ' + countdown + ' seconds...</small>';
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                    }
                }, 1000);
            }
            
            // Improve touch experience
            backButton.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });
            
            backButton.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
            
            // Add vibration feedback on mobile (optional)
            backButton.addEventListener('touchstart', function() {
                if (navigator.vibrate) {
                    navigator.vibrate(10);
                }
            });
        });
    </script>
</body>
</html>