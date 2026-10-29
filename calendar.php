<?php
/**
 * Finance Calendar Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_auth();

$pageTitle = "Finance Calendar";
$user = current_user();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="dashboard.php" class="btn-icon-circle text-decoration-none">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">Finance Calendar</h3>
    </div>
</div>

<!-- Calendar Legend Pills -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <span class="badge rounded-pill" style="background: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2;">
        <i class="fa-solid fa-circle text-danger me-1" style="font-size: 0.55rem;"></i> Expense
    </span>
    <span class="badge rounded-pill" style="background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9;">
        <i class="fa-solid fa-circle text-success me-1" style="font-size: 0.55rem;"></i> Income
    </span>
    <span class="badge rounded-pill" style="background: #F3E8FF; color: #6B21A8; border: 1px solid #D8B4FE;">
        <i class="fa-solid fa-circle text-purple me-1" style="font-size: 0.55rem;"></i> EMI Due
    </span>
    <span class="badge rounded-pill" style="background: #FFF8E1; color: #E65100; border: 1px solid #FFE082;">
        <i class="fa-solid fa-circle text-warning me-1" style="font-size: 0.55rem;"></i> Recurring Bill
    </span>
</div>

<!-- Calendar Card Container -->
<div class="app-card p-3 mb-4 shadow-sm" style="border-radius: var(--radius-lg); background: #FFFFFF;">
    <div id="calendar"></div>
</div>

<!-- Calendar Event Detail Modal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="eventModalBody">
                <!-- Dynamically populated -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
/* FullCalendar customization */
.fc .fc-toolbar-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--text-primary);
}
.fc .fc-button-primary {
    background-color: var(--accent-orange);
    border-color: var(--accent-orange);
    border-radius: 20px;
    font-size: 0.8rem;
    padding: 0.3rem 0.8rem;
}
.fc .fc-button-primary:hover, .fc .fc-button-primary:active {
    background-color: var(--accent-orange-hover) !important;
    border-color: var(--accent-orange-hover) !important;
}
.fc-theme-standard td, .fc-theme-standard th {
    border-color: var(--border-light);
}
.fc-event {
    cursor: pointer;
    border-radius: 6px;
    padding: 1px 4px;
    font-size: 0.73rem;
    font-weight: 600;
}
.fc-day-today {
    background-color: #FFF9F2 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next',
            center: 'title',
            right: 'today'
        },
        height: 'auto',
        events: 'api/calendar.php',
        eventClick: function(info) {
            const ev = info.event;
            const props = ev.extendedProps;

            let html = `
                <div class="mb-3">
                    <span class="badge ${props.type === 'expense' ? 'bg-danger' : (props.type === 'income' ? 'bg-success' : 'bg-primary')} mb-2">
                        ${ev.title}
                    </span>
                    <h6 class="fw-bold mb-1">Date: ${ev.startStr}</h6>
                </div>
            `;

            if (props.notes) {
                html += `<p class="text-muted small"><strong>Notes:</strong> ${props.notes}</p>`;
            }
            if (props.payment_method) {
                html += `<p class="text-muted small"><strong>Method:</strong> ${props.payment_method}</p>`;
            }

            $('#eventModalTitle').text(ev.title);
            $('#eventModalBody').html(html);
            $('#eventDetailModal').modal('show');
        }
    });

    calendar.render();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

