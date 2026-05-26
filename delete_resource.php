<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($conn->query("DELETE FROM resource_info WHERE id = $id")) {
        header("Location: resource_info.php?msg=deleted");
    } else {
        header("Location: resource_info.php?msg=error");
    }
} else {
    header("Location: resource_info.php");
}
exit();
?>