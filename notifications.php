<?php
/**
 * Notifications & Reminders Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Notifications & Reminders";
$user = current_user();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="dashboard.php" class="btn-icon-circle text-decoration-none">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">Notifications</h3>
    </div>
    <button id="markAllReadBtn" class="btn btn-sm btn-light border rounded-pill px-3 text-secondary" style="font-size: 0.78rem; font-weight: 700;">
        <i class="fa-solid fa-check-double me-1"></i> Mark all read
    </button>
</div>

<!-- Notifications List -->
<div id="notificationsContainer">
    <div class="text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
        <span class="ms-2">Loading alerts...</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function loadNotifications() {
        const container = $('#notificationsContainer');
        container.html(`
            <div class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                <span class="ms-2">Loading alerts...</span>
            </div>
        `);

        $.getJSON('api/notifications.php', { action: 'list' }, function(res) {
            if (!res.success) {
                container.html(`<div class="alert alert-danger">${res.message}</div>`);
                return;
            }

            if (res.notifications.length === 0) {
                container.html(`
                    <div class="empty-state-box">
                        <div class="empty-state-icon" style="background: #FFF8E1; color: #FFA000;">
                            <i class="fa-regular fa-bell-slash"></i>
                        </div>
                        <h6>You're all caught up!</h6>
                        <p>No unread alerts or notifications right now</p>
                    </div>
                `);
                return;
            }

            let html = '<div class="d-flex flex-column gap-2">';
            for (const n of res.notifications) {
                let iconClass = 'fa-circle-info text-info';
                let iconBg = '#E1F5FE';

                if (n.type === 'danger') {
                    iconClass = 'fa-triangle-exclamation text-danger';
                    iconBg = '#FFEBEE';
                } else if (n.type === 'warning') {
                    iconClass = 'fa-clock text-warning';
                    iconBg = '#FFF3E0';
                } else if (n.type === 'success') {
                    iconClass = 'fa-circle-check text-success';
                    iconBg = '#E8F5E9';
                }

                const isUnread = (n.is_read == 0);
                const borderHighlight = isUnread ? 'border-start border-4 border-warning' : '';

                html += `
                    <div class="app-card p-3 mb-0 ${borderHighlight}" id="notif-${n.id}" style="${isUnread ? 'background: #FFFDF9;' : ''}">
                        <div class="d-flex align-items-start gap-3">
                            <div class="category-icon-box flex-shrink-0" style="background-color: ${iconBg}; width: 40px; height: 40px; font-size: 1.1rem;">
                                <i class="fa-solid ${iconClass}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-1 ${isUnread ? 'text-primary' : 'text-secondary'}">${escapeHtml(n.title)}</h6>
                                    <small class="text-muted" style="font-size: 0.72rem;">${n.time_ago}</small>
                                </div>
                                <p class="text-secondary small mb-2">${escapeHtml(n.message)}</p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    ${n.link ? `<a href="${n.link}" class="btn btn-sm btn-light border rounded-pill px-3 py-0" style="font-size: 0.75rem;">View Details</a>` : '<span></span>'}
                                    <div class="d-flex gap-2">
                                        ${isUnread ? `<button class="btn btn-link text-muted p-0 small mark-read-btn" data-id="${n.id}" title="Mark as read"><i class="fa-solid fa-check"></i></button>` : ''}
                                        <button class="btn btn-link text-danger p-0 small delete-notif-btn" data-id="${n.id}" title="Delete"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            html += '</div>';

            container.html(html);
        });
    }

    // Mark single as read
    $(document).on('click', '.mark-read-btn', function() {
        const id = $(this).data('id');
        $.post('api/notifications.php', { action: 'mark_read', id: id }, function(res) {
            if (res.success) {
                loadNotifications();
            }
        });
    });

    // Mark all as read
    $('#markAllReadBtn').on('click', function() {
        $.post('api/notifications.php', { action: 'mark_all_read' }, function(res) {
            if (res.success) {
                toastr.success(res.message);
                loadNotifications();
            }
        });
    });

    // Delete notification
    $(document).on('click', '.delete-notif-btn', function() {
        const id = $(this).data('id');
        $.post('api/notifications.php', { action: 'delete', id: id }, function(res) {
            if (res.success) {
                $(`#notif-${id}`).slideUp(200, function() { $(this).remove(); });
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"'`=\/]/g, function (s) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;',
                '`': '&#x60;',
                '=': '&#x3D;'
            }[s];
        });
    }

    loadNotifications();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
