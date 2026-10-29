<?php
/**
 * Registration Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$pageTitle = "Create Account";
$hideAppShell = true;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container d-flex flex-column justify-content-center align-items-center min-vh-100 py-4" style="max-width: 440px;">
    <!-- Brand Header -->
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 76px; height: 76px; border-radius: 24px; background: #FFF3E6; box-shadow: var(--shadow-card);">
            <img src="assets/img/default-avatar.svg" width="56" height="56" alt="Logo">
        </div>
        <h3 class="fw-bold mb-1" style="color: var(--text-primary); letter-spacing: -0.4px;">Join <?= APP_NAME ?></h3>
        <p class="text-muted small">Smart expense and loan management</p>
    </div>

    <!-- Register Card -->
    <div class="app-card border-0 p-4 w-100 shadow-sm" style="border-radius: 28px;">
        <h5 class="fw-bold mb-1">Create your Account</h5>
        <p class="text-muted small mb-4">Start managing your personal finances</p>

        <form id="registerForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="register">

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 14px 0 0 14px; border-color: var(--border-soft);">
                        <i class="fa-regular fa-user"></i>
                    </span>
                    <input type="text" name="name" class="form-control border-start-0 ps-0" placeholder="e.g. Satya Sharma" required autofocus style="border-radius: 0 14px 14px 0;">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 14px 0 0 14px; border-color: var(--border-soft);">
                        <i class="fa-regular fa-envelope"></i>
                    </span>
                    <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="name@example.com" required style="border-radius: 0 14px 14px 0;">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 14px 0 0 14px; border-color: var(--border-soft);">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" id="regPassword" class="form-control border-start-0 ps-0" placeholder="Minimum 6 characters" minlength="6" required style="border-radius: 0 14px 14px 0;">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 14px 0 0 14px; border-color: var(--border-soft);">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>
                    <input type="password" name="confirm_password" id="regConfirmPassword" class="form-control border-start-0 ps-0" placeholder="Re-type password" required style="border-radius: 0 14px 14px 0;">
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" id="regSubmitBtn" class="btn btn-primary-smart py-2 fs-6">
                    Create Account
                </button>
            </div>
        </form>

        <div class="text-center mt-3 pt-3 border-top" style="border-color: var(--border-soft) !important;">
            <span class="text-muted small">Already have an account?</span>
            <a href="login.php" class="fw-bold text-decoration-none ms-1" style="color: var(--accent-amber);">Log In</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('regSubmitBtn');

    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const pass = document.getElementById('regPassword').value;
        const confirmPass = document.getElementById('regConfirmPassword').value;

        if (pass !== confirmPass) {
            toastr.error('Passwords do not match.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating account...';

        const formData = new FormData(registerForm);

        fetch('api/auth.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                setTimeout(() => {
                    window.location.href = data.redirect || 'dashboard.php';
                }, 800);
            } else {
                toastr.error(data.message || 'Registration failed.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Create Account';
            }
        })
        .catch(err => {
            toastr.error('A network error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Create Account';
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
