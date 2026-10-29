<?php
/**
 * Settings & Preferences Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Settings & Profile";
$user = current_user();
$userId = $user['id'];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="dashboard.php" class="btn-icon-circle text-decoration-none">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">Settings</h3>
    </div>
    <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3" style="font-weight: 700;">
        <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Logout
    </a>
</div>

<div class="row g-4">
    <!-- Left Column: Profile & Preferences -->
    <div class="col-lg-6">
        <!-- Profile Card -->
        <div class="app-card mb-4 p-4">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-user-circle me-2 text-warning"></i> Profile Information</h6>
            
            <form id="profileForm" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="user-avatar-btn" style="width: 64px; height: 64px; border-width: 2px;">
                        <img id="avatarPreview" src="<?= !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'assets/img/default-avatar.svg' ?>" alt="Avatar">
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label mb-1">Change Avatar</label>
                        <input type="file" name="avatar" id="avatarInput" class="form-control form-control-sm" accept="image/*">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    <small class="text-muted" style="font-size: 0.72rem;">Email address cannot be changed.</small>
                </div>

                <button type="submit" id="saveProfileBtn" class="btn btn-primary-smart btn-sm px-4">
                    Save Profile
                </button>
            </form>
        </div>

        <!-- Preferences Card -->
        <div class="app-card mb-4 p-4">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-sliders me-2 text-warning"></i> Preferences</h6>

            <form id="preferencesForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_preferences">

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Currency Symbol</label>
                        <select name="currency_symbol" class="form-select">
                            <option value="₹" <?= ($user['currency_symbol'] === '₹') ? 'selected' : '' ?>>₹ (INR Indian Rupee)</option>
                            <option value="$" <?= ($user['currency_symbol'] === '$') ? 'selected' : '' ?>>$ (USD US Dollar)</option>
                            <option value="€" <?= ($user['currency_symbol'] === '€') ? 'selected' : '' ?>>€ (EUR Euro)</option>
                            <option value="£" <?= ($user['currency_symbol'] === '£') ? 'selected' : '' ?>>£ (GBP British Pound)</option>
                            <option value="AED " <?= ($user['currency_symbol'] === 'AED ') ? 'selected' : '' ?>>AED (UAE Dirham)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Date Format</label>
                        <select name="date_format" class="form-select">
                            <option value="d M Y" <?= ($user['date_format'] === 'd M Y') ? 'selected' : '' ?>>29 Oct 2026</option>
                            <option value="Y-m-d" <?= ($user['date_format'] === 'Y-m-d') ? 'selected' : '' ?>>2026-10-29</option>
                            <option value="m/d/Y" <?= ($user['date_format'] === 'm/d/Y') ? 'selected' : '' ?>>10/29/2026</option>
                            <option value="d/m/Y" <?= ($user['date_format'] === 'd/m/Y') ? 'selected' : '' ?>>29/10/2026</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">App Theme</label>
                    <select name="theme" class="form-select">
                        <option value="light" <?= ($user['theme'] === 'light') ? 'selected' : '' ?>>Light Warm Cream (Default)</option>
                        <option value="dark" <?= ($user['theme'] === 'dark') ? 'selected' : '' ?>>Dark Mode</option>
                    </select>
                </div>

                <button type="submit" id="savePrefBtn" class="btn btn-primary-smart btn-sm px-4">
                    Save Preferences
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Security & Export -->
    <div class="col-lg-6">
        <!-- Password Change Card -->
        <div class="app-card mb-4 p-4">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-lock me-2 text-warning"></i> Security & Password</h6>

            <form id="passwordForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" minlength="6" placeholder="Min. 6 chars" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>

                <button type="submit" id="savePasswordBtn" class="btn btn-outline-secondary btn-sm px-4 rounded-pill fw-bold">
                    Update Password
                </button>
            </form>
        </div>

        <!-- Data Export Shortcuts -->
        <div class="app-card mb-4 p-4">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-file-export me-2 text-warning"></i> Export Financial Reports</h6>
            
            <div class="d-flex flex-column gap-2">
                <a href="export/csv.php?type=transactions" class="btn btn-light border d-flex justify-content-between align-items-center py-2 px-3 text-secondary text-decoration-none rounded-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-csv text-success fs-5"></i>
                        <div class="text-start">
                            <div class="fw-bold small text-dark">Download Transactions (CSV)</div>
                            <small class="text-muted">Compatible with Microsoft Excel & Google Sheets</small>
                        </div>
                    </div>
                    <i class="fa-solid fa-download text-muted"></i>
                </a>

                <a href="export/csv.php?type=emis" class="btn btn-light border d-flex justify-content-between align-items-center py-2 px-3 text-secondary text-decoration-none rounded-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-excel text-purple fs-5"></i>
                        <div class="text-start">
                            <div class="fw-bold small text-dark">Download EMI Records (CSV)</div>
                            <small class="text-muted">Complete breakdown of all loans & repayment status</small>
                        </div>
                    </div>
                    <i class="fa-solid fa-download text-muted"></i>
                </a>

                <a href="export/print_report.php" target="_blank" class="btn btn-light border d-flex justify-content-between align-items-center py-2 px-3 text-secondary text-decoration-none rounded-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-pdf text-danger fs-5"></i>
                        <div class="text-start">
                            <div class="fw-bold small text-dark">Printable Monthly Statement / PDF</div>
                            <small class="text-muted">Professional formatted report for records</small>
                        </div>
                    </div>
                    <i class="fa-solid fa-arrow-up-right-from-square text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#avatarInput').on('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#avatarPreview').attr('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    $('#profileForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#saveProfileBtn');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

        const formData = new FormData(this);

        $.ajax({
            url: 'api/auth.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(res.message);
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('Save Profile');
            }
        });
    });

    $('#preferencesForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#savePrefBtn');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

        const formData = new FormData(this);

        $.ajax({
            url: 'api/auth.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(res.message);
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('Save Preferences');
            }
        });
    });

    $('#passwordForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#savePasswordBtn');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...');

        const formData = new FormData(this);

        $.ajax({
            url: 'api/auth.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#passwordForm')[0].reset();
                } else {
                    toastr.error(res.message);
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('Update Password');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
