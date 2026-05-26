<?php
session_start();
require_once "db.php";

// Get month/year from query or default to current
$month = isset($_GET['month']) ? intval($_GET['month']) : date("n");
$year  = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

// Handle month overflow
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$firstDayOfMonth = date("Y-m-01", strtotime("$year-$month-01"));
$lastDayOfMonth  = date("Y-m-t", strtotime("$year-$month-01"));

// Fetch all events for this month (including multi-day)
$sql = "SELECT id, event_name, event_type, department, event_start_date, event_end_date, resource_person 
        FROM events 
        WHERE (event_start_date BETWEEN ? AND ?) OR (event_end_date BETWEEN ? AND ?)
        ORDER BY event_start_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $firstDayOfMonth, $lastDayOfMonth, $firstDayOfMonth, $lastDayOfMonth);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();

// Build an array of events per date
$daysInMonth = date("t", strtotime($firstDayOfMonth));
$startDayOfWeek = date("w", strtotime($firstDayOfMonth)); // 0=Sun

$eventsByDate = [];
$today = date("Y-m-d");

foreach ($events as $event) {
    $start = strtotime($event['event_start_date']);
    $end   = strtotime($event['event_end_date']);
    for ($date = $start; $date <= $end; $date = strtotime("+1 day", $date)) {
        $dateKey = date("Y-m-d", $date);
        if (!isset($eventsByDate[$dateKey])) {
            $eventsByDate[$dateKey] = [];
        }
        // Determine status
        if ($today < $event['event_start_date']) {
            $status = 'upcoming';
        } elseif ($today > $event['event_end_date']) {
            $status = 'completed';
        } else {
            $status = 'ongoing';
        }
        $eventsByDate[$dateKey][] = [
            'id' => $event['id'],
            'name' => $event['event_name'],
            'type' => $event['event_type'],
            'department' => $event['department'],
            'start' => $event['event_start_date'],
            'end' => $event['event_end_date'],
            'resource' => $event['resource_person'],
            'status' => $status
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Event Calendar | EventHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .calendar-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }
        .premium-card {
            background: white;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .calendar-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 25px 30px;
            color: white;
        }
        .calendar-header h2 {
            font-weight: 700;
            margin: 0;
        }
        .nav-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 25px;
        }
        .nav-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
            color: white;
        }
        .month-title {
            font-size: 1.8rem;
            font-weight: 800;
            text-align: center;
            margin: 20px 0;
            color: #1a1a2e;
        }
        .calendar-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
        }
        .calendar-table th {
            text-align: center;
            padding: 15px;
            font-weight: 700;
            color: #667eea;
            font-size: 1rem;
        }
        .calendar-table td {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 12px;
            vertical-align: top;
            height: 130px;
            transition: all 0.3s;
            position: relative;
        }
        .calendar-table td:hover {
            background: white;
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        .day-number {
            font-weight: 800;
            font-size: 1.2rem;
            color: #1a1a2e;
            margin-bottom: 8px;
            display: inline-block;
            background: #e9ecef;
            width: 35px;
            height: 35px;
            line-height: 35px;
            text-align: center;
            border-radius: 50%;
        }
        .today .day-number {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .event-badge {
            display: inline-block;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 20px;
            margin: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            cursor: pointer;
            transition: all 0.2s;
        }
        .event-badge:hover {
            filter: brightness(0.9);
        }
        /* Status colors */
        .event-badge.upcoming { background: rgba(13,110,253,0.15); color: #0d6efd; }
        .event-badge.ongoing { background: rgba(25,135,84,0.15); color: #198754; }
        .event-badge.completed { background: rgba(108,117,125,0.15); color: #6c757d; }
        .event-count {
            position: absolute;
            bottom: 8px;
            right: 12px;
            font-size: 0.7rem;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 2px 6px;
            border-radius: 20px;
        }
        .btn-back {
            background: #6c757d;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-back:hover {
            background: #5a6268;
            color: white;
        }
        @media (max-width: 768px) {
            .calendar-table td { height: 100px; padding: 8px; }
            .day-number { width: 28px; height: 28px; line-height: 28px; font-size: 1rem; }
            .event-badge { font-size: 0.6rem; padding: 2px 5px; }
            .month-title { font-size: 1.3rem; }
        }
        /* Modal styling */
        .event-modal .modal-content {
            border-radius: 25px;
            border: none;
        }
        .event-modal .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 25px 25px 0 0;
        }
        .event-item {
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 12px;
        }
    </style>
</head>
<body>
<div class="calendar-wrapper">
    <a href="admin_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    
    <div class="premium-card">
        <div class="calendar-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2><i class="fas fa-calendar-alt me-2"></i> Event Calendar</h2>
                    <p class="mb-0 opacity-75">Manage and view all events</p>
                </div>
                <div class="mt-2 mt-sm-0">
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="fas fa-calendar-week me-1"></i> <?php echo date("F Y", strtotime($firstDayOfMonth)); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="p-4">
            <!-- Navigation -->
            <div class="nav-buttons">
                <a href="?month=<?php echo $month-1; ?>&year=<?php echo $year; ?>" class="nav-btn">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
                <a href="?month=<?php echo $month+1; ?>&year=<?php echo $year; ?>" class="nav-btn">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            
            <div class="month-title">
                <?php echo date("F Y", strtotime($firstDayOfMonth)); ?>
            </div>
            
            <!-- Calendar Grid -->
            <div class="table-responsive">
                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                        <?php
                        $currentDay = 1;
                        $cellCount = 0;
                        // Empty cells before first day
                        for ($i = 0; $i < $startDayOfWeek; $i++) {
                            echo "<td class='bg-light' style='background:#e9ecef !important'></td>";
                            $cellCount++;
                        }
                        // Fill days
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $dateKey = date("Y-m-d", strtotime("$year-$month-$day"));
                            $isToday = ($dateKey == $today);
                            $dayEvents = isset($eventsByDate[$dateKey]) ? $eventsByDate[$dateKey] : [];
                            $eventCount = count($dayEvents);
                            
                            echo "<td class='" . ($isToday ? "today" : "") . "' data-date='$dateKey'>";
                            echo "<div class='day-number'>$day</div>";
                            
                            // Show first 2 events as badges
                            if ($eventCount > 0) {
                                $displayEvents = array_slice($dayEvents, 0, 2);
                                foreach ($displayEvents as $ev) {
                                    $statusClass = $ev['status'];
                                    $statusText = ($statusClass == 'upcoming') ? 'Upcoming' : (($statusClass == 'ongoing') ? 'Live' : 'Completed');
                                    echo "<div class='event-badge $statusClass' onclick='showEventDetails(" . json_encode($ev) . ")'>";
                                    echo "<i class='fas fa-calendar-day me-1'></i> " . htmlspecialchars(substr($ev['name'], 0, 25));
                                    echo "</div>";
                                }
                                if ($eventCount > 2) {
                                    echo "<div class='event-badge' onclick='showAllEventsForDate(\"$dateKey\")'>+ $eventCount more</div>";
                                }
                                echo "<div class='event-count'><i class='fas fa-calendar-alt'></i> $eventCount</div>";
                            } else {
                                echo "<div style='height:50px'></div>";
                            }
                            echo "</td>";
                            $cellCount++;
                            // New row after 7 cells
                            if ($cellCount % 7 == 0 && $day != $daysInMonth) {
                                echo "</tr><tr>";
                            }
                        }
                        // Fill remaining cells
                        $remaining = (7 - ($cellCount % 7)) % 7;
                        for ($i = 0; $i < $remaining; $i++) {
                            echo "<td class='bg-light' style='background:#e9ecef !important'></td>";
                        }
                        ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Single Event Details -->
<div class="modal fade event-modal" id="eventDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i> Event Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventDetailBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Multiple Events on a Date -->
<div class="modal fade event-modal" id="multiEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-day me-2"></i> Events on <span id="modalDateLabel"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="multiEventBody">
                <!-- Dynamic list of events -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Embed PHP events data
    const eventsData = <?php echo json_encode($eventsByDate); ?>;
    
    function showEventDetails(event) {
        let statusText = '';
        let statusClass = '';
        switch(event.status) {
            case 'upcoming': statusText = 'Upcoming'; statusClass = 'primary'; break;
            case 'ongoing': statusText = 'Live'; statusClass = 'success'; break;
            case 'completed': statusText = 'Completed'; statusClass = 'secondary'; break;
        }
        const modalBody = document.getElementById('eventDetailBody');
        modalBody.innerHTML = `
            <div class="event-item">
                <h5><i class="fas fa-calendar-alt me-2"></i>${escapeHtml(event.name)}</h5>
                <p><strong>Type:</strong> ${escapeHtml(event.type)}</p>
                <p><strong>Department:</strong> ${escapeHtml(event.department)}</p>
                <p><strong>Start:</strong> ${event.start}</p>
                <p><strong>End:</strong> ${event.end}</p>
                <p><strong>Resource Person:</strong> ${escapeHtml(event.resource) || '—'}</p>
                <p><strong>Status:</strong> <span class="badge bg-${statusClass}">${statusText}</span></p>
                <a href="edit_event.php?id=${event.id}" class="btn btn-sm btn-primary mt-2"><i class="fas fa-edit"></i> Edit Event</a>
            </div>
        `;
        new bootstrap.Modal(document.getElementById('eventDetailModal')).show();
    }
    
    function showAllEventsForDate(date) {
        const events = eventsData[date] || [];
        document.getElementById('modalDateLabel').innerText = date;
        let html = '';
        events.forEach(ev => {
            let statusText = '', statusClass = '';
            switch(ev.status) {
                case 'upcoming': statusText = 'Upcoming'; statusClass = 'primary'; break;
                case 'ongoing': statusText = 'Live'; statusClass = 'success'; break;
                case 'completed': statusText = 'Completed'; statusClass = 'secondary'; break;
            }
            html += `
                <div class="event-item mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6><i class="fas fa-calendar-day me-2"></i>${escapeHtml(ev.name)}</h6>
                            <small>${ev.start} → ${ev.end}</small><br>
                            <small><strong>Type:</strong> ${escapeHtml(ev.type)} | <strong>Dept:</strong> ${escapeHtml(ev.department)}</small>
                        </div>
                        <span class="badge bg-${statusClass}">${statusText}</span>
                    </div>
                    <a href="edit_event.php?id=${ev.id}" class="btn btn-sm btn-link p-0 mt-1">Edit Event <i class="fas fa-arrow-right"></i></a>
                </div>
            `;
        });
        document.getElementById('multiEventBody').innerHTML = html;
        new bootstrap.Modal(document.getElementById('multiEventModal')).show();
    }
    
    function escapeHtml(str) {
        if(!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if(m === '&') return '&amp;';
            if(m === '<') return '&lt;';
            if(m === '>') return '&gt;';
            return m;
        });
    }
    
    // Make calendar cells clickable to show all events for that date
    document.querySelectorAll('.calendar-table td[data-date]').forEach(cell => {
        cell.addEventListener('click', function(e) {
            // Prevent if clicking on a badge (which already opens single event)
            if(e.target.classList.contains('event-badge')) return;
            const date = this.getAttribute('data-date');
            if(eventsData[date] && eventsData[date].length > 0) {
                showAllEventsForDate(date);
            }
        });
    });
</script>
</body>
</html>