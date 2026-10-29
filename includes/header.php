<?php
/**
 * Global App Header
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/functions.php';

// Check if user is logged in
$currentUser = current_user();
$theme = $currentUser['theme'] ?? 'light';
$currencySymbol = $currentUser['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;

// Run background checks for alerts if logged in
if ($currentUser) {
    check_emi_and_bill_alerts($currentUser['id']);
    check_budget_alerts($currentUser['id'], (int)date('m'), (int)date('Y'));
    
    $db = get_db();
    $stmtNotif = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtNotif->execute([$currentUser['id']]);
    $unreadNotifCount = (int)$stmtNotif->fetchColumn();
} else {
    $unreadNotifCount = 0;
}

$pageTitle = $pageTitle ?? APP_NAME;
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?></title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">

    <!-- PWA & Mobile WebApp Capabilities -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#FAF5EE">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SmartSpend">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome 6.5.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.min.css">

    <!-- Flatpickr Date Picker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">

    <!-- Custom Modern Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php if ($currentUser && !isset($hideAppShell)): ?>
    <div class="app-shell">
        <!-- Desktop Sidebar Navigation -->
        <aside class="app-sidebar d-none d-lg-flex">
            <!-- Brand -->
            <a href="dashboard.php" class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <img src="assets/img/default-avatar.svg" alt="Logo">
                </div>
                <div class="sidebar-brand-text">
                    <h4><?= APP_NAME ?></h4>
                    <small>Smart Finance Tracker</small>
                </div>
            </a>

            <!-- Nav Groups -->
            <div class="sidebar-nav-container">
                <div class="sidebar-nav-group-title">MAIN</div>
                <a href="dashboard.php" class="sidebar-nav-item <?= in_array($currentPage, ['dashboard.php', 'index.php']) ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-house"></i> Dashboard</span>
                </a>
                <a href="transactions.php" class="sidebar-nav-item <?= ($currentPage === 'transactions.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-receipt"></i> Transactions</span>
                </a>
                <a href="expenses.php" class="sidebar-nav-item <?= ($currentPage === 'expenses.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-arrow-down-long text-danger"></i> Expenses</span>
                </a>
                <a href="income.php" class="sidebar-nav-item <?= ($currentPage === 'income.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-arrow-up-long text-success"></i> Income</span>
                </a>

                <div class="sidebar-nav-group-title">FINANCE & PLANNING</div>
                <a href="emis.php" class="sidebar-nav-item <?= ($currentPage === 'emis.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-landmark text-purple"></i> EMI & Loans</span>
                </a>
                <a href="budgets.php" class="sidebar-nav-item <?= ($currentPage === 'budgets.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-wallet text-warning"></i> Budgets</span>
                </a>
                <a href="goals.php" class="sidebar-nav-item <?= ($currentPage === 'goals.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-bullseye text-success"></i> Savings Goals</span>
                </a>
                <a href="recurring.php" class="sidebar-nav-item <?= ($currentPage === 'recurring.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-arrows-rotate text-info"></i> Recurring Bills</span>
                </a>
                <a href="calendar.php" class="sidebar-nav-item <?= ($currentPage === 'calendar.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-calendar-days text-warning-emphasis"></i> Finance Calendar</span>
                </a>

                <div class="sidebar-nav-group-title">INSIGHTS & TOOLS</div>
                <a href="reports.php" class="sidebar-nav-item <?= ($currentPage === 'reports.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-chart-pie"></i> Stats & Analytics</span>
                </a>
                <a href="categories.php" class="sidebar-nav-item <?= ($currentPage === 'categories.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-tags"></i> Categories</span>
                </a>
                <a href="notifications.php" class="sidebar-nav-item <?= ($currentPage === 'notifications.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-bell"></i> Notifications</span>
                    <?php if ($unreadNotifCount > 0): ?>
                        <span class="badge rounded-pill bg-danger" style="font-size: 0.65rem;"><?= $unreadNotifCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="settings.php" class="sidebar-nav-item <?= ($currentPage === 'settings.php') ? 'active' : '' ?>">
                    <span><i class="fa-solid fa-gear"></i> Settings</span>
                </a>
            </div>

            <!-- User Footer in Sidebar -->
            <div class="sidebar-user-card">
                <a href="settings.php" class="sidebar-user-info">
                    <img src="<?= !empty($currentUser['avatar']) ? htmlspecialchars($currentUser['avatar']) : 'assets/img/default-avatar.svg' ?>" class="sidebar-user-avatar" alt="Avatar">
                    <div class="text-truncate" style="max-width: 140px;">
                        <div class="fw-bold small text-dark text-truncate"><?= htmlspecialchars($currentUser['name']) ?></div>
                        <small class="text-muted text-truncate d-block" style="font-size: 0.72rem;"><?= htmlspecialchars($currentUser['email']) ?></small>
                    </div>
                </a>
                <a href="logout.php" class="btn btn-sm btn-light border-0 text-danger rounded-circle" title="Logout" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="app-main-content">
            <!-- Top App Bar -->
            <header class="app-topbar">
                <div class="topbar-left">
                    <!-- Mobile Hamburger Button -->
                    <button class="btn-icon-circle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavOffcanvas" aria-controls="mobileNavOffcanvas">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <!-- Header Greeting -->
                    <div class="topbar-greeting">
                        <h2>Hi, <?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?>! <span class="wave-hand">👋</span></h2>
                        <p class="d-none d-sm-block">Let's manage your money</p>
                    </div>
                </div>

                <div class="topbar-right">
                    <!-- Add Transaction Button -->
                    <button class="btn btn-primary-smart btn-sm d-none d-sm-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                        <i class="fa-solid fa-plus"></i> Add Transaction
                    </button>

                    <!-- Notifications Bell -->
                    <a href="notifications.php" class="btn-icon-circle position-relative text-decoration-none" title="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        <?php if ($unreadNotifCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                                <?= $unreadNotifCount ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- User Avatar Button -->
                    <a href="settings.php" class="btn-icon-circle p-0 overflow-hidden" title="Profile & Settings" style="border: 2px solid #FFE4D1;">
                        <img src="<?= !empty($currentUser['avatar']) ? htmlspecialchars($currentUser['avatar']) : 'assets/img/default-avatar.svg' ?>" width="38" height="38" alt="Avatar" style="object-fit: cover;">
                    </a>
                </div>
            </header>

            <!-- Page Body Content Starts Here -->
            <main class="app-content-body">
<?php endif; ?>
