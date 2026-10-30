<?php
/**
 * SmartSpend - Login Screen
 * Redesigned Modern 100VH Zero-Scroll Layout matching Reference Design
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

<style>
/* ========================================================
   Strict 100VH / 100DVH Constraints - Absolute Zero Scroll
   ======================================================== */
html,
body {
    height: 100% !important;
    height: 100dvh !important;
    max-height: 100dvh !important;
    overflow: hidden !important;
    margin: 0 !important;
    padding: 0 !important;
    background-color: #FAF5EE !important;
    font-family: 'Redaction', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, serif !important;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    touch-action: manipulation;
}

/* Page Outer Wrapper */
.login-page-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
    height: 100dvh;
    max-height: 100dvh;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle at 92% 10%, #CDEFE0 0%, #E3F6EE 28%, #F2FAF6 62%, #FAF5EE 100%);
}

/* Ambient Mint Organic Curves */
.ambient-bg-glow {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 1;
    background: radial-gradient(circle at 10% 95%, rgba(167, 243, 208, 0.35) 0%, transparent 45%),
                radial-gradient(circle at 85% 15%, rgba(52, 211, 153, 0.22) 0%, transparent 55%);
}

/* Main Mobile App Frame */
.login-container {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 480px;
    height: 100%;
    height: 100dvh;
    max-height: 100dvh;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    box-sizing: border-box;
}

/* Desktop Polished Shell */
@media (min-width: 521px) {
    body {
        background: linear-gradient(135deg, #E6F5EE 0%, #D8EDE3 50%, #FAF5EE 100%) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .login-container {
        height: 94vh;
        max-height: 860px;
        border-radius: 36px;
        background: radial-gradient(circle at 92% 10%, #CDEFE0 0%, #E3F6EE 28%, #F2FAF6 62%, #FAF5EE 100%);
        box-shadow: 0 25px 65px rgba(15, 31, 56, 0.12), 0 0 0 1px rgba(255, 255, 255, 0.6) inset;
    }
}

/* ========================================================
   Top Section: Brand, Welcome & 3D Isometric Visual
   ======================================================== */
.login-top-section {
    position: relative;
    z-index: 2;
    padding: calc(env(safe-area-inset-top, 0px) + 20px) 24px 10px 24px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-shrink: 0;
}

.brand-text-col {
    position: relative;
    z-index: 3;
    max-width: 64%;
}

.brand-row {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: clamp(10px, 1.8vh, 18px);
    text-decoration: none;
}

.brand-logo-img {
    width: 36px;
    height: 36px;
    object-fit: contain;
    flex-shrink: 0;
    border-radius: 10px;
}

.brand-name-group {
    display: flex;
    flex-direction: column;
}

.brand-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0F1F38;
    line-height: 1.1;
    letter-spacing: -0.3px;
}

.brand-title-accent {
    color: #059669;
}

.brand-subtitle {
    font-size: 0.68rem;
    font-weight: 500;
    color: #64748B;
    line-height: 1.25;
    white-space: nowrap;
}

.welcome-heading-group {
    margin-top: 4px;
}

.welcome-title {
    font-size: clamp(1.65rem, 5.2vw, 2.15rem);
    font-weight: 800;
    color: #0F1F38;
    line-height: 1.15;
    letter-spacing: -0.6px;
    margin-bottom: 6px;
}

.wave-emoji {
    display: inline-block;
    animation: waveHand 2.2s infinite;
    transform-origin: 70% 70%;
}

@keyframes waveHand {
    0%, 60%, 100% { transform: rotate(0deg); }
    10%, 30% { transform: rotate(14deg); }
    20% { transform: rotate(-8deg); }
    40% { transform: rotate(10deg); }
    50% { transform: rotate(-4deg); }
}

.welcome-subtitle {
    font-size: clamp(0.78rem, 2.6vw, 0.88rem);
    color: #475569;
    line-height: 1.4;
    margin-bottom: 0;
    font-weight: 450;
}

/* 3D Isometric Smartphone Visual */
.hero-3d-visual {
    position: absolute;
    right: -10px;
    top: 6px;
    width: clamp(140px, 38vw, 195px);
    pointer-events: none;
    z-index: 2;
    animation: floatPhone 5s ease-in-out infinite;
}

.img-3d-phone {
    width: 100%;
    height: auto;
    display: block;
    filter: drop-shadow(0 14px 28px rgba(5, 150, 105, 0.16));
    mask-image: radial-gradient(circle at 60% 50%, black 72%, transparent 100%);
    -webkit-mask-image: radial-gradient(circle at 60% 50%, black 72%, transparent 100%);
}

@keyframes floatPhone {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-6px) rotate(1deg); }
}

/* ========================================================
   Bottom White Card Section (Form, Social, Footer)
   ======================================================== */
.login-card {
    position: relative;
    z-index: 4;
    background: #FFFFFF;
    border-radius: 32px 32px 0 0;
    box-shadow: 0 -12px 36px rgba(15, 31, 56, 0.08);
    padding: clamp(16px, 2.5vh, 26px) 24px calc(env(safe-area-inset-bottom, 0px) + 18px) 24px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    flex: 1 1 auto;
    margin-top: 6px;
}

@media (min-width: 521px) {
    .login-card {
        border-radius: 30px;
        margin: 0 16px 16px 16px;
        box-shadow: 0 10px 30px rgba(15, 31, 56, 0.06);
        padding: 22px 24px 20px 24px;
    }
}

/* Form Fields */
.form-field-group {
    margin-bottom: clamp(8px, 1.6vh, 14px);
}

.field-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    color: #1E293B;
    margin-bottom: 5px;
    letter-spacing: -0.1px;
}

.input-container {
    position: relative;
    display: flex;
    align-items: center;
    height: clamp(44px, 5.2vh, 48px);
    background: #FFFFFF;
    border: 1.5px solid #E2E8F0;
    border-radius: 14px;
    padding: 0 14px;
    transition: all 0.2s ease;
}

.input-container:focus-within {
    border-color: #059669;
    box-shadow: 0 0 0 3.5px rgba(5, 150, 105, 0.12);
}

.input-icon {
    color: #94A3B8;
    font-size: 0.98rem;
    margin-right: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.clean-input {
    border: none;
    background: transparent;
    width: 100%;
    height: 100%;
    outline: none;
    color: #0F1F38;
    font-size: 0.94rem;
    font-weight: 500;
    font-family: inherit;
    padding: 0;
}

.clean-input::placeholder {
    color: #94A3B8;
    font-weight: 400;
}

.btn-toggle-password {
    border: none;
    background: transparent;
    color: #94A3B8;
    padding: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: color 0.15s ease;
    flex-shrink: 0;
}

.btn-toggle-password:hover {
    color: #059669;
}

/* Options Row: Remember Me & Forgot Password */
.options-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: clamp(10px, 1.8vh, 16px);
    font-size: 0.84rem;
}

.remember-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #475569;
    font-weight: 500;
    cursor: pointer;
    user-select: none;
    margin: 0;
}

.custom-checkbox {
    width: 18px;
    height: 18px;
    accent-color: #059669;
    cursor: pointer;
    border-radius: 5px;
}

.forgot-link {
    color: #059669;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.15s ease;
}

.forgot-link:hover {
    color: #047857;
    text-decoration: underline;
}

/* Sign In CTA Button */
.btn-signin-primary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    height: clamp(46px, 5.5vh, 52px);
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: #FFFFFF !important;
    font-size: 1.05rem;
    font-weight: 700;
    border: none;
    border-radius: 9999px;
    box-shadow: 0 10px 22px rgba(5, 150, 105, 0.32);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    user-select: none;
}

.btn-signin-primary:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    box-shadow: 0 12px 26px rgba(5, 150, 105, 0.42);
    transform: translateY(-1.5px);
}

.btn-signin-primary:active {
    transform: scale(0.98);
    box-shadow: 0 6px 14px rgba(5, 150, 105, 0.25);
}

.btn-arrow {
    transition: transform 0.2s ease;
}

.btn-signin-primary:hover .btn-arrow {
    transform: translateX(3px);
}

/* OR Divider */
.or-divider-row {
    display: flex;
    align-items: center;
    gap: 14px;
    margin: clamp(8px, 1.5vh, 14px) 0;
}

.or-line {
    flex: 1;
    height: 1px;
    background: #E2E8F0;
}

.or-text {
    font-size: 0.76rem;
    font-weight: 600;
    color: #94A3B8;
    letter-spacing: 0.5px;
}

/* Social Buttons */
.social-buttons-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: clamp(10px, 1.8vh, 16px);
}

.social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    height: clamp(42px, 5vh, 48px);
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.social-btn:hover {
    background: #FFFFFF;
    border-color: #CBD5E1;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 31, 56, 0.08);
}

.social-btn:active {
    transform: scale(0.96);
}

/* Card Footer */
.card-footer-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.88rem;
    color: #64748B;
    margin-top: 2px;
}

.create-account-link {
    color: #059669;
    font-weight: 700;
    text-decoration: none;
    transition: color 0.15s ease;
}

.create-account-link:hover {
    color: #047857;
    text-decoration: underline;
}

/* ========================================================
   Compact Screen (< 680px Height) Tuning
   ======================================================== */
@media (max-height: 680px) {
    .login-top-section {
        padding-top: calc(env(safe-area-inset-top, 0px) + 12px);
        padding-bottom: 4px;
    }

    .hero-3d-visual {
        width: 120px;
        top: 2px;
    }

    .welcome-title {
        font-size: 1.5rem;
    }

    .welcome-subtitle {
        font-size: 0.76rem;
    }

    .login-card {
        padding: 14px 20px 14px 20px;
    }

    .form-field-group {
        margin-bottom: 6px;
    }

    .input-container {
        height: 42px;
    }

    .options-row {
        margin-bottom: 8px;
    }

    .btn-signin-primary {
        height: 44px;
    }

    .or-divider-row {
        margin: 6px 0;
    }

    .social-btn {
        height: 40px;
    }
}
</style>

<div class="login-page-wrapper">
    <!-- Ambient Mint Glow -->
    <div class="ambient-bg-glow"></div>

    <div class="login-container">
        <!-- TOP SECTION: Brand, Welcome & 3D Illustration -->
        <div class="login-top-section">
            <div class="brand-text-col">
                <!-- Brand Row -->
                <div class="brand-row">
                    <img src="assets/img/logo-symbol.png" class="brand-logo-img" alt="SmartSpend Logo">
                    <div class="brand-name-group">
                        <span class="brand-title">Smart<span class="brand-title-accent">Spend</span></span>
                        <span class="brand-subtitle">Personal Expense & Loan Management</span>
                    </div>
                </div>

                <!-- Welcome Text -->
                <div class="welcome-heading-group">
                    <h1 class="welcome-title">Welcome<br>Back! <span class="wave-emoji">👋</span></h1>
                    <p class="welcome-subtitle">Sign in to continue managing your finances smarter.</p>
                </div>
            </div>

            <!-- 3D Smartphone Hero Visual -->
            <div class="hero-3d-visual">
                <img src="assets/img/login-hero-3d.png" alt="SmartSpend Financial Dashboard" class="img-3d-phone">
            </div>
        </div>

        <!-- BOTTOM CARD SECTION: Rounded Card -->
        <div class="login-card">
            <form id="loginForm" autocomplete="on">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="login">

                <!-- Email Address -->
                <div class="form-field-group">
                    <label for="loginEmail" class="field-label">Email Address</label>
                    <div class="input-container">
                        <span class="input-icon">
                            <i class="fa-regular fa-envelope"></i>
                        </span>
                        <input type="email" name="email" id="loginEmail" class="clean-input" placeholder="name@example.com" required autofocus autocomplete="email">
                    </div>
                </div>

                <!-- Password -->
                <div class="form-field-group">
                    <label for="loginPassword" class="field-label">Password</label>
                    <div class="input-container">
                        <span class="input-icon">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="loginPassword" class="clean-input" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="btn-toggle-password" id="togglePasswordBtn" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye-slash" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="options-row">
                    <label class="remember-label" for="rememberMe">
                        <input type="checkbox" id="rememberMe" name="remember" class="custom-checkbox" checked>
                        <span>Remember me</span>
                    </label>
                    <a href="javascript:void(0)" id="forgotPasswordLink" class="forgot-link">Forgot password?</a>
                </div>

                <!-- Sign In Button -->
                <button type="submit" id="loginSubmitBtn" class="btn-signin-primary">
                    <span id="btnText">Sign In</span>
                    <svg class="btn-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>

            <!-- OR Divider -->
            <div class="or-divider-row">
                <span class="or-line"></span>
                <span class="or-text">OR</span>
                <span class="or-line"></span>
            </div>

            <!-- Social Login Buttons -->
            <div class="social-buttons-row">
                <!-- Google -->
                <button type="button" class="social-btn social-auth-btn" data-provider="Google" title="Sign in with Google">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.8-2.4 3.66v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.15z"/>
                        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/>
                        <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.99 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                    </svg>
                </button>

                <!-- Apple -->
                <button type="button" class="social-btn social-auth-btn" data-provider="Apple" title="Sign in with Apple">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="#000000">
                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.89c.66-.82 1.11-1.96.99-3.1-.96.04-2.12.64-2.8 1.44-.6.69-1.12 1.84-1 2.96 1.07.08 2.15-.49 2.81-1.3"/>
                    </svg>
                </button>

                <!-- Facebook -->
                <button type="button" class="social-btn social-auth-btn" data-provider="Facebook" title="Sign in with Facebook">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </button>
            </div>

            <!-- Footer: Don't have an account? Create Account -->
            <div class="card-footer-row">
                <span>Don't have an account?</span>
                <a href="register.php" class="create-account-link">Create Account</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('loginSubmitBtn');
    const btnText = document.getElementById('btnText');
    const passwordInput = document.getElementById('loginPassword');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');

    // 1. Password Visibility Toggle
    if (togglePasswordBtn && passwordInput && togglePasswordIcon) {
        togglePasswordBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            togglePasswordIcon.className = isPassword ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        });
    }

    // 2. Forgot Password Alert
    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: 'Password Reset',
                    text: 'To reset your password, please contact the administrator or verify your registered email address.',
                    confirmButtonColor: '#059669',
                    customClass: {
                        popup: 'rounded-4'
                    }
                });
            } else if (typeof toastr !== 'undefined') {
                toastr.info('Please contact support to reset your account password.');
            } else {
                alert('Please contact support to reset your account password.');
            }
        });
    }

    // 3. Social Sign-in Placeholder Click
    document.querySelectorAll('.social-auth-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const provider = this.getAttribute('data-provider') || 'Social provider';
            if (typeof toastr !== 'undefined') {
                toastr.info(`Sign in with ${provider} will be available in the upcoming release.`);
            } else {
                alert(`Sign in with ${provider} will be available in the upcoming release.`);
            }
        });
    });

    // 4. Form Submission with AJAX
    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitBtn.disabled = true;
            btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Signing in...';

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
                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message || 'Login successful!');
                    }
                    setTimeout(() => {
                        window.location.href = data.redirect || 'dashboard.php';
                    }, 500);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.message || 'Invalid email or password.');
                    } else {
                        alert(data.message || 'Login failed.');
                    }
                    submitBtn.disabled = false;
                    btnText.textContent = 'Sign In';
                }
            })
            .catch(err => {
                if (typeof toastr !== 'undefined') {
                    toastr.error('A network connection error occurred. Please try again.');
                } else {
                    alert('A network connection error occurred.');
                }
                submitBtn.disabled = false;
                btnText.textContent = 'Sign In';
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
