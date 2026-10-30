<?php
/**
 * SmartSpend - Registration Screen
 * Seamlessly Blended 100VH Zero-Scroll Layout matching Reference Design
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

/* Page Outer Canvas */
.register-page-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
    height: 100dvh;
    max-height: 100dvh;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #FAF5EE;
}

/* Main Container Frame */
.register-container {
    position: relative;
    width: 100%;
    max-width: 460px;
    height: 100%;
    height: 100dvh;
    max-height: 100dvh;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    box-sizing: border-box;
    background: linear-gradient(135deg, #FFFFFF 0%, #F5FBF8 35%, #E2F6ED 75%, #CDF0DF 100%);
}

/* Desktop Polished Shell */
@media (min-width: 521px) {
    body {
        background: linear-gradient(135deg, #E6F5EE 0%, #D8EDE3 50%, #FAF5EE 100%) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .register-container {
        height: 94vh;
        max-height: 870px;
        border-radius: 36px;
        box-shadow: 0 25px 65px rgba(15, 31, 56, 0.12), 0 0 0 1px rgba(255, 255, 255, 0.8) inset;
    }
}

/* ========================================================
   Seamless Background Layer (Integrated 3D Wallet & Mint Waves)
   ======================================================== */
.register-hero-bg {
    position: absolute;
    top: 0;
    right: 0;
    width: clamp(200px, 54%, 270px);
    height: clamp(230px, 34vh, 320px);
    background-image: url('assets/img/register-bg-wallet.png');
    background-repeat: no-repeat;
    background-position: top right;
    background-size: contain;
    pointer-events: none;
    z-index: 1;
}

/* Ambient bottom-left wave accent */
.register-bottom-ambient-curve {
    position: absolute;
    bottom: -30px;
    left: -30px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(167, 243, 208, 0.45) 0%, transparent 70%);
    pointer-events: none;
    z-index: 1;
}

/* ========================================================
   Top Content Section (Brand & Heading)
   ======================================================== */
.register-top-section {
    position: relative;
    z-index: 2;
    padding: calc(env(safe-area-inset-top, 0px) + 20px) 24px 10px 24px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    flex-shrink: 0;
}

.brand-row {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    margin-bottom: clamp(14px, 2.5vh, 26px);
    text-decoration: none;
}

.brand-logo-img {
    width: 38px;
    height: 38px;
    object-fit: contain;
    flex-shrink: 0;
    border-radius: 10px;
}

.brand-name-group {
    display: flex;
    flex-direction: column;
}

.brand-title {
    font-size: 1.3rem;
    font-weight: 800;
    color: #0F1F38;
    line-height: 1.1;
    letter-spacing: -0.3px;
}

.brand-title-accent {
    color: #059669;
}

.brand-subtitle {
    font-size: 0.72rem;
    font-weight: 500;
    color: #64748B;
    line-height: 1.25;
    white-space: nowrap;
}

.heading-group {
    max-width: 62%;
    margin-top: clamp(4px, 1vh, 10px);
}

.register-title {
    font-size: clamp(1.65rem, 5.2vw, 2.15rem);
    font-weight: 800;
    color: #0F1F38;
    line-height: 1.15;
    letter-spacing: -0.6px;
    margin-bottom: 6px;
}

.register-subtitle {
    font-size: clamp(0.78rem, 2.5vw, 0.88rem);
    color: #475569;
    line-height: 1.4;
    margin-bottom: 0;
    font-weight: 450;
}

/* ========================================================
   Bottom White Card Section (Form, Inputs, Footer)
   ======================================================== */
.register-card {
    position: relative;
    z-index: 3;
    background: #FFFFFF;
    border-radius: 36px 36px 0 0;
    box-shadow: 0 -12px 36px rgba(15, 31, 56, 0.08);
    padding: clamp(18px, 2.6vh, 26px) 24px calc(env(safe-area-inset-bottom, 0px) + 18px) 24px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    flex: 1 1 auto;
}

@media (min-width: 521px) {
    .register-card {
        border-radius: 32px;
        margin: 0 16px 16px 16px;
        box-shadow: 0 10px 30px rgba(15, 31, 56, 0.06);
        padding: 22px 24px 20px 24px;
    }
}

/* Form Container */
#registerForm {
    display: flex;
    flex-direction: column;
    gap: clamp(9px, 1.4vh, 14px);
}

/* Form Field Groups */
.form-field-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.field-label {
    display: block;
    font-size: 0.84rem;
    font-weight: 600;
    color: #1E293B;
    margin-bottom: 0;
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
    padding: 0 16px;
    transition: all 0.2s ease;
}

.input-container:focus-within {
    border-color: #059669;
    box-shadow: 0 0 0 3.5px rgba(5, 150, 105, 0.12);
}

.input-icon {
    color: #94A3B8;
    font-size: 0.95rem;
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
    font-size: 0.92rem;
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

/* Password Strength Meter Row */
.password-meter-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1px 2px 0 2px;
    font-size: 0.76rem;
}

.meter-bars {
    display: flex;
    align-items: center;
    gap: 6px;
    width: 130px;
}

.meter-bar {
    flex: 1;
    height: 4px;
    border-radius: 9999px;
    background-color: #E2E8F0;
    transition: background-color 0.25s ease;
}

.meter-bar.active-weak {
    background-color: #EF4444;
}

.meter-bar.active-medium {
    background-color: #F59E0B;
}

.meter-bar.active-strong {
    background-color: #059669;
}

.meter-text {
    color: #64748B;
    font-weight: 500;
    font-size: 0.74rem;
}

/* Create Account CTA Button */
.btn-register-primary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    height: clamp(48px, 5.6vh, 52px);
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: #FFFFFF !important;
    font-size: 1.02rem;
    font-weight: 700;
    border: none;
    border-radius: 9999px;
    box-shadow: 0 10px 24px rgba(5, 150, 105, 0.3);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    user-select: none;
    margin-top: 4px;
}

.btn-register-primary:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    box-shadow: 0 12px 28px rgba(5, 150, 105, 0.4);
    transform: translateY(-1.5px);
}

.btn-register-primary:active {
    transform: scale(0.98);
    box-shadow: 0 6px 14px rgba(5, 150, 105, 0.25);
}

.btn-arrow {
    transition: transform 0.2s ease;
}

.btn-register-primary:hover .btn-arrow {
    transform: translateX(3px);
}

/* OR Divider */
.or-divider-row {
    display: flex;
    align-items: center;
    gap: 14px;
    margin: clamp(6px, 1.2vh, 12px) 0;
}

.or-line {
    flex: 1;
    height: 1px;
    background: #E2E8F0;
}

.or-text {
    font-size: 0.74rem;
    font-weight: 600;
    color: #94A3B8;
    letter-spacing: 0.5px;
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

.login-link {
    color: #059669;
    font-weight: 700;
    text-decoration: none;
    transition: color 0.15s ease;
}

.login-link:hover {
    color: #047857;
    text-decoration: underline;
}

/* ========================================================
   Compact Screen (< 680px Height) Tuning
   ======================================================== */
@media (max-height: 680px) {
    .register-top-section {
        padding-top: calc(env(safe-area-inset-top, 0px) + 10px);
        padding-bottom: 4px;
    }

    .brand-row {
        margin-bottom: 8px;
    }

    .register-hero-bg {
        width: 170px;
        height: 200px;
    }

    .register-title {
        font-size: 1.45rem;
        margin-bottom: 3px;
    }

    .register-subtitle {
        font-size: 0.74rem;
    }

    .register-card {
        padding: 14px 18px 12px 18px;
    }

    #registerForm {
        gap: 6px;
    }

    .input-container {
        height: 40px;
    }

    .btn-register-primary {
        height: 42px;
    }

    .or-divider-row {
        margin: 4px 0;
    }
}
</style>

<div class="register-page-wrapper">
    <div class="register-container">
        <!-- Integrated Background Layer (3D Wallet & Mint Waves) -->
        <div class="register-hero-bg"></div>
        <div class="register-bottom-ambient-curve"></div>

        <!-- TOP SECTION: Brand & Headings -->
        <div class="register-top-section">
            <!-- Brand Row -->
            <div class="brand-row">
                <img src="assets/img/logo-symbol.png" class="brand-logo-img" alt="SmartSpend Logo">
                <div class="brand-name-group">
                    <span class="brand-title">Smart<span class="brand-title-accent">Spend</span></span>
                    <span class="brand-subtitle">Smart expense and loan management</span>
                </div>
            </div>

            <!-- Headings -->
            <div class="heading-group">
                <h1 class="register-title">Create your<br>Account</h1>
                <p class="register-subtitle">Start managing your personal finances smarter.</p>
            </div>
        </div>

        <!-- BOTTOM CARD SECTION: Rounded Card -->
        <div class="register-card">
            <form id="registerForm" autocomplete="on">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="register">

                <!-- Full Name -->
                <div class="form-field-group">
                    <label for="regName" class="field-label">Full Name</label>
                    <div class="input-container">
                        <span class="input-icon">
                            <i class="fa-regular fa-user"></i>
                        </span>
                        <input type="text" name="name" id="regName" class="clean-input" placeholder="e.g. Satya Sharma" required autofocus autocomplete="name">
                    </div>
                </div>

                <!-- Email Address -->
                <div class="form-field-group">
                    <label for="regEmail" class="field-label">Email Address</label>
                    <div class="input-container">
                        <span class="input-icon">
                            <i class="fa-regular fa-envelope"></i>
                        </span>
                        <input type="email" name="email" id="regEmail" class="clean-input" placeholder="name@example.com" required autocomplete="email">
                    </div>
                </div>

                <!-- Password -->
                <div class="form-field-group">
                    <label for="regPassword" class="field-label">Password</label>
                    <div class="input-container">
                        <span class="input-icon">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="regPassword" class="clean-input" placeholder="Minimum 6 characters" minlength="6" required autocomplete="new-password">
                        <button type="button" class="btn-toggle-password" id="togglePasswordBtn" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye-slash" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    <!-- Password Strength Meter -->
                    <div class="password-meter-row">
                        <div class="meter-bars">
                            <span class="meter-bar" id="meterBar1"></span>
                            <span class="meter-bar" id="meterBar2"></span>
                            <span class="meter-bar" id="meterBar3"></span>
                        </div>
                        <span class="meter-text" id="meterText">Use at least 6 characters</span>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-field-group">
                    <label for="regConfirmPassword" class="field-label">Confirm Password</label>
                    <div class="input-container">
                        <span class="input-icon">
                            <i class="fa-solid fa-shield-halved"></i>
                        </span>
                        <input type="password" name="confirm_password" id="regConfirmPassword" class="clean-input" placeholder="Re-type password" required autocomplete="new-password">
                        <button type="button" class="btn-toggle-password" id="toggleConfirmBtn" aria-label="Toggle confirm password visibility">
                            <i class="fa-regular fa-eye-slash" id="toggleConfirmIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Create Account CTA Button -->
                <button type="submit" id="regSubmitBtn" class="btn-register-primary">
                    <span id="btnText">Create Account</span>
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

            <!-- Footer: Already have an account? Log In -->
            <div class="card-footer-row">
                <span>Already have an account?</span>
                <a href="login.php" class="login-link">Log In</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('regSubmitBtn');
    const btnText = document.getElementById('btnText');
    const passwordInput = document.getElementById('regPassword');
    const confirmInput = document.getElementById('regConfirmPassword');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');
    const toggleConfirmBtn = document.getElementById('toggleConfirmBtn');
    const toggleConfirmIcon = document.getElementById('toggleConfirmIcon');

    // Password strength elements
    const meterBar1 = document.getElementById('meterBar1');
    const meterBar2 = document.getElementById('meterBar2');
    const meterBar3 = document.getElementById('meterBar3');
    const meterText = document.getElementById('meterText');

    // 1. Password Visibility Toggle
    if (togglePasswordBtn && passwordInput && togglePasswordIcon) {
        togglePasswordBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const isPass = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPass ? 'text' : 'password');
            togglePasswordIcon.className = isPass ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        });
    }

    if (toggleConfirmBtn && confirmInput && toggleConfirmIcon) {
        toggleConfirmBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const isPass = confirmInput.getAttribute('type') === 'password';
            confirmInput.setAttribute('type', isPass ? 'text' : 'password');
            toggleConfirmIcon.className = isPass ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        });
    }

    // 2. Real-time Password Strength Indicator
    if (passwordInput && meterBar1 && meterBar2 && meterBar3 && meterText) {
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            meterBar1.className = 'meter-bar';
            meterBar2.className = 'meter-bar';
            meterBar3.className = 'meter-bar';

            if (val.length === 0) {
                meterText.textContent = 'Use at least 6 characters';
                meterText.style.color = '#64748B';
            } else if (val.length < 6) {
                meterBar1.classList.add('active-weak');
                meterText.textContent = 'Too short';
                meterText.style.color = '#EF4444';
            } else if (val.length < 9 || !/[0-9]/.test(val) || !/[a-zA-Z]/.test(val)) {
                meterBar1.classList.add('active-medium');
                meterBar2.classList.add('active-medium');
                meterText.textContent = 'Good password';
                meterText.style.color = '#F59E0B';
            } else {
                meterBar1.classList.add('active-strong');
                meterBar2.classList.add('active-strong');
                meterBar3.classList.add('active-strong');
                meterText.textContent = 'Strong password';
                meterText.style.color = '#059669';
            }
        });
    }

    // 3. Form Submission with AJAX
    if (registerForm && submitBtn) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const pass = passwordInput.value;
            const confirmPass = confirmInput.value;

            if (pass.length < 6) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Password must be at least 6 characters long.');
                } else {
                    alert('Password must be at least 6 characters long.');
                }
                passwordInput.focus();
                return;
            }

            if (pass !== confirmPass) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Passwords do not match.');
                } else {
                    alert('Passwords do not match.');
                }
                confirmInput.focus();
                return;
            }

            submitBtn.disabled = true;
            btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating account...';

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
                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message || 'Account created successfully!');
                    }
                    setTimeout(() => {
                        window.location.href = data.redirect || 'dashboard.php';
                    }, 600);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.message || 'Registration failed.');
                    } else {
                        alert(data.message || 'Registration failed.');
                    }
                    submitBtn.disabled = false;
                    btnText.textContent = 'Create Account';
                }
            })
            .catch(err => {
                if (typeof toastr !== 'undefined') {
                    toastr.error('A network connection error occurred. Please try again.');
                } else {
                    alert('A network connection error occurred.');
                }
                submitBtn.disabled = false;
                btnText.textContent = 'Create Account';
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
