<?php
/**
 * Stats & Reports Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Stats & Analytics";
$user = current_user();
$userId = $user['id'];
$currencySymbol = $user['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Bar -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">Stats & Analytics</h3>
        <p class="text-muted small mb-0">Financial trends and category breakdowns</p>
    </div>
    
    <!-- Period Dropdown Pill -->
    <div class="dropdown">
        <button class="dropdown-pill dropdown-toggle" type="button" id="periodDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
            This Month
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4">
            <li><a class="dropdown-item period-select-opt active" href="javascript:void(0)" data-period="this_month">This Month</a></li>
            <li><a class="dropdown-item period-select-opt" href="javascript:void(0)" data-period="last_month">Last Month</a></li>
            <li><a class="dropdown-item period-select-opt" href="javascript:void(0)" data-period="this_week">This Week</a></li>
            <li><a class="dropdown-item period-select-opt" href="javascript:void(0)" data-period="this_year">This Year</a></li>
        </ul>
    </div>
</div>

<!-- Total Expenses Hero Card with Cute Piggy Bank -->
<div class="app-card border-0 mb-3 p-4" style="border-radius: var(--radius-xl); background: #FFFFFF; box-shadow: var(--shadow-card);">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <div class="text-secondary fw-semibold mb-1" style="font-size: 0.95rem;">Total Expenses</div>
            <div class="fw-bolder mb-1" id="statTotalExpense" style="font-size: 2.4rem; color: #28221D; letter-spacing: -0.8px;">
                <span class="spinner-border spinner-border-sm text-secondary"></span>
            </div>
            <div class="small fw-semibold" id="statComparisonText" style="color: var(--text-muted); font-size: 0.85rem;">
                vs last month <span id="statCompArrow"><i class="fa-solid fa-arrow-trend-up text-danger"></i></span> <span id="statCompPct">0%</span>
            </div>
        </div>
        <div class="flex-shrink-0">
            <img src="assets/img/piggy-bank.svg" width="130" height="105" alt="Piggy Bank" style="filter: drop-shadow(0 6px 14px rgba(244, 63, 94, 0.15));">
        </div>
    </div>
</div>

<!-- Responsive Charts Grid (Side-by-Side on Desktop, Stacked on Mobile) -->
<div class="row g-3">
    <!-- Expenses by Category Card -->
    <div class="col-lg-6">
        <div class="overview-card h-100 mb-0">
            <div class="card-top-bar">
                <h4 class="card-heading">Expenses by Category</h4>
            </div>

            <div id="categoryStatsContent">
                <div class="row align-items-center g-3">
                    <div class="col-sm-5 text-center">
                        <div class="chart-container-relative" style="width: 150px; height: 150px;">
                            <canvas id="categoryStatsChart"></canvas>
                        </div>
                    </div>
                    <div class="col-sm-7">
                        <div class="category-legend-list mt-0" id="categoryStatsLegend">
                            <!-- Dynamically populated -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Trend Line Chart Card -->
    <div class="col-lg-6">
        <div class="overview-card h-100 mb-0">
            <div class="card-top-bar">
                <h4 class="card-heading">Daily Trend</h4>
                <span class="badge rounded-pill bg-light text-secondary border px-3 py-1" style="font-size: 0.74rem;">This Week</span>
            </div>

            <div style="height: 200px; position: relative;">
                <canvas id="dailyTrendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Cute Encouragement Card (Mockup Screen 3 exact design) -->
<div class="encouragement-card my-3">
    <div class="encouragement-content">
        <div class="encouragement-icon">
            <i class="fa-solid fa-seedling"></i>
        </div>
        <div class="encouragement-text">
            <h5>Great job! 🌱</h5>
            <p id="encouragementMsg">Tracking your daily expenses helps you save more.</p>
        </div>
    </div>
    <div class="flex-shrink-0">
        <img src="assets/img/default-avatar.svg" width="44" height="44" alt="Cat Mascot" style="border-radius: 50%;">
    </div>
</div>

<!-- Statement Export Buttons -->
<div class="d-flex justify-content-between align-items-center mt-3 pt-2">
    <a href="export/print_report.php" class="btn btn-light border btn-sm rounded-pill px-4 text-secondary" style="font-size: 0.85rem; font-weight: 700;">
        <i class="fa-solid fa-print me-1"></i> Statement / PDF
    </a>
    <a href="export/csv.php?type=transactions" class="btn btn-light border btn-sm rounded-pill px-4 text-secondary" style="font-size: 0.85rem; font-weight: 700;">
        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPeriod = 'this_month';
    let catChart = null;
    let trendChart = null;

    $('.period-select-opt').on('click', function() {
        $('.period-select-opt').removeClass('active');
        $(this).addClass('active');
        currentPeriod = $(this).data('period');
        $('#periodDropdownBtn').text($(this).text());
        fetchReportsData();
    });

    function fetchReportsData() {
        $.getJSON('api/reports.php', { period: currentPeriod }, function(res) {
            if (!res.success) {
                toastr.error('Error fetching analytics.');
                return;
            }

            // 1. Total Expenses
            $('#statTotalExpense').text(res.formatted_total_expense);
            
            // Comparison
            let compIcon = res.pct_direction === 'up' 
                ? '<i class="fa-solid fa-arrow-trend-up text-danger"></i>' 
                : '<i class="fa-solid fa-arrow-trend-down text-success"></i>';
            $('#statCompArrow').html(compIcon);
            $('#statCompPct').text(res.pct_change + '%');

            if (res.pct_direction === 'down' && res.pct_change > 0) {
                $('#encouragementMsg').text(`You spent ${res.pct_change}% less than last period!`);
            } else if (res.total_expense === 0) {
                $('#encouragementMsg').text('No expenses logged for this period yet.');
            } else {
                $('#encouragementMsg').text('Stay within your monthly budget to maximize savings.');
            }

            // 2. Category Donut Chart
            updateCategoryChart(res.donut, res.legend);

            // 3. Daily Trend Line Chart
            updateTrendChart(res.trend);
        });
    }

    function updateCategoryChart(donut, legend) {
        if (catChart) {
            catChart.destroy();
        }

        const container = $('#categoryStatsContent');
        if (!donut.data || donut.data.length === 0) {
            container.html(`
                <div class="empty-state-box py-3">
                    <p class="text-muted small mb-0">No expenses recorded for this period.</p>
                </div>
            `);
            return;
        }

        container.html(`
            <div class="row align-items-center g-3">
                <div class="col-sm-5 text-center">
                    <div class="chart-container-relative" style="width: 150px; height: 150px;">
                        <canvas id="categoryStatsChart"></canvas>
                    </div>
                </div>
                <div class="col-sm-7">
                    <div class="category-legend-list mt-0" id="categoryStatsLegend"></div>
                </div>
            </div>
        `);

        const ctx = document.getElementById('categoryStatsChart').getContext('2d');
        const pastelColors = ['#F87171', '#FBBF24', '#F472B6', '#A78BFA', '#38BDF8', '#4ADE80', '#94A3B8'];

        catChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: donut.labels,
                datasets: [{
                    data: donut.data,
                    backgroundColor: pastelColors.slice(0, donut.data.length),
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Populate legend
        let legHtml = '';
        for (let i = 0; i < legend.length; i++) {
            const item = legend[i];
            const chipColor = pastelColors[i % pastelColors.length];
            legHtml += `
                <div class="legend-item">
                    <div class="legend-left">
                        <span class="legend-chip" style="background-color: ${chipColor};"></span>
                        <span class="text-truncate" style="max-width: 130px;">${item.name}</span>
                    </div>
                    <div class="legend-right">
                        <span class="legend-amount">${item.formatted_amount}</span>
                        <span class="legend-pct">${item.percentage}%</span>
                    </div>
                </div>
            `;
        }
        $('#categoryStatsLegend').html(legHtml);
    }

    function updateTrendChart(trend) {
        if (trendChart) {
            trendChart.destroy();
        }

        const ctx = document.getElementById('dailyTrendChart').getContext('2d');

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trend.labels,
                datasets: [{
                    data: trend.data,
                    borderColor: '#22C55E',
                    backgroundColor: 'rgba(34, 197, 94, 0.08)',
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#22C55E',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        display: false,
                        beginAtZero: true
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            font: { family: 'Redaction', size: 11, weight: '700' },
                            color: '#9C9287'
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1E7538',
                        titleFont: { size: 10 },
                        bodyFont: { size: 12, weight: 'bold' },
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return '<?= $currencySymbol ?>' + Number(context.raw).toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Initial load
    fetchReportsData();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
