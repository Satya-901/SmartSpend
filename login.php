<?php
/**
 * Login Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$pageTitle = "Login";
$hideAppShell = true;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container d-flex flex-column justify-content-center align-items-center min-vh-100 py-4" style="max-width: 440px;">
    <!-- Brand Header -->
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 76px; height: 76px; border-radius: 24px; background: #FFF3E6; box-shadow: var(--shadow-card);">
            <img src="assets/img/default-avatar.svg" width="56" height="56" alt="Logo">
        </div>
        <h3 class="fw-bold mb-1" style="color: var(--text-primary); letter-spacing: -0.4px;"><?= APP_NAME ?></h3>
        <p class="text-muted small">Personal Expense & Loan Management</p>
    </div>

    <!-- Login Card -->
    <div class="app-card border-0 p-4 w-100 shadow-sm" style="border-radius: 28px;">
        <h5 class="fw-bold mb-1">Welcome back! 👋</h5>
        <p class="text-muted small mb-4">Sign in to your account</p>

        <form id="loginForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="login">

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 14px 0 0 14px; border-color: var(--border-soft);">
                        <i class="fa-regular fa-envelope"></i>
                    </span>
                    <input type="email" name="email" id="loginEmail" class="form-control border-start-0 ps-0" placeholder="name@example.com" required autofocus style="border-radius: 0 14px 14px 0;">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 14px 0 0 14px; border-color: var(--border-soft);">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" id="loginPassword" class="form-control border-start-0 ps-0" placeholder="••••••••" required style="border-radius: 0 14px 14px 0;">
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" id="loginSubmitBtn" class="btn btn-primary-smart py-2 fs-6">
                    Sign In
                </button>
            </div>
        </form>

        <div class="text-center mt-3 pt-3 border-top" style="border-color: var(--border-soft) !important;">
            <span class="text-muted small">Don't have an account?</span>
            <a href="register.php" class="fw-bold text-decoration-none ms-1" style="color: var(--accent-amber);">Create Account</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('loginSubmitBtn');

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Signing in...';

        const formData = new FormData(loginForm);

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
                }, 600);
            } else {
                toastr.error(data.message || 'Login failed.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Sign In';
            }
        })
        .catch(err => {
            toastr.error('A network error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Sign In';
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
