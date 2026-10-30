<?php
/**
 * Mobile Navigation (Bottom Nav Bar & Mobile Drawer)
 * 5-Tab Navigation matching Reference Design: Home, Analytics, Budget, Goals, Profile
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Bottom Navigation Bar (Visible on mobile/tablet < 992px) -->
<div class="bottom-nav-container d-lg-none">
    <nav class="bottom-nav-bar">
        <!-- 1. Home -->
        <a href="dashboard.php" class="nav-item-btn <?= in_array($currentPage, ['dashboard.php', 'index.php']) ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i>
            <span>Home</span>
        </a>

        <!-- 2. Analytics -->
        <a href="reports.php" class="nav-item-btn <?= ($currentPage === 'reports.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-simple"></i>
            <span>Analytics</span>
        </a>

        <!-- 3. Budget -->
        <a href="budgets.php" class="nav-item-btn <?= ($currentPage === 'budgets.php') ? 'active' : '' ?>">
            <i class="fa-regular fa-file-lines"></i>
            <span>Budget</span>
        </a>

        <!-- 4. Goals -->
        <a href="goals.php" class="nav-item-btn <?= ($currentPage === 'goals.php') ? 'active' : '' ?>">
            <i class="fa-regular fa-heart"></i>
            <span>Goals</span>
        </a>

        <!-- 5. Profile -->
        <a href="settings.php" class="nav-item-btn <?= ($currentPage === 'settings.php') ? 'active' : '' ?>">
            <i class="fa-regular fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>
</div>

<!-- Mobile Offcanvas Menu (Left side drawer for secondary options) -->
<div class="offcanvas offcanvas-start rounded-end-4" tabindex="-1" id="mobileNavOffcanvas" style="width: 290px; border-top-right-radius: 26px !important; border-bottom-right-radius: 26px !important; background: var(--bg-sidebar);">
    <div class="offcanvas-header pb-2 pt-3 px-3 border-bottom" style="border-color: var(--border-soft) !important;">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= !empty($currentUser['avatar']) ? htmlspecialchars($currentUser['avatar']) : 'assets/img/default-avatar.svg' ?>" class="rounded-circle" width="46" height="46" alt="Avatar" style="background: #E8F8F0; border: 1.5px solid #A7F3D0; object-fit: cover;">
            <div class="text-truncate" style="max-width: 170px;">
                <h6 class="mb-0 fw-bold text-dark text-truncate"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></h6>
                <small class="text-muted text-truncate d-block" style="font-size: 0.72rem;"><?= htmlspecialchars($currentUser['email'] ?? '') ?></small>
            </div>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body px-2 py-3">
        <div class="sidebar-nav-group-title px-2">MAIN</div>
        <a href="dashboard.php" class="sidebar-nav-item <?= in_array($currentPage, ['dashboard.php', 'index.php']) ? 'active' : '' ?>">
            <span><i class="fa-solid fa-house"></i> Home</span>
        </a>
        <a href="reports.php" class="sidebar-nav-item <?= ($currentPage === 'reports.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-chart-simple text-success"></i> Analytics</span>
        </a>
        <a href="budgets.php" class="sidebar-nav-item <?= ($currentPage === 'budgets.php') ? 'active' : '' ?>">
            <span><i class="fa-regular fa-file-lines text-warning"></i> Budget</span>
        </a>
        <a href="goals.php" class="sidebar-nav-item <?= ($currentPage === 'goals.php') ? 'active' : '' ?>">
            <span><i class="fa-regular fa-heart text-danger"></i> Goals</span>
        </a>
        <a href="settings.php" class="sidebar-nav-item <?= ($currentPage === 'settings.php') ? 'active' : '' ?>">
            <span><i class="fa-regular fa-user text-primary"></i> Profile</span>
        </a>

        <div class="sidebar-nav-group-title px-2">TRANSACTIONS & CASH FLOW</div>
        <a href="transactions.php" class="sidebar-nav-item <?= ($currentPage === 'transactions.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-receipt"></i> All Transactions</span>
        </a>
        <a href="expenses.php" class="sidebar-nav-item <?= ($currentPage === 'expenses.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-arrow-down-long text-danger"></i> Expenses</span>
        </a>
        <a href="income.php" class="sidebar-nav-item <?= ($currentPage === 'income.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-arrow-up-long text-success"></i> Income</span>
        </a>

        <div class="sidebar-nav-group-title px-2">FINANCE & PLANNING</div>
        <a href="emis.php" class="sidebar-nav-item <?= ($currentPage === 'emis.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-landmark text-purple"></i> EMI & Loans</span>
        </a>
        <a href="recurring.php" class="sidebar-nav-item <?= ($currentPage === 'recurring.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-arrows-rotate text-info"></i> Recurring Bills</span>
        </a>
        <a href="calendar.php" class="sidebar-nav-item <?= ($currentPage === 'calendar.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-calendar-days text-warning-emphasis"></i> Finance Calendar</span>
        </a>

        <div class="sidebar-nav-group-title px-2">SETTINGS & ACCOUNT</div>
        <a href="categories.php" class="sidebar-nav-item <?= ($currentPage === 'categories.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-tags"></i> Categories</span>
        </a>
        <a href="notifications.php" class="sidebar-nav-item <?= ($currentPage === 'notifications.php') ? 'active' : '' ?>">
            <span><i class="fa-solid fa-bell"></i> Notifications</span>
            <?php if (!empty($unreadNotifCount) && $unreadNotifCount > 0): ?>
                <span class="badge rounded-pill bg-danger" style="font-size: 0.65rem;"><?= $unreadNotifCount ?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php" class="sidebar-nav-item text-danger">
            <span><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</span>
        </a>
    </div>
</div>
