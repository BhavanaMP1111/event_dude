<?php
include 'db.php';

// Get resource ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Resource ID.");
}
$id = intval($_GET['id']);
$msg = "";

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM resource_info WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Resource not found.");
}

$data = $result->fetch_assoc();

// Update resource when form is submitted
if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $phone_number = $_POST['phone_number'];
    $company = $_POST['company'];
    $designation = $_POST['designation'];
    $email_id = $_POST['email_id'];
    $profile_link = trim($_POST['profile_link']);
    $payment = $_POST['payment'];

    if ($profile_link !== "" && !preg_match('~^https?://~i', $profile_link)) {
        $profile_link = "https://" . $profile_link;
    }

    $update_sql = "UPDATE resource_info SET 
        name=?, phone_number=?, company=?, designation=?, email_id=?, profile_link=?, payment=? 
        WHERE id=?";

    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->bind_param("ssssssdi", $name, $phone_number, $company, $designation, $email_id, $profile_link, $payment, $id);

    if ($stmt_update->execute()) {
        $msg = "<div class='alert alert-success text-center'>Updated Successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger text-center'>Error: " . $stmt_update->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Resource</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background: #f8f9fa; }
        .container { max-width: 600px; }
        @media (max-width: 576px) {
            h2 { font-size: 1.4rem; }
            .card { padding: 15px; }
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center">✏️ Edit Resource</h2>

    <?= $msg ?>

    <div class="card p-4 shadow">
        <form method="POST">

            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control mb-3" value="<?= htmlspecialchars($data['name']) ?>" required>

            <label class="form-label">Phone Number</label>
            <input type="text" name="phone_number" class="form-control mb-3" value="<?= htmlspecialchars($data['phone_number']) ?>">

            <label class="form-label">Company</label>
            <input type="text" name="company" class="form-control mb-3" value="<?= htmlspecialchars($data['company']) ?>">

            <label class="form-label">Designation</label>
            <input type="text" name="designation" class="form-control mb-3" value="<?= htmlspecialchars($data['designation']) ?>">

            <label class="form-label">Email ID</label>
            <input type="email" name="email_id" class="form-control mb-3" value="<?= htmlspecialchars($data['email_id']) ?>">

            <label class="form-label">Profile Link</label>
            <input type="url" name="profile_link" class="form-control mb-3" 
                   value="<?= htmlspecialchars($data['profile_link']) ?>">

            <label class="form-label">Payment (₹)</label>
            <input type="number" name="payment" class="form-control mb-3" step="0.01" min="0"
                   value="<?= htmlspecialchars($data['payment']) ?>">

            <button type="submit" name="update" class="btn btn-primary w-100 py-2">Update Resource</button>

            <a href="resource_info.php" class="btn btn-secondary w-100 mt-2">Back</a>
        </form>
    </div>
</div>

</body>
</html>
