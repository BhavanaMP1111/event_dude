<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$id){
    die("Invalid achievement ID.");
}

$gallery_dir = "uploads/achievements/$id/gallery/";
if(!is_dir($gallery_dir)){
    die("No gallery found for this achievement.");
}

$images = glob($gallery_dir . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
if(empty($images)){
    die("No images found.");
}

// Create a temporary ZIP file
$zip = new ZipArchive();
$temp_zip = tempnam(sys_get_temp_dir(), 'achievement_') . '.zip';
if($zip->open($temp_zip, ZipArchive::CREATE) !== TRUE){
    die("Could not create ZIP file.");
}

foreach($images as $img){
    $zip->addFile($img, basename($img));
}
$zip->close();

// Send file for download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="achievement_' . $id . '_photos.zip"');
header('Content-Length: ' . filesize($temp_zip));
readfile($temp_zip);

// Delete the temporary ZIP file
unlink($temp_zip);
exit;
?>