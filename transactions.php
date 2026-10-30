<?php
/**
 * SmartSpend - Transactions Screen
 * Pixel-Perfect Implementation matching Reference Mockup
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Transactions";
$user = current_user();
$userId = $user['id'];
$currencySymbol = $user['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;
$categories = get_user_categories($userId);

$db = get_db();

// Visual styling helper matching reference mockup
function get_tx_visual_meta($notes, $categoryName, $type, $fallbackColor, $fallbackIcon) {
    $text = strtolower(trim(($notes ?? '') . ' ' . ($categoryName ?? '')));
    $txType = strtolower($type ?? 'expense');

    // 1. Starbucks / Coffee
    if (strpos($text, 'starbuck') !== false || strpos($text, 'coffee') !== false || strpos($text, 'cafe') !== false || strpos($text, 'chai') !== false) {
        return [
            'icon' => 'fa-mug-hot',
            'bg' => '#0D9488',
            'color' => '#FFFFFF',
            'title' => !empty($notes) ? $notes : ($categoryName ?: 'Coffee Shop'),
            'sub' => $categoryName ?: 'Food & Dining'
        ];
    }
    // 2. Uber / Taxi / Cab
    if (strpos($text, 'uber') !== false || strpos($text, 'ola') !== false || strpos($text, 'rapido') !== false || strpos($text, 'taxi') !== false || strpos($text, 'cab') !== false || strpos($text, 'auto') !== false) {
        return [
            'icon' => 'fa-car',
            'bg' => '#111827',
            'color' => '#FFFFFF',
            'title' => !empty($notes) ? $notes : ($categoryName ?: 'Uber'),
            'sub' => $categoryName ?: 'Transport'
        ];
    }
    // 3. Salary / Income
    if (strpos($text, 'salar') !== false || strpos($text, 'payroll') !== false || strpos($text, 'stipend') !== false || $txType === 'income') {
        return [
            'icon' => 'fa-money-bill-wave',
            'bg' => '#059669',
            'color' => '#FFFFFF',
            'title' => !empty($notes) ? $notes : ($categoryName ?: 'Salary'),
            'sub' => $categoryName ?: 'Income'
        ];
    }
    // 4. Walmart / Shopping / Amazon
    if (strpos($text, 'walmart') !== false || strpos($text, 'amazon') !== false || strpos($text, 'flipkart') !== false || strpos($text, 'shop') !== false || strpos($text, 'myntra') !== false) {
        return [
            'icon' => 'fa-asterisk',
            'bg' => '#0071DC',
            'color' => '#FFFFFF',
            'title' => !empty($notes) ? $notes : ($categoryName ?: 'Walmart'),
            'sub' => $categoryName ?: 'Shopping'
        ];
    }
    // 5. Netflix / Entertainment
    if (strpos($text, 'netfl') !== false || strpos($text, 'spotify') !== false || strpos($text, 'prime') !== false || strpos($text, 'movie') !== false || strpos($text, 'cinema') !== false || strpos($text, 'ent') !== false) {
        return [
            'icon' => 'fa-film',
            'bg' => '#18181B',
            'color' => '#EF4444',
            'title' => !empty($notes) ? $notes : ($categoryName ?: 'Netflix'),
            'sub' => $categoryName ?: 'Entertainment'
        ];
    }
    // 6. Pharmacy / Health & Fitness
    if (strpos($text, 'pharm') !== false || strpos($text, 'medic') !== false || strpos($text, 'doctor') !== false || strpos($text, 'health') !== false || strpos($text, 'hospital') !== false) {
        return [
            'icon' => 'fa-plus',
            'bg' => '#06B6D4',
            'color' => '#FFFFFF',
            'title' => !empty($notes) ? $notes : ($categoryName ?: 'Pharmacy'),
            'sub' => $categoryName ?: 'Health & Fitness'
        ];
    }
    // 7. Grocery Store / Food & Dining
    if (strpos($text, 'groc') !== false || strpos($text, 'supermarket') !== false || strpos($text, 'mart') !== false || strpos($text, 'blinkit') !== false || strpos($text, 'zepto') !== false || strpos($text, 'instamart') !== false) {
        return [
            'icon' => 'fa-cart-shopping',
            'bg' => '#10B981',
            'color' => '#FFFFFF',
            'title' => !empty($notes) ? $notes : ($categoryName ?: 'Grocery Store'),
            'sub' => $categoryName ?: 'Food & Dining'
        ];
    }
    // 8. Fuel / Petrol / Gas
    if (strpos($text, 'petrol') !== false || strpos($text, 'fuel') !== false || strpos($text, 'gas') !== false || strpos($text, 'diesel') !== false) {
        return [
            'icon' => 'fa-gas-pump',
            'bg' => '#2563EB',
            'color' => '#FFFFFF',
            'title' => !empty($notes) ? $notes : ($categoryName ?: 'Petrol'),
            'sub' => $categoryName ?: 'Transport'
        ];
    }
    // 9. Bills / Utilities
    if (strpos($text, 'bill') !== false || strpos($text, 'util') !== false || strpos($text, 'recharge') !== false || strpos($text, 'wifi') !== false || strpos($text, 'emi') !== false) {
        return [
            'icon' => 'fa-file-invoice-dollar',
            'bg' => '#6366F1',
            'color' => '#FFFFFF',
            'title' => !empty($notes) ? $notes : ($categoryName ?: 'Bills'),
            'sub' => $categoryName ?: 'Bills & Utilities'
        ];
    }

    // Default fallback
    $title = !empty($notes) ? $notes : ($categoryName ?: ($txType === 'income' ? 'Income' : 'Expense'));
    $sub = !empty($notes) ? ($categoryName ?: 'General') : ($categoryName ?: ucfirst($txType));
    $icon = !empty($fallbackIcon) ? $fallbackIcon : 'fa-receipt';
    $bg = !empty($fallbackColor) ? $fallbackColor : '#0D9488';

    return [
        'icon' => $icon,
        'bg' => $bg,
        'color' => '#FFFFFF',
        'title' => $title,
        'sub' => $sub
    ];
}

// Fetch user's real transactions from DB
$stmtInitial = $db->prepare("
    SELECT t.*, c.name as category_name, c.icon as cat_icon, c.color as cat_color, c.bg_color as cat_bg 
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?
    ORDER BY t.date DESC, t.time DESC, t.id DESC
    LIMIT 100
");
$stmtInitial->execute([$userId]);
$initialRows = $stmtInitial->fetchAll();

$initialGrouped = [];
foreach ($initialRows as $row) {
    $dayLabel = format_relative_day($row['date']);
    if (!isset($initialGrouped[$dayLabel])) {
        $initialGrouped[$dayLabel] = [];
    }
    $initialGrouped[$dayLabel][] = $row;
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ========================================================
   Pixel-Perfect Transactions Page Styling
   ======================================================== */

/* Hide generic app-topbar on transactions page */
.app-topbar {
    display: none !important;
}

/* Page Shell Container */
.tx-page-shell {
    max-width: 500px;
    margin: 0 auto;
    padding: calc(env(safe-area-inset-top, 0px) + 12px) 16px calc(env(safe-area-inset-bottom, 0px) + 90px) 16px;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

@media (min-width: 992px) {
    .tx-page-shell {
        max-width: 580px;
        padding-top: 24px;
    }
}

/* 1. Top Header Bar */
.tx-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 4px;
    margin-bottom: 16px;
}

.tx-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.tx-btn-back {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0F172A;
    text-decoration: none;
    font-size: 1.25rem;
    transition: background-color 0.15s ease, transform 0.1s ease;
}

.tx-btn-back:hover {
    background-color: #F1F5F9;
    color: #0F172A;
}

.tx-btn-back:active {
    transform: scale(0.92);
}

.tx-header-title {
    font-size: 1.45rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0;
    letter-spacing: -0.3px;
    line-height: 1.2;
}

.tx-header-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.tx-header-action-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: #0F172A;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    cursor: pointer;
    transition: background-color 0.15s ease, transform 0.1s ease;
}

.tx-header-action-btn:hover {
    background-color: #F1F5F9;
}

.tx-header-action-btn:active {
    transform: scale(0.92);
}

/* Search Bar Panel (Collapsible) */
.tx-search-panel {
    margin-bottom: 14px;
    padding: 0 4px;
    display: none;
}

.tx-search-input-box {
    position: relative;
    display: flex;
    align-items: center;
}

.tx-search-input-box i.search-ico {
    position: absolute;
    left: 16px;
    color: #94A3B8;
    font-size: 0.95rem;
    pointer-events: none;
}

.tx-search-field {
    width: 100%;
    height: 46px;
    padding: 0 42px 0 44px;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 9999px;
    font-size: 0.92rem;
    color: #0F172A;
    outline: none;
    transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.tx-search-field:focus {
    background: #FFFFFF;
    border-color: #059669;
    box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
}

.tx-search-clear-btn {
    position: absolute;
    right: 12px;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #E2E8F0;
    border: none;
    color: #64748B;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    cursor: pointer;
}

/* 2. Filter Pills Row (All, Income, Expenses) */
.tx-pills-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    padding: 0 4px;
}

.tx-pill-btn {
    border: none;
    outline: none;
    background: #F1F5F9;
    color: #475569;
    font-size: 0.92rem;
    font-weight: 600;
    padding: 8px 24px;
    border-radius: 9999px;
    cursor: pointer;
    user-select: none;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.tx-pill-btn:hover {
    background: #E2E8F0;
    color: #0F172A;
}

.tx-pill-btn.active {
    background: #064E3B;
    color: #FFFFFF;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(6, 78, 59, 0.22);
}

/* 3. Date Grouping & Transactions List */
.tx-date-heading {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0F172A;
    margin: 22px 0 10px 4px;
    letter-spacing: -0.2px;
}

.tx-group-wrapper {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.tx-row-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 6px;
    border-radius: 18px;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    transition: background-color 0.15s ease, transform 0.1s ease;
}

.tx-row-item:hover {
    background-color: #F8FAFC;
}

.tx-row-item:active {
    transform: scale(0.99);
    background-color: #F1F5F9;
}

.tx-row-left {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    flex: 1;
}

.tx-squircle {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.tx-meta-texts {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}

.tx-title-text {
    font-size: 0.98rem;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tx-subtitle-text {
    font-size: 0.82rem;
    font-weight: 500;
    color: #94A3B8;
    margin-top: 3px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tx-row-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    margin-left: 14px;
    flex-shrink: 0;
}

.tx-amount-text {
    font-size: 0.98rem;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.2px;
}

.tx-amount-text.expense {
    color: #0F172A;
}

.tx-amount-text.income {
    color: #059669;
}

.tx-time-text {
    font-size: 0.78rem;
    font-weight: 500;
    color: #94A3B8;
    margin-top: 3px;
    line-height: 1.2;
}

/* Empty State Box */
.tx-empty-card {
    text-align: center;
    padding: 50px 20px;
    background: #F8FAFC;
    border-radius: 24px;
    border: 1.5px dashed #E2E8F0;
    margin: 24px 0;
}

.tx-empty-icon {
    width: 64px;
    height: 64px;
    background: #E8F8F0;
    color: #059669;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin: 0 auto 16px auto;
}

.tx-empty-card h5 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 6px;
}

.tx-empty-card p {
    font-size: 0.85rem;
    color: #64748B;
    margin-bottom: 18px;
    max-width: 280px;
    margin-left: auto;
    margin-right: auto;
}

/* Dropdown menu polish */
.tx-dropdown-menu {
    border-radius: 18px;
    padding: 8px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
}

.tx-dropdown-menu .dropdown-item {
    border-radius: 12px;
    padding: 9px 14px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.15s ease;
}

.tx-dropdown-menu .dropdown-item:hover {
    background-color: #F1F5F9;
    color: #0F172A;
}
</style>

<div class="tx-page-shell">

    <!-- 1. Top Header Bar: Back Arrow, Bold Title, Search & 3-Dots Icons -->
    <header class="tx-header-bar">
        <div class="tx-header-left">
            <a href="dashboard.php" class="tx-btn-back" title="Go to Dashboard">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h1 class="tx-header-title">Transactions</h1>
        </div>

        <div class="tx-header-actions">
            <!-- Search Toggle Button -->
            <button type="button" class="tx-header-action-btn" id="toggleSearchBtn" title="Search Transactions">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>

            <!-- 3-Dots Action Menu -->
            <div class="dropdown">
                <button type="button" class="tx-header-action-btn" data-bs-toggle="dropdown" aria-expanded="false" title="More Options">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end tx-dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                            <i class="fa-solid fa-plus text-success"></i> Add Transaction
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fa-solid fa-sliders text-warning"></i> Advanced Filters
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item" href="export/csv.php?type=transactions">
                            <i class="fa-solid fa-file-csv text-primary"></i> Export CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="export/print_report.php" target="_blank">
                            <i class="fa-solid fa-file-pdf text-danger"></i> Print Statement
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Collapsible Search Input -->
    <div id="txSearchPanel" class="tx-search-panel">
        <div class="tx-search-input-box">
            <i class="fa-solid fa-magnifying-glass search-ico"></i>
            <input type="text" id="txSearchInput" class="tx-search-field" placeholder="Search merchant, notes, category...">
            <button type="button" id="clearSearchBtn" class="tx-search-clear-btn" title="Clear">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- 2. Filter Pills Row: All, Income, Expenses -->
    <div class="tx-pills-row">
        <button type="button" class="tx-pill-btn active" data-type="all">All</button>
        <button type="button" class="tx-pill-btn" data-type="income">Income</button>
        <button type="button" class="tx-pill-btn" data-type="expense">Expenses</button>
    </div>

    <!-- 3. Transactions List Container -->
    <div id="transactionsListContainer">
        <?php if (!empty($initialGrouped)): ?>
            <?php foreach ($initialGrouped as $dayLabel => $items): ?>
                <div class="tx-date-heading"><?= htmlspecialchars($dayLabel) ?></div>
                <div class="tx-group-wrapper">
                    <?php foreach ($items as $item): 
                        $meta = get_tx_visual_meta(
                            $item['notes'],
                            $item['category_name'],
                            $item['type'],
                            $item['cat_color'] ?? '#64748B',
                            $item['cat_icon'] ?? 'fa-receipt'
                        );
                        $isExpense = ($item['type'] === 'expense');
                        $formattedAmount = ($isExpense ? '- ' : '+ ') . format_currency($item['amount'], $currencySymbol);
                        $formattedTime = !empty($item['time']) ? date('h:i A', strtotime($item['time'])) : '';
                    ?>
                        <div class="tx-row-item" data-tx='<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>'>
                            <div class="tx-row-left">
                                <div class="tx-squircle" style="background-color: <?= $meta['bg'] ?>; color: <?= $meta['color'] ?>;">
                                    <i class="fa-solid <?= $meta['icon'] ?>"></i>
                                </div>
                                <div class="tx-meta-texts">
                                    <span class="tx-title-text"><?= htmlspecialchars($meta['title']) ?></span>
                                    <span class="tx-subtitle-text"><?= htmlspecialchars($meta['sub']) ?></span>
                                </div>
                            </div>
                            <div class="tx-row-right">
                                <span class="tx-amount-text <?= $isExpense ? 'expense' : 'income' ?>">
                                    <?= htmlspecialchars($formattedAmount) ?>
                                </span>
                                <span class="tx-time-text"><?= htmlspecialchars($formattedTime) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="tx-empty-card">
                <div class="tx-empty-icon">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <h5>No transactions yet</h5>
                <p>Track your daily expenses and income to see them organized here.</p>
                <button type="button" class="btn btn-primary-smart px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Transaction
                </button>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Filter Modal Sheet -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom pb-3">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-sliders text-success me-2"></i> Filter Transactions
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="filterForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">CATEGORY</label>
                        <select id="filterCategory" class="form-select rounded-3 py-2">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-secondary small">FROM DATE</label>
                            <input type="date" id="filterDateFrom" class="form-control rounded-3 py-2">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-secondary small">TO DATE</label>
                            <input type="date" id="filterDateTo" class="form-control rounded-3 py-2">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between gap-2 mt-4 pt-2">
                        <button type="button" id="resetFiltersBtn" class="btn btn-light border flex-grow-1 rounded-pill py-2 fw-semibold">Reset</button>
                        <button type="submit" class="btn btn-primary-smart flex-grow-1 rounded-pill py-2 fw-bold">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Detail Sheet Modal -->
<div class="modal fade" id="txDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom pb-3">
                <h5 class="modal-title fw-bold" id="txDetailTitle">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="txDetailBody">
                <!-- Dynamically populated via JS -->
            </div>
            <div class="modal-footer border-top justify-content-between px-4 py-3">
                <button type="button" id="modalDeleteTxBtn" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold">
                    <i class="fa-solid fa-trash me-1"></i> Delete
                </button>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentFilterType = 'all';
    let currentCategoryId = '';
    let currentDateFrom = '';
    let currentDateTo = '';
    let currentSearch = '';
    const currencySymbol = '<?= htmlspecialchars($currencySymbol) ?>';

    // Helper: Map visuals identically in JS
    function getTxVisuals(item) {
        const notes = (item.notes || '').toLowerCase();
        const cat = (item.category_name || '').toLowerCase();
        const text = notes + ' ' + cat;
        const type = item.type || 'expense';

        if (text.includes('starbuck') || text.includes('coffee') || text.includes('cafe') || text.includes('chai')) {
            return { icon: 'fa-mug-hot', bg: '#0D9488', color: '#FFFFFF', title: item.notes || item.category_name || 'Coffee Shop', sub: item.category_name || 'Food & Dining' };
        }
        if (text.includes('uber') || text.includes('ola') || text.includes('rapido') || text.includes('taxi') || text.includes('cab') || text.includes('auto')) {
            return { icon: 'fa-car', bg: '#111827', color: '#FFFFFF', title: item.notes || item.category_name || 'Uber', sub: item.category_name || 'Transport' };
        }
        if (text.includes('salar') || text.includes('payroll') || text.includes('stipend') || type === 'income') {
            return { icon: 'fa-money-bill-wave', bg: '#059669', color: '#FFFFFF', title: item.notes || item.category_name || 'Salary', sub: item.category_name || 'Income' };
        }
        if (text.includes('walmart') || text.includes('amazon') || text.includes('flipkart') || text.includes('shop') || text.includes('myntra')) {
            return { icon: 'fa-asterisk', bg: '#0071DC', color: '#FFFFFF', title: item.notes || item.category_name || 'Walmart', sub: item.category_name || 'Shopping' };
        }
        if (text.includes('netfl') || text.includes('spotify') || text.includes('prime') || text.includes('movie') || text.includes('cinema') || text.includes('ent')) {
            return { icon: 'fa-film', bg: '#18181B', color: '#EF4444', title: item.notes || item.category_name || 'Netflix', sub: item.category_name || 'Entertainment' };
        }
        if (text.includes('pharm') || text.includes('medic') || text.includes('doctor') || text.includes('health') || text.includes('hospital')) {
            return { icon: 'fa-plus', bg: '#06B6D4', color: '#FFFFFF', title: item.notes || item.category_name || 'Pharmacy', sub: item.category_name || 'Health & Fitness' };
        }
        if (text.includes('groc') || text.includes('supermarket') || text.includes('mart') || text.includes('blinkit') || text.includes('zepto') || text.includes('instamart')) {
            return { icon: 'fa-cart-shopping', bg: '#10B981', color: '#FFFFFF', title: item.notes || item.category_name || 'Grocery Store', sub: item.category_name || 'Food & Dining' };
        }
        if (text.includes('petrol') || text.includes('fuel') || text.includes('gas') || text.includes('diesel')) {
            return { icon: 'fa-gas-pump', bg: '#2563EB', color: '#FFFFFF', title: item.notes || item.category_name || 'Petrol', sub: item.category_name || 'Transport' };
        }
        if (text.includes('bill') || text.includes('util') || text.includes('recharge') || text.includes('wifi') || text.includes('emi')) {
            return { icon: 'fa-file-invoice-dollar', bg: '#6366F1', color: '#FFFFFF', title: item.notes || item.category_name || 'Bills', sub: item.category_name || 'Bills & Utilities' };
        }

        return {
            icon: item.icon || 'fa-receipt',
            bg: item.color || '#64748B',
            color: '#FFFFFF',
            title: item.notes || item.category_name || (type === 'income' ? 'Income' : 'Expense'),
            sub: item.notes ? (item.category_name || 'General') : (item.category_name || (type === 'income' ? 'Income' : 'Expense'))
        };
    }

    // Toggle Search Bar
    $('#toggleSearchBtn').on('click', function() {
        $('#txSearchPanel').slideToggle(200, function() {
            if ($(this).is(':visible')) {
                $('#txSearchInput').focus();
            }
        });
    });

    // Clear Search Input
    $('#clearSearchBtn').on('click', function() {
        $('#txSearchInput').val('');
        $(this).hide();
        currentSearch = '';
        loadTransactions();
    });

    // Debounced Live Search
    let searchTimer = null;
    $('#txSearchInput').on('input', function() {
        clearTimeout(searchTimer);
        currentSearch = $(this).val().trim();
        if (currentSearch.length > 0) {
            $('#clearSearchBtn').css('display', 'flex');
        } else {
            $('#clearSearchBtn').hide();
        }
        searchTimer = setTimeout(loadTransactions, 300);
    });

    // Filter Pills Click (All, Income, Expenses)
    $('.tx-pill-btn').on('click', function() {
        $('.tx-pill-btn').removeClass('active');
        $(this).addClass('active');
        currentFilterType = $(this).data('type');
        loadTransactions();
    });

    // Filter Modal Submit
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        currentCategoryId = $('#filterCategory').val();
        currentDateFrom = $('#filterDateFrom').val();
        currentDateTo = $('#filterDateTo').val();
        $('#filterModal').modal('hide');
        loadTransactions();
    });

    // Filter Modal Reset
    $('#resetFiltersBtn').on('click', function() {
        $('#filterCategory').val('');
        $('#filterDateFrom').val('');
        $('#filterDateTo').val('');
        currentCategoryId = '';
        currentDateFrom = '';
        currentDateTo = '';
        $('#filterModal').modal('hide');
        loadTransactions();
    });

    // Load Transactions from API
    function loadTransactions() {
        const container = $('#transactionsListContainer');
        container.html(`
            <div class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                <span class="ms-2">Loading transactions...</span>
            </div>
        `);

        $.getJSON('api/transactions.php', {
            action: 'list',
            type: currentFilterType,
            category_id: currentCategoryId,
            date_from: currentDateFrom,
            date_to: currentDateTo,
            search: currentSearch
        }, function(res) {
            if (!res.success) {
                container.html(`<div class="alert alert-danger mx-2">${escapeHtml(res.message)}</div>`);
                return;
            }

            if (res.count === 0) {
                container.html(`
                    <div class="tx-empty-card">
                        <div class="tx-empty-icon">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <h5>No transactions found</h5>
                        <p>No transactions match your current search or filter criteria.</p>
                        <button type="button" class="btn btn-primary-smart px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                            <i class="fa-solid fa-plus me-1"></i> Add Transaction
                        </button>
                    </div>
                `);
                return;
            }

            let html = '';
            for (const [dayLabel, items] of Object.entries(res.grouped)) {
                html += `<div class="tx-date-heading">${escapeHtml(dayLabel)}</div>`;
                html += `<div class="tx-group-wrapper">`;

                for (const item of items) {
                    const meta = getTxVisuals(item);
                    const isExpense = (item.type === 'expense');
                    const sign = isExpense ? '- ' : '+ ';
                    const formattedAmount = sign + currencySymbol + Number(item.amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const timeStr = item.formatted_time || '';

                    html += `
                        <div class="tx-row-item" data-tx='${JSON.stringify(item).replace(/'/g, "&apos;")}'>
                            <div class="tx-row-left">
                                <div class="tx-squircle" style="background-color: ${meta.bg}; color: ${meta.color};">
                                    <i class="fa-solid ${meta.icon}"></i>
                                </div>
                                <div class="tx-meta-texts">
                                    <span class="tx-title-text">${escapeHtml(meta.title)}</span>
                                    <span class="tx-subtitle-text">${escapeHtml(meta.sub)}</span>
                                </div>
                            </div>
                            <div class="tx-row-right">
                                <span class="tx-amount-text ${isExpense ? 'expense' : 'income'}">
                                    ${escapeHtml(formattedAmount)}
                                </span>
                                <span class="tx-time-text">${escapeHtml(timeStr)}</span>
                            </div>
                        </div>
                    `;
                }
                html += `</div>`;
            }

            container.html(html);
        }).fail(function() {
            container.html(`<div class="alert alert-danger mx-2">Error retrieving transactions.</div>`);
        });
    }

    // Click on Transaction Row to open details
    $(document).on('click', '.tx-row-item', function() {
        const item = $(this).data('tx');
        if (!item) return;

        const meta = getTxVisuals(item);
        const isExpense = (item.type === 'expense');
        const sign = isExpense ? '- ' : '+ ';
        const formattedAmount = sign + currencySymbol + Number(item.amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        let receiptHtml = '';
        if (item.receipt_file) {
            receiptHtml = `
                <div class="mt-3 pt-3 border-top">
                    <label class="form-label fw-bold d-block small text-secondary">RECEIPT ATTACHMENT</label>
                    <button class="btn btn-outline-success btn-sm rounded-pill px-3 view-receipt-btn" data-file="${escapeHtml(item.receipt_file)}">
                        <i class="fa-solid fa-paperclip me-1"></i> View Receipt
                    </button>
                </div>
            `;
        }

        $('#txDetailBody').html(`
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="tx-squircle" style="background-color: ${meta.bg}; color: ${meta.color}; width: 54px; height: 54px; font-size: 1.4rem;">
                    <i class="fa-solid ${meta.icon}"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1 text-dark">${escapeHtml(meta.title)}</h5>
                    <span class="badge ${isExpense ? 'badge-soft-danger' : 'badge-soft-success'} px-2 py-1">${item.type.toUpperCase()}</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-secondary small fw-semibold" style="width: 40%;">Amount:</td>
                        <td class="fw-bold fs-5 ${isExpense ? 'text-dark' : 'text-success'}">${escapeHtml(formattedAmount)}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary small fw-semibold">Category:</td>
                        <td class="fw-semibold text-dark">${escapeHtml(item.category_name || 'Uncategorized')}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary small fw-semibold">Date & Time:</td>
                        <td class="fw-semibold text-dark">${escapeHtml(item.date)} ${escapeHtml(item.formatted_time || item.time || '')}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary small fw-semibold">Payment Mode:</td>
                        <td class="fw-semibold text-dark">${escapeHtml(item.payment_method || 'UPI')}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary small fw-semibold">Notes:</td>
                        <td class="text-dark">${escapeHtml(item.notes || 'None')}</td>
                    </tr>
                </table>
            </div>
            ${receiptHtml}
        `);

        $('#modalDeleteTxBtn').data('id', item.id);
        $('#txDetailModal').modal('show');
    });

    // Delete Transaction Handler
    $('#modalDeleteTxBtn').on('click', function() {
        const txId = $(this).data('id');
        $('#txDetailModal').modal('hide');

        confirmAction('Delete Transaction?', 'Are you sure you want to remove this transaction record?').then(res => {
            if (res.isConfirmed) {
                $.post('api/transactions.php', { action: 'delete', id: txId }, function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message);
                        loadTransactions();
                    } else {
                        toastr.error(resp.message);
                    }
                }).fail(function(xhr) {
                    toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error deleting transaction.');
                });
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
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
