<?php
session_start();
include 'db.php';

$error = "";

if(isset($_SESSION['admin'])){
    header("Location: admin_dashboard.php");
    exit();
}

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admins WHERE username=? AND password=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1){
        $row = $result->fetch_assoc();
        $_SESSION['admin'] = $username;
        $_SESSION['department'] = $row['department'] ?? 'CSE';
        header("Location: admin_dashboard.php");
        exit();
    }
    else{
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | EventHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Existing login styles (unchanged) */
        * { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container { max-width: 450px; width: 100%; position: relative; }
        .login-card {
            background: white;
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 40px;
            text-align: center;
            color: white;
            position: relative;
        }
        .login-header h2 { font-weight: 800; font-size: 2rem; margin-bottom: 10px; }
        .login-header p { opacity: 0.8; font-size: 0.9rem; }
        .login-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
        }
        .login-body { padding: 40px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { font-weight: 600; color: #1a1a2e; margin-bottom: 8px; display: block; }
        .input-group {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            transition: all 0.3s;
        }
        .input-group:focus-within { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .input-group-text { background: transparent; border: none; color: #999; }
        .form-control { border: none; padding: 12px 0; font-size: 1rem; }
        .form-control:focus { box-shadow: none; }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 14px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
            width: 100%;
            color: white;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102,126,234,0.3); }
        .alert { border-radius: 15px; border: none; margin-bottom: 25px; }
        .credentials-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }
        .credentials-info p { margin: 5px 0; font-size: 0.85rem; color: #666; }
        .credentials-info strong { color: #667eea; }
        @media (max-width: 576px) {
            .login-header { padding: 30px; }
            .login-body { padding: 30px; }
            .login-header h2 { font-size: 1.5rem; }
        }

        /* Mini Calendar Popup Styles */
        .calendar-icon-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 1.3rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .calendar-icon-btn:hover {
            background: rgba(255,255,255,0.4);
            transform: scale(1.05);
        }
        .calendar-popup {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            margin-top: 15px;
            padding: 15px;
            z-index: 1000;
            display: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .calendar-popup.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        .mini-calendar {
            font-size: 0.8rem;
        }
        .mini-calendar .month-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .mini-calendar .month-nav button {
            background: #667eea;
            border: none;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            font-size: 0.8rem;
            cursor: pointer;
        }
        .mini-calendar .month-nav span {
            font-weight: 700;
            color: #1a1a2e;
        }
        .mini-calendar table {
            width: 100%;
            border-collapse: collapse;
        }
        .mini-calendar th {
            text-align: center;
            padding: 5px;
            color: #667eea;
            font-weight: 600;
        }
        .mini-calendar td {
            text-align: center;
            padding: 6px 2px;
            cursor: pointer;
            border-radius: 20px;
            transition: background 0.2s;
        }
        .mini-calendar td:hover {
            background: #f0f0f0;
        }
        .event-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin-left: 2px;
            vertical-align: middle;
        }
        .event-dot.upcoming { background: #0d6efd; }
        .event-dot.ongoing { background: #198754; }
        .event-dot.completed { background: #6c757d; }
        .loading-spinner {
            text-align: center;
            padding: 20px;
            color: #667eea;
        }
        /* Modal styles */
        .event-modal .modal-content {
            border-radius: 20px;
        }
        .event-modal .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px 20px 0 0;
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
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon"><i class="fas fa-calendar-alt"></i></div>
            <h2>Welcome Back</h2>
            <p>Event Management System</p>
            <button class="calendar-icon-btn" id="calendarToggleBtn">
                <i class="fas fa-calendar-day"></i>
            </button>
        </div>
        <div class="login-body">
            <?php if($error != ""): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user me-2"></i> Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock me-2"></i> Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                </div>
                <button type="submit" name="login" class="btn-login"><i class="fas fa-sign-in-alt me-2"></i> Login</button>
            </form>
            <div class="credentials-info">
                <p><i class="fas fa-info-circle"></i> Department Login Credentials</p>
                <p><strong>Username:</strong> cse / ise / aiml / ece / mech / civil &nbsp;|&nbsp; <strong>Password:</strong> department@example.com</p>
            </div>
        </div>
        <!-- Mini Calendar Popup -->
        <div class="calendar-popup" id="calendarPopup">
            <div id="miniCalendarContent">
                <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading events...</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for event details -->
<div class="modal fade event-modal" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-day me-2"></i> Events on <span id="modalDate"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalEventList">
                <!-- Events will be injected here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let eventsData = [];

    // Load events from calendar_event.php (existing endpoint)
    function loadEvents() {
        return $.getJSON('calendar_event.php').then(function(data){
            eventsData = data;
            return eventsData;
        });
    }

    // Get status class from color
    function getStatusFromColor(color) {
        if (color === '#28a745') return 'ongoing';
        if (color === '#007bff') return 'upcoming';
        return 'completed';
    }

    // Render mini calendar
    function renderCalendar(month, year) {
        const firstDay = new Date(year, month, 1);
        const startDayOfWeek = firstDay.getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        let html = `<div class="mini-calendar">
            <div class="month-nav">
                <button class="prev-month"><i class="fas fa-chevron-left"></i></button>
                <span>${firstDay.toLocaleString('default', { month: 'long' })} ${year}</span>
                <button class="next-month"><i class="fas fa-chevron-right"></i></button>
            </div>
            <table>
                <thead><tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr></thead>
                <tbody><tr>`;
        
        let day = 1;
        for (let i = 0; i < startDayOfWeek; i++) {
            html += '<td></td>';
        }
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const dayEvents = eventsData.filter(ev => {
                const evStart = ev.start;
                const evEnd = ev.end ? ev.end : ev.start;
                return dateStr >= evStart && dateStr <= evEnd;
            });
            let dots = '';
            let addedColors = [];
            dayEvents.forEach(ev => {
                let colorClass = getStatusFromColor(ev.color);
                if (!addedColors.includes(colorClass)) {
                    dots += `<span class="event-dot ${colorClass}"></span>`;
                    addedColors.push(colorClass);
                }
            });
            html += `<td data-date="${dateStr}">${d}${dots}</td>`;
            if ((startDayOfWeek + d) % 7 === 0 && d !== daysInMonth) {
                html += `</tr><tr>`;
            }
        }
        const remainingCells = (7 - ((startDayOfWeek + daysInMonth) % 7)) % 7;
        for (let i = 0; i < remainingCells; i++) {
            html += '<td></td>';
        }
        html += `</tr></tbody></table></div>`;
        
        $('#miniCalendarContent').html(html);
        
        // Month navigation
        $('.prev-month').off('click').on('click', function(){
            let newMonth = month - 1;
            let newYear = year;
            if (newMonth < 0) {
                newMonth = 11;
                newYear--;
            }
            renderCalendar(newMonth, newYear);
        });
        $('.next-month').off('click').on('click', function(){
            let newMonth = month + 1;
            let newYear = year;
            if (newMonth > 11) {
                newMonth = 0;
                newYear++;
            }
            renderCalendar(newMonth, newYear);
        });
        
        // Click on date: fetch events from get_events_by_date.php and show modal
        $('td[data-date]').off('click').on('click', function(e){
            e.stopPropagation();
            const date = $(this).data('date');
            $.getJSON('get_events_by_date.php', { date: date }, function(events){
                $('#modalDate').text(date);
                const modalBody = $('#modalEventList');
                if (events.length === 0) {
                    modalBody.html('<div class="alert alert-info">No events on this date.</div>');
                } else {
                    let html = '';
                    events.forEach(ev => {
                        let statusText = '', statusClass = '';
                        if (ev.status === 'upcoming') { statusText = 'Upcoming'; statusClass = 'primary'; }
                        else if (ev.status === 'ongoing') { statusText = 'Live'; statusClass = 'success'; }
                        else { statusText = 'Completed'; statusClass = 'secondary'; }
                        html += `
                            <div class="event-item">
                                <h6><i class="fas fa-calendar-alt me-2"></i>${escapeHtml(ev.event_name)}</h6>
                                <p><strong>Type:</strong> ${escapeHtml(ev.event_type)}</p>
                                <p><strong>Department:</strong> ${escapeHtml(ev.department)}</p>
                                <p><strong>Coordinator:</strong> ${escapeHtml(ev.coordinator) || '—'}</p>
                                <p><strong>Dates:</strong> ${ev.event_start_date} → ${ev.event_end_date}</p>
                                <p><strong>Resource Person:</strong> ${escapeHtml(ev.resource_person) || '—'}</p>
                                <p><strong>Remuneration:</strong> ₹${ev.remuneration}</p>
                                <p><strong>Status:</strong> <span class="badge bg-${statusClass}">${statusText}</span></p>
                                ${ev.event_description ? `<p><strong>Description:</strong> ${escapeHtml(ev.event_description)}</p>` : ''}
                            </div>
                        `;
                    });
                    modalBody.html(html);
                }
                const modal = new bootstrap.Modal(document.getElementById('eventModal'));
                modal.show();
            }).fail(function(){
                $('#modalEventList').html('<div class="alert alert-danger">Failed to load events.</div>');
                const modal = new bootstrap.Modal(document.getElementById('eventModal'));
                modal.show();
            });
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    // Popup toggle
    const $popup = $('#calendarPopup');
    const $toggleBtn = $('#calendarToggleBtn');
    
    function openPopup() {
        if ($popup.hasClass('show')) return;
        if (eventsData.length === 0) {
            $('#miniCalendarContent').html('<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading events...</div>');
            loadEvents().then(() => {
                renderCalendar(currentMonth, currentYear);
            });
        } else {
            renderCalendar(currentMonth, currentYear);
        }
        $popup.addClass('show');
    }
    function closePopup() { $popup.removeClass('show'); }
    function togglePopup() {
        if ($popup.hasClass('show')) closePopup();
        else openPopup();
    }
    
    $toggleBtn.on('click', function(e){
        e.stopPropagation();
        togglePopup();
    });
    $(document).on('click', function(e){
        if (!$(e.target).closest('.calendar-popup').length && !$(e.target).closest('#calendarToggleBtn').length) {
            closePopup();
        }
    });
    $popup.on('click', function(e){
        e.stopPropagation();
    });
});
</script>
</body>
</html>