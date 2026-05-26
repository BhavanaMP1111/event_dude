<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "event_final";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Add department column if not exists
$check_column = "SHOW COLUMNS FROM events LIKE 'department'";
$result = $conn->query($check_column);
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE events ADD COLUMN department VARCHAR(100) DEFAULT 'CSE' AFTER sem");
}

// Add coordinator column
$check_coordinator = "SHOW COLUMNS FROM events LIKE 'coordinator'";
$result = $conn->query($check_coordinator);
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE events ADD COLUMN coordinator VARCHAR(255) DEFAULT NULL AFTER department");
}

// Add multiple photos support - create new table for event galleries
$check_gallery = "SHOW TABLES LIKE 'event_gallery'";
$result = $conn->query($check_gallery);
if ($result->num_rows == 0) {
    $conn->query("CREATE TABLE IF NOT EXISTS event_gallery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        photo_path VARCHAR(500) NOT NULL,
        photo_name VARCHAR(255),
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    )");
}
?>