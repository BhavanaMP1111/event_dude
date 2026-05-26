<?php
include 'db.php';

function formatDateForDisplay($d) {
    if (empty($d) || $d === '0000-00-00') return 'N/A';
    $ts = strtotime($d);
    return $ts ? date('M d, Y', $ts) : htmlspecialchars($d);
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | EventHub</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
        .premium-header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 20px 20px 0 0; padding: 25px; color: white; }
        .premium-card { background: white; border-radius: 20px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow: hidden; margin: 20px; }
        .stat-box { background: white; border-radius: 15px; padding: 20px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: transform 0.3s; height: 100%; }
        .stat-box:hover { transform: translateY(-5px); }
        .stat-box i { font-size: 2rem; margin-bottom: 10px; }
        .stat-number { font-size: 1.8rem; font-weight: 700; color: #1a1a2e; }
        .stat-label { color: #666; font-size: 0.85rem; }
        table.dataTable thead th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; border: none; padding: 15px; }
        .photo-gallery { display: flex; gap: 5px; flex-wrap: wrap; }
        .gallery-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 5px; cursor: pointer; }
        @media (max-width: 768px) { .stat-number { font-size: 1.3rem; } .premium-header h1 { font-size: 1.5rem; } }
    </style>
</head>
<body>
    <div class="container mt-4 mb-4">
        <div class="premium-card">
            <div class="premium-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h1 class="mb-0"><i class="fas fa-graduation-cap me-2"></i> Student Dashboard</h1>
                        <p class="mb-0 mt-2 opacity-75">Browse and explore all events</p>
                    </div>
                    <div class="mt-2 mt-sm-0">
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="fas fa-calendar-alt me-1"></i> <?php echo date('F d, Y'); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="p-4">
                <?php
                $total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
                $ongoing_events = $conn->query("SELECT COUNT(*) as count FROM events WHERE event_start_date <= CURDATE() AND event_end_date >= CURDATE()")->fetch_assoc()['count'];
                $upcoming_events = $conn->query("SELECT COUNT(*) as count FROM events WHERE event_start_date > CURDATE()")->fetch_assoc()['count'];
                ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><div class="stat-box"><i class="fas fa-calendar-alt" style="color: #667eea;"></i><div class="stat-number"><?php echo $total_events; ?></div><div class="stat-label">Total Events</div></div></div>
                    <div class="col-md-4"><div class="stat-box"><i class="fas fa-play-circle" style="color: #28a745;"></i><div class="stat-number"><?php echo $ongoing_events; ?></div><div class="stat-label">Ongoing Events</div></div></div>
                    <div class="col-md-4"><div class="stat-box"><i class="fas fa-clock" style="color: #fd7e14;"></i><div class="stat-number"><?php echo $upcoming_events; ?></div><div class="stat-label">Upcoming Events</div></div></div>
                </div>
                
                <div class="table-responsive">
                    <table id="eventsTable" class="table table-striped table-bordered align-middle" style="width:100%">
                        <thead>
                            <tr><th>Event Type</th><th>Event Name</th><th>Department</th><th>Description</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Resource Person</th><th>Document</th><th>Photos</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM events WHERE event_start_date IS NOT NULL AND event_start_date != '0000-00-00' ORDER BY event_start_date DESC";
                            $result = $conn->query($sql);
                            if ($result) {
                                while ($row = $result->fetch_assoc()):
                                    $start = $row['event_start_date'] ?? '';
                                    $end = $row['event_end_date'] ?? '';
                                    if ($today >= $start && $today <= $end) { $badge = "<span class='badge bg-success'>Ongoing</span>"; }
                                    elseif ($today < $start) { $badge = "<span class='badge bg-primary'>Upcoming</span>"; }
                                    else { $badge = "<span class='badge bg-secondary'>Completed</span>"; }
                                    
                                    // Get photos
                                    $photos = $conn->query("SELECT photo_path FROM event_gallery WHERE event_id = " . $row['id']);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['event_type'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['event_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['event_description'] ?? '', 0, 100)); ?></td>
                                <td><?php echo formatDateForDisplay($start); ?></td>
                                <td><?php echo formatDateForDisplay($end); ?></td>
                                <td><?php echo $badge; ?></td>
                                <td><?php echo htmlspecialchars($row['resource_person'] ?? ''); ?></td>
                                <td class="text-center"><?php if(!empty($row['file_path'])): ?><a href="<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-download"></i></a><?php else: ?>—<?php endif; ?></td>
                                <td>
                                    <?php if($photos && $photos->num_rows > 0): ?>
                                        <div class="photo-gallery">
                                            <?php while($photo = $photos->fetch_assoc()): ?>
                                                <a href="<?php echo $photo['photo_path']; ?>" data-lightbox="event-<?php echo $row['id']; ?>">
                                                    <img src="<?php echo $photo['photo_path']; ?>" class="gallery-thumb">
                                                </a>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#eventsTable').DataTable({ pageLength: 10, order: [[4, 'desc']] });
        });
        lightbox.option({ 'resizeDuration': 200, 'wrapAround': true });
    </script>
</body>
</html>