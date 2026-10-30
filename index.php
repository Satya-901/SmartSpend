<?php
/**
 * SmartSpend - Welcome / Onboarding Landing Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect directly to dashboard
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>SmartSpend - Take Control of Your Finances</title>
    <meta name="description"
        content="Spend smart, save more, reach your goals with SmartSpend personal finance tracker.">

    <!-- PWA & Mobile WebApp Capabilities -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#FAF5EE">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SmartSpend">

    <!-- Bootstrap 5.3.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        /* Local Offline Font: Redaction */
        @font-face {
            font-family: 'Redaction';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('assets/fonts/Redaction-Regular.woff2') format('woff2'),
                 url('assets/fonts/Redaction-Regular.ttf') format('truetype');
        }

        @font-face {
            font-family: 'Redaction';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('assets/fonts/Redaction-Bold.woff2') format('woff2'),
                 url('assets/fonts/Redaction-Bold.ttf') format('truetype');
        }

        @font-face {
            font-family: 'Redaction';
            font-style: italic;
            font-weight: 400;
            font-display: swap;
            src: url('assets/fonts/Redaction-Italic.woff2') format('woff2'),
                 url('assets/fonts/Redaction-Italic.ttf') format('truetype');
        }

        /* Base resets & Strict 100VH / 100DVH constraints to eliminate scrolling */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
            height: 100dvh;
            max-height: 100dvh;
            overflow: hidden;
            background-color: #FAF5EE;
            font-family: 'Redaction', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            touch-action: manipulation;
        }

        /* Fullscreen Hero Background */
        .onboarding-screen {
            position: relative;
            height: 100%;
            height: 100dvh;
            max-height: 100dvh;
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            background-color: #FAF5EE;
            background-image: url('assets/img/hero-onboarding.png');
            background-size: cover;
            background-position: center bottom;
            background-repeat: no-repeat;
        }

        /* Subtle Light Gradient Overlay for pristine text readability */
        .onboarding-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg,
                    rgba(255, 255, 255, 0.94) 0%,
                    rgba(255, 255, 255, 0.82) 22%,
                    rgba(255, 255, 255, 0.28) 42%,
                    rgba(255, 255, 255, 0.04) 62%,
                    rgba(255, 255, 255, 0.55) 86%,
                    rgba(255, 255, 255, 0.92) 100%);
            pointer-events: none;
            z-index: 1;
        }

        /* Content layers */
        .onboarding-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: calc(env(safe-area-inset-top, 0px) + 26px) 28px calc(env(safe-area-inset-bottom, 0px) + 26px) 28px;
        }

        /* Brand Header */
        .brand-header {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .brand-icon-leaf {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
        }

        .brand-name {
            font-size: 1.45rem;
            font-weight: 800;
            color: #0F1F38;
            letter-spacing: -0.4px;
        }

        .brand-name-spend {
            color: #059669;
        }

        /* Hero Text */
        .hero-text-block {
            margin-top: 24px;
        }

        .hero-title {
            font-size: clamp(2rem, 7.5vw, 2.75rem);
            font-weight: 800;
            color: #0F1F38;
            line-height: 1.15;
            letter-spacing: -0.8px;
            margin-bottom: 14px;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 3.8vw, 1.25rem);
            font-weight: 500;
            color: #475569;
            line-height: 1.48;
            margin-bottom: 0;
        }

        /* Middle spacer to absorb screen height differences */
        .hero-middle-spacer {
            flex: 1 1 auto;
            min-height: 40px;
        }

        /* Bottom CTA Controls */
        .bottom-cta-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .btn-get-started {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            height: 56px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #FFFFFF !important;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 9999px;
            text-decoration: none;
            box-shadow: 0 10px 24px rgba(4, 120, 87, 0.35);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            border: none;
            cursor: pointer;
            user-select: none;
        }

        .btn-get-started:hover {
            background: linear-gradient(135deg, #047857 0%, #065F46 100%);
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(4, 120, 87, 0.45);
        }

        .btn-get-started:active {
            transform: scale(0.975);
            box-shadow: 0 6px 16px rgba(4, 120, 87, 0.3);
        }

        .btn-arrow {
            transition: transform 0.2s ease;
        }

        .btn-get-started:hover .btn-arrow {
            transform: translateX(3px);
        }

        /* Already have an account row */
        .signin-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 14px;
            font-size: 0.94rem;
            color: #475569;
            font-weight: 500;
        }

        .signin-link {
            color: #047857;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .signin-link:hover {
            color: #065F46;
            text-decoration: underline;
        }

        /* Desktop Container Polish */
        @media (min-width: 521px) {
            body {
                background: linear-gradient(135deg, #F3ECE1 0%, #E8DFD1 100%);
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .onboarding-screen {
                height: 94vh;
                max-height: 880px;
                border-radius: 36px;
                box-shadow: 0 25px 60px rgba(50, 35, 20, 0.2);
            }
        }
    </style>
</head>

<body>

    <main class="onboarding-screen">
        <!-- Soft Gradient Overlay -->
        <div class="onboarding-overlay"></div>

        <div class="onboarding-content">
            <!-- Top Section: Brand & Headline -->
            <div class="top-section">
                <!-- Brand Logo (Emerald Twin-Leaf SVG + Wordmark) -->
                <div class="brand-header">
                    <svg class="brand-icon-leaf" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 23C7 15 13 7 23 6C23 14 18 23 7 23Z" fill="url(#leafGradientDark)" />
                        <path d="M17 25C17 18.5 21 12 28 11C28 17.5 24.5 25 17 25Z" fill="url(#leafGradientLight)" />
                        <defs>
                            <linearGradient id="leafGradientDark" x1="7" y1="6" x2="23" y2="23"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#059669" />
                                <stop offset="1" stop-color="#047857" />
                            </linearGradient>
                            <linearGradient id="leafGradientLight" x1="17" y1="11" x2="28" y2="25"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#34D399" />
                                <stop offset="1" stop-color="#10B981" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <span class="brand-name">Smart<span class="brand-name-spend">Spend</span></span>
                </div>

                <!-- Hero Heading & Subtitle -->
                <div class="hero-text-block">
                    <h1 class="hero-title">Take Control<br>of Your Finances</h1>
                    <p class="hero-subtitle">Spend smart, save more,<br>reach your goals.</p>
                </div>
            </div>

            <!-- Middle Flex Spacer to ensure 100vh without overflow -->
            <div class="hero-middle-spacer"></div>

            <!-- Bottom Section: CTAs -->
            <div class="bottom-cta-block">
                <a href="register.php" class="btn-get-started">
                    <span>Get Started</span>
                    <svg class="btn-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>

                <div class="signin-row">
                    <span>Already have an account?</span>
                    <a href="login.php" class="signin-link">Sign In</a>
                </div>
            </div>
        </div>
    </main>

</body>

</html>