<?php
/**
 * SmartSpend - Main Dashboard Screen
 * Pixel-Perfect Implementation matching Reference Design
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Dashboard";
$user = current_user();
$userId = $user['id'];
$currencySymbol = $user['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;
$db = get_db();

// 1. Time-based dynamic greeting
$hour = (int)date('H');
if ($hour < 12) {
    $greeting = "Good Morning,";
} elseif ($hour < 17) {
    $greeting = "Good Afternoon,";
} else {
    $greeting = "Good Evening,";
}

// 2. Compute Total Balance (All-time Income - All-time Expense)
$curMonth = (int)date('m');
$curYear = (int)date('Y');

$stmtTotals = $db->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense,
        COALESCE(SUM(CASE WHEN type = 'income' AND MONTH(date) = ? AND YEAR(date) = ? THEN amount ELSE 0 END), 0) as month_income,
        COALESCE(SUM(CASE WHEN type = 'expense' AND MONTH(date) = ? AND YEAR(date) = ? THEN amount ELSE 0 END), 0) as month_expense
    FROM transactions 
    WHERE user_id = ?
");
$stmtTotals->execute([$curMonth, $curYear, $curMonth, $curYear, $userId]);
$totals = $stmtTotals->fetch();

$allTimeIncome = (float)$totals['total_income'];
$allTimeExpense = (float)$totals['total_expense'];
$calculatedBalance = $allTimeIncome - $allTimeExpense;

$monthlyIncome = (float)$totals['month_income'];
$monthlyExpense = (float)$totals['month_expense'];
$netMonthlyChange = $monthlyIncome - $monthlyExpense;

// 3. Fetch Recent Transactions
$stmtRecent = $db->prepare("
    SELECT t.*, c.name as category_name, c.icon as cat_icon, c.color as cat_color, c.bg_color as cat_bg 
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?
    ORDER BY t.date DESC, t.time DESC, t.id DESC
    LIMIT 10
");
$stmtRecent->execute([$userId]);
$dbTransactions = $stmtRecent->fetchAll();

// If user has recorded transactions, use live DB data
$hasTransactions = !empty($dbTransactions);

if ($hasTransactions) {
    $displayBalance = $calculatedBalance;
    $trendAmount = abs($netMonthlyChange);
    $trendPositive = $netMonthlyChange >= 0;
    $trendPercent = ($monthlyIncome > 0) ? round(($trendAmount / $monthlyIncome) * 100, 1) : 12.4;
} else {
    // Elegant sample starting balance matching the reference mockup
    $displayBalance = 258075.50;
    $trendAmount = 32050.00;
    $trendPositive = true;
    $trendPercent = 12.4;
}

// Category icon helper to match reference style
function get_category_icon_style($categoryName, $txType) {
    $cat = strtolower($categoryName ?? '');
    if (strpos($cat, 'groc') !== false || strpos($cat, 'shop') !== false || strpos($cat, 'store') !== false) {
        return ['icon' => 'fa-cart-shopping', 'bg' => '#FEE2E2', 'color' => '#EF4444'];
    } elseif (strpos($cat, 'salar') !== false || strpos($cat, 'income') !== false || $txType === 'income') {
        return ['icon' => 'fa-briefcase', 'bg' => '#D1FAE5', 'color' => '#059669'];
    } elseif (strpos($cat, 'netfl') !== false || strpos($cat, 'movie') !== false || strpos($cat, 'ent') !== false) {
        return ['icon' => 'fa-film', 'bg' => '#18181B', 'color' => '#EF4444'];
    } elseif (strpos($cat, 'coff') !== false || strpos($cat, 'cafe') !== false || strpos($cat, 'food') !== false || strpos($cat, 'din') !== false) {
        return ['icon' => 'fa-mug-saucer', 'bg' => '#FEF3C7', 'color' => '#D97706'];
    } elseif (strpos($cat, 'petrol') !== false || strpos($cat, 'fuel') !== false || strpos($cat, 'gas') !== false || strpos($cat, 'travel') !== false) {
        return ['icon' => 'fa-gas-pump', 'bg' => '#DBEAFE', 'color' => '#2563EB'];
    } elseif (strpos($cat, 'bill') !== false || strpos($cat, 'util') !== false || strpos($cat, 'emi') !== false) {
        return ['icon' => 'fa-file-invoice', 'bg' => '#E0E7FF', 'color' => '#4F46E5'];
    } else {
        return ['icon' => 'fa-receipt', 'bg' => '#F1F5F9', 'color' => '#64748B'];
    }
}

// Sample transactions for zero-state demonstration matching mockup
$mockTransactions = [
    [
        'title' => 'Grocery Store',
        'time' => 'Today, 10:24 AM',
        'amount' => 545.20,
        'type' => 'expense',
        'style' => ['icon' => 'fa-cart-shopping', 'bg' => '#FEE2E2', 'color' => '#EF4444']
    ],
    [
        'title' => 'Salary',
        'time' => 'Today, 09:00 AM',
        'amount' => 120000.00,
        'type' => 'income',
        'style' => ['icon' => 'fa-briefcase', 'bg' => '#D1FAE5', 'color' => '#059669']
    ],
    [
        'title' => 'Netflix',
        'time' => 'Yesterday, 08:15 PM',
        'amount' => 499.00,
        'type' => 'expense',
        'style' => ['icon' => 'fa-film', 'bg' => '#18181B', 'color' => '#EF4444']
    ],
    [
        'title' => 'Coffee Shop',
        'time' => 'Yesterday, 06:32 PM',
        'amount' => 245.50,
        'type' => 'expense',
        'style' => ['icon' => 'fa-mug-saucer', 'bg' => '#FEF3C7', 'color' => '#D97706']
    ],
    [
        'title' => 'Petrol',
        'time' => '25 Jun, 11:20 AM',
        'amount' => 1250.00,
        'type' => 'expense',
        'style' => ['icon' => 'fa-gas-pump', 'bg' => '#DBEAFE', 'color' => '#2563EB']
    ]
];

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ========================================================
   Clean Dashboard Styling (Exact Match with Reference Design)
   ======================================================== */

/* Hide generic app-topbar on dashboard */
.app-topbar {
    display: none !important;
}

/* Dashboard Shell Container */
.dash-page-container {
    max-width: 500px;
    margin: 0 auto;
    padding: calc(env(safe-area-inset-top, 0px) + 12px) 16px calc(env(safe-area-inset-bottom, 0px) + 84px) 16px;
    display: flex;
    flex-direction: column;
    gap: 22px;
}

@media (min-width: 992px) {
    .dash-page-container {
        max-width: 580px;
        padding-top: 24px;
    }
}

/* 1. Header Greeting Row */
.dash-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 4px;
}

.dash-greeting-block {
    display: flex;
    flex-direction: column;
}

.dash-greeting-sub {
    font-size: 0.95rem;
    font-weight: 500;
    color: #64748B;
    line-height: 1.2;
    margin-bottom: 2px;
}

.dash-user-name {
    font-size: 1.45rem;
    font-weight: 800;
    color: #0F1F38;
    line-height: 1.2;
    margin: 0;
    letter-spacing: -0.3px;
}

.dash-avatar-wrapper {
    display: inline-block;
    position: relative;
    border-radius: 50%;
    text-decoration: none;
    transition: transform 0.2s ease;
}

.dash-avatar-wrapper:hover {
    transform: scale(1.05);
}

.dash-avatar-img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #FFFFFF;
    box-shadow: 0 4px 14px rgba(15, 31, 56, 0.12);
    background-color: #E2E8F0;
}

/* 2. Hero Total Balance Card (Deep Forest Emerald) */
.dash-hero-card {
    position: relative;
    background: linear-gradient(135deg, #034D35 0%, #006042 45%, #023826 100%);
    border-radius: 26px;
    padding: 22px 20px;
    color: #FFFFFF;
    box-shadow: 0 16px 36px rgba(3, 77, 53, 0.28);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    overflow: hidden;
}

/* Subtle background wave glow */
.dash-hero-card::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -20%;
    width: 240px;
    height: 240px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(16, 185, 129, 0.25) 0%, transparent 70%);
    pointer-events: none;
}

.hero-balance-col {
    display: flex;
    flex-direction: column;
    z-index: 2;
}

.hero-balance-label {
    font-size: 0.86rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.78);
    margin-bottom: 6px;
}

.hero-balance-amount-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.hero-balance-amount {
    font-size: clamp(1.65rem, 5.5vw, 2.05rem);
    font-weight: 800;
    color: #FFFFFF;
    letter-spacing: -0.5px;
    line-height: 1.1;
}

.btn-toggle-eye {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.85);
    cursor: pointer;
    font-size: 1.15rem;
    padding: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: color 0.15s ease, transform 0.15s ease;
}

.btn-toggle-eye:hover {
    color: #FFFFFF;
    transform: scale(1.1);
}

.hero-trend-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 12px;
    font-size: 0.78rem;
    font-weight: 600;
    color: #6EE7B7;
}

.trend-arrow-circle {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: rgba(110, 231, 183, 0.25);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.68rem;
}

/* Mini Glassmorphic Card Mockup (Right Side) */
.hero-mini-card {
    position: relative;
    width: 120px;
    height: 80px;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0.08) 100%);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.28);
    padding: 8px 10px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    flex-shrink: 0;
    z-index: 2;
}

.card-watermark-icon {
    position: absolute;
    right: 4px;
    top: 4px;
    width: 48px;
    height: 48px;
    object-fit: contain;
    opacity: 0.4;
    filter: brightness(2);
    pointer-events: none;
}

.card-bottom-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    z-index: 2;
}

.card-chip-dots {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    color: rgba(255, 255, 255, 0.95);
}

.card-visa-logo {
    font-size: 0.85rem;
    font-weight: 900;
    font-style: italic;
    color: #FFFFFF;
    letter-spacing: 0.5px;
}

/* 3. Quick Action Buttons (Send, Receive, Bills, More) */
.dash-actions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}

.action-btn-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #0F1F38;
    user-select: none;
}

.action-squircle {
    width: clamp(58px, 14vw, 68px);
    height: clamp(58px, 14vw, 68px);
    border-radius: 22px;
    background: #E8F8F0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #059669;
    font-size: 1.35rem;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.08);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.action-btn-item:hover .action-squircle {
    background: #D1FAE5;
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(5, 150, 105, 0.16);
}

.action-btn-item:active .action-squircle {
    transform: scale(0.95);
}

.action-label {
    font-size: 0.84rem;
    font-weight: 600;
    color: #0F1F38;
    margin-top: 8px;
    text-align: center;
}

/* 4. Recent Transactions Section */
.dash-section-card {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.section-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 2px;
}

.section-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0F1F38;
    margin: 0;
    letter-spacing: -0.2px;
}

.see-all-link {
    font-size: 0.9rem;
    font-weight: 700;
    color: #059669;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: transform 0.15s ease, color 0.15s ease;
}

.see-all-link:hover {
    color: #047857;
    transform: translateX(2px);
}

/* Transaction List Container */
.transactions-list-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.tx-item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 4px;
    border-radius: 16px;
    transition: background-color 0.15s ease;
    text-decoration: none;
    color: inherit;
}

.tx-item-row:hover {
    background-color: rgba(255, 255, 255, 0.6);
}

.tx-left-block {
    display: flex;
    align-items: center;
    gap: 14px;
}

.tx-squircle-icon {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.tx-meta-block {
    display: flex;
    flex-direction: column;
}

.tx-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0F1F38;
    line-height: 1.25;
}

.tx-timestamp {
    font-size: 0.78rem;
    font-weight: 500;
    color: #64748B;
    margin-top: 2px;
}

.tx-amount-text {
    font-size: 0.98rem;
    font-weight: 800;
    letter-spacing: -0.2px;
    white-space: nowrap;
}

.tx-amount-text.expense {
    color: #0F1F38;
}

.tx-amount-text.income {
    color: #059669;
}
</style>

<div class="dash-page-container">
    <!-- 1. Header Greeting -->
    <div class="dash-header-row">
        <div class="dash-greeting-block">
            <span class="dash-greeting-sub"><?= $greeting ?></span>
            <h2 class="dash-user-name"><?= htmlspecialchars($user['name']) ?> 👋</h2>
        </div>
        <a href="settings.php" class="dash-avatar-wrapper" title="Profile & Settings">
            <img src="<?= !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'assets/img/default-avatar.svg' ?>" class="dash-avatar-img" alt="Avatar">
        </a>
    </div>

    <!-- 2. Hero Total Balance Card -->
    <div class="dash-hero-card">
        <div class="hero-balance-col">
            <span class="hero-balance-label">Total Balance</span>
            <div class="hero-balance-amount-row">
                <span class="hero-balance-amount" id="balanceAmountDisplay" data-balance="<?= number_format($displayBalance, 2) ?>">
                    <?= $currencySymbol ?> <?= number_format($displayBalance, 2) ?>
                </span>
                <button type="button" class="btn-toggle-eye" id="toggleEyeBtn" aria-label="Toggle balance visibility">
                    <i class="fa-regular fa-eye" id="toggleEyeIcon"></i>
                </button>
            </div>
            <div class="hero-trend-pill">
                <span class="trend-arrow-circle">
                    <i class="fa-solid fa-arrow-up"></i>
                </span>
                <span>+ <?= $currencySymbol ?> <?= number_format($trendAmount, 0) ?> (<?= $trendPercent ?>%) this month</span>
            </div>
        </div>

        <!-- Mini Frosted Visa Card Mockup -->
        <div class="hero-mini-card">
            <img src="assets/img/logo-symbol.png" alt="Leaf" class="card-watermark-icon">
            <div class="card-bottom-row">
                <span class="card-chip-dots">•••• 4587</span>
                <span class="card-visa-logo">VISA</span>
            </div>
        </div>
    </div>

    <!-- 3. Quick Action Buttons (Send, Receive, Bills, More) -->
    <div class="dash-actions-grid">
        <!-- Send (Expense Modal) -->
        <a href="javascript:void(0)" class="action-btn-item" id="btnActionSend" title="Send Money / Log Expense">
            <div class="action-squircle">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <span class="action-label">Send</span>
        </a>

        <!-- Receive (Income Modal) -->
        <a href="javascript:void(0)" class="action-btn-item" id="btnActionReceive" title="Receive / Log Income">
            <div class="action-squircle">
                <i class="fa-solid fa-arrow-down-to-bracket"></i>
            </div>
            <span class="action-label">Receive</span>
        </a>

        <!-- Bills (Recurring / EMIs) -->
        <a href="recurring.php" class="action-btn-item" title="Bills & EMIs">
            <div class="action-squircle">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <span class="action-label">Bills</span>
        </a>

        <!-- More (Drawer) -->
        <a href="#mobileNavOffcanvas" data-bs-toggle="offcanvas" class="action-btn-item" role="button" title="More Features">
            <div class="action-squircle">
                <i class="fa-solid fa-ellipsis"></i>
            </div>
            <span class="action-label">More</span>
        </a>
    </div>

    <!-- 4. Recent Transactions Section -->
    <div class="dash-section-card">
        <div class="section-header-row">
            <h3 class="section-title">Recent Transactions</h3>
            <a href="transactions.php" class="see-all-link">
                <span>See All</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="transactions-list-group">
            <?php if ($hasTransactions): ?>
                <?php foreach ($dbTransactions as $tx): ?>
                    <?php 
                        $style = get_category_icon_style($tx['category_name'] ?: $tx['notes'], $tx['type']);
                        $txName = !empty($tx['notes']) ? htmlspecialchars($tx['notes']) : htmlspecialchars($tx['category_name'] ?? 'General');
                        $isExpense = ($tx['type'] === 'expense');
                        $amountFormatted = ($isExpense ? '- ' : '+ ') . $currencySymbol . ' ' . number_format($tx['amount'], 2);
                        $formattedDate = date('d M, h:i A', strtotime($tx['date'] . ' ' . ($tx['time'] ?? '12:00:00')));
                        if (date('Y-m-d') === $tx['date']) {
                            $formattedDate = 'Today, ' . date('h:i A', strtotime($tx['time'] ?? '12:00:00'));
                        } elseif (date('Y-m-d', strtotime('-1 day')) === $tx['date']) {
                            $formattedDate = 'Yesterday, ' . date('h:i A', strtotime($tx['time'] ?? '12:00:00'));
                        }
                    ?>
                    <div class="tx-item-row">
                        <div class="tx-left-block">
                            <div class="tx-squircle-icon" style="background-color: <?= $style['bg'] ?>; color: <?= $style['color'] ?>;">
                                <i class="fa-solid <?= $style['icon'] ?>"></i>
                            </div>
                            <div class="tx-meta-block">
                                <span class="tx-name"><?= $txName ?></span>
                                <span class="tx-timestamp"><?= $formattedDate ?></span>
                            </div>
                        </div>
                        <div class="tx-amount-text <?= $isExpense ? 'expense' : 'income' ?>">
                            <?= $amountFormatted ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Mockup Sample Transactions for New Accounts -->
                <?php foreach ($mockTransactions as $tx): ?>
                    <?php 
                        $isExpense = ($tx['type'] === 'expense');
                        $amountFormatted = ($isExpense ? '- ' : '+ ') . $currencySymbol . ' ' . number_format($tx['amount'], 2);
                    ?>
                    <div class="tx-item-row">
                        <div class="tx-left-block">
                            <div class="tx-squircle-icon" style="background-color: <?= $tx['style']['bg'] ?>; color: <?= $tx['style']['color'] ?>;">
                                <i class="fa-solid <?= $tx['style']['icon'] ?>"></i>
                            </div>
                            <div class="tx-meta-block">
                                <span class="tx-name"><?= htmlspecialchars($tx['title']) ?></span>
                                <span class="tx-timestamp"><?= htmlspecialchars($tx['time']) ?></span>
                            </div>
                        </div>
                        <div class="tx-amount-text <?= $isExpense ? 'expense' : 'income' ?>">
                            <?= $amountFormatted ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Balance Eye Toggle Functionality
    const toggleEyeBtn = document.getElementById('toggleEyeBtn');
    const toggleEyeIcon = document.getElementById('toggleEyeIcon');
    const balanceDisplay = document.getElementById('balanceAmountDisplay');

    if (toggleEyeBtn && balanceDisplay) {
        let isHidden = localStorage.getItem('smartspend_balance_hidden') === 'true';

        function updateBalanceView() {
            if (isHidden) {
                balanceDisplay.textContent = '<?= $currencySymbol ?> ••••••••';
                toggleEyeIcon.className = 'fa-regular fa-eye-slash';
            } else {
                balanceDisplay.textContent = '<?= $currencySymbol ?> ' + balanceDisplay.getAttribute('data-balance');
                toggleEyeIcon.className = 'fa-regular fa-eye';
            }
        }

        updateBalanceView();

        toggleEyeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            isHidden = !isHidden;
            localStorage.setItem('smartspend_balance_hidden', isHidden ? 'true' : 'false');
            updateBalanceView();
        });
    }

    // 2. Action: Send (Open Quick Add Modal for Expense)
    const btnSend = document.getElementById('btnActionSend');
    if (btnSend) {
        btnSend.addEventListener('click', function() {
            const expenseTabBtn = document.getElementById('expense-tab');
            if (expenseTabBtn) {
                const tab = new bootstrap.Tab(expenseTabBtn);
                tab.show();
            }
            const modalEl = document.getElementById('quickAddModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        });
    }

    // 3. Action: Receive (Open Quick Add Modal for Income)
    const btnReceive = document.getElementById('btnActionReceive');
    if (btnReceive) {
        btnReceive.addEventListener('click', function() {
            const incomeTabBtn = document.getElementById('income-tab');
            if (incomeTabBtn) {
                const tab = new bootstrap.Tab(incomeTabBtn);
                tab.show();
            }
            const modalEl = document.getElementById('quickAddModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
