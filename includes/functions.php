<?php
/**
 * Global Helper Functions
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth.php';

function sanitize(mixed $data): mixed {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function json_response(bool $success, string $message = '', array $data = [], int $http_code = 200): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($http_code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

function format_currency(float|int|string $amount, ?string $symbol = null): string {
    $amount = (float)$amount;
    if ($symbol === null) {
        $user = current_user();
        $symbol = $user['currency_symbol'] ?? DEFAULT_CURRENCY_SYMBOL;
    }
    // Indian formatting or standard international formatting with 2 decimals if has cents or 0
    $formatted = number_format($amount, 2, '.', ',');
    // If ends with .00, we can show clean integer or standard decimal. Let's keep 2 decimals for precision or clean format
    if (substr($formatted, -3) === '.00') {
        $formatted = number_format($amount, 0, '.', ',');
    }
    return $symbol . $formatted;
}

function format_date(string $dateStr, ?string $format = null): string {
    if (empty($dateStr)) return '';
    if ($format === null) {
        $user = current_user();
        $format = $user['date_format'] ?? DEFAULT_DATE_FORMAT;
    }
    $timestamp = strtotime($dateStr);
    return date($format, $timestamp);
}

function format_relative_day(string $dateStr): string {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($dateStr === $today) {
        return 'Today';
    } elseif ($dateStr === $yesterday) {
        return 'Yesterday';
    } else {
        return date('d M Y', strtotime($dateStr));
    }
}

function get_user_categories(int $userId, ?string $type = null): array {
    $db = get_db();
    $sql = "SELECT * FROM categories WHERE (user_id IS NULL OR user_id = ?)";
    $params = [$userId];

    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }

    $sql .= " ORDER BY user_id DESC, name ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function log_notification(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): bool {
    try {
        $db = get_db();
        // Prevent spamming identical unread notifications
        $stmt = $db->prepare("SELECT id FROM notifications WHERE user_id = ? AND title = ? AND is_read = 0 LIMIT 1");
        $stmt->execute([$userId, $title]);
        if ($stmt->fetch()) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $title, $message, $type, $link]);
    } catch (Exception $e) {
        return false;
    }
}

function check_budget_alerts(int $userId, int $month, int $year): void {
    $db = get_db();
    // Fetch all active budgets for user for this month
    $stmt = $db->prepare("
        SELECT b.*, c.name as category_name 
        FROM budgets b
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.user_id = ? AND b.month = ? AND b.year = ?
    ");
    $stmt->execute([$userId, $month, $year]);
    $budgets = $stmt->fetchAll();

    foreach ($budgets as $b) {
        $budgetAmount = (float)$b['amount'];
        $threshold = (float)$b['alert_threshold'];
        $catId = $b['category_id'];

        if ($catId) {
            // Category budget
            $stmtSpent = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM transactions 
                WHERE user_id = ? AND type = 'expense' AND category_id = ? 
                AND MONTH(date) = ? AND YEAR(date) = ?
            ");
            $stmtSpent->execute([$userId, $catId, $month, $year]);
            $spent = (float)$stmtSpent->fetchColumn();
            $catLabel = $b['category_name'] ?? 'Category';
        } else {
            // Overall monthly budget
            $stmtSpent = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM transactions 
                WHERE user_id = ? AND type = 'expense' 
                AND MONTH(date) = ? AND YEAR(date) = ?
            ");
            $stmtSpent->execute([$userId, $month, $year]);
            $spent = (float)$stmtSpent->fetchColumn();
            $catLabel = 'Overall Monthly Budget';
        }

        if ($budgetAmount > 0) {
            $usagePct = ($spent / $budgetAmount) * 100;
            if ($usagePct >= 100) {
                log_notification(
                    $userId,
                    "Budget Exceeded: {$catLabel}",
                    "You have spent " . format_currency($spent) . " exceeding your budget of " . format_currency($budgetAmount) . " (" . round($usagePct) . "% used).",
                    "danger",
                    "budgets.php"
                );
            } elseif ($usagePct >= $threshold) {
                log_notification(
                    $userId,
                    "Budget Alert: {$catLabel}",
                    "You have reached " . round($usagePct) . "% (" . format_currency($spent) . ") of your " . format_currency($budgetAmount) . " limit.",
                    "warning",
                    "budgets.php"
                );
            }
        }
    }
}

function check_emi_and_bill_alerts(int $userId): void {
    $db = get_db();
    $today = date('Y-m-d');
    $currentDay = (int)date('d');
    $currentMonth = (int)date('m');
    $currentYear = (int)date('Y');

    // 1. Check Active EMIs due soon or overdue
    $stmt = $db->prepare("SELECT * FROM emis WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $emis = $stmt->fetchAll();

    foreach ($emis as $emi) {
        $dueDay = (int)$emi['due_day'];
        
        // Calculate due date for current month
        $daysInMonth = (int)date('t');
        $actualDueDay = min($dueDay, $daysInMonth);
        $dueDateThisMonth = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $actualDueDay);

        // Check if installment was paid this month
        $stmtPaid = $db->prepare("
            SELECT id FROM emi_payments 
            WHERE emi_id = ? AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?
        ");
        $stmtPaid->execute([$emi['id'], $currentMonth, $currentYear]);
        $alreadyPaid = $stmtPaid->fetch();

        if (!$alreadyPaid) {
            // If overdue this month
            if ($today > $dueDateThisMonth) {
                log_notification(
                    $userId,
                    "Overdue EMI: {$emi['loan_name']}",
                    "Your monthly EMI of " . format_currency($emi['emi_amount']) . " for '{$emi['loan_name']}' was due on " . date('d M', strtotime($dueDateThisMonth)) . " and is overdue!",
                    "danger",
                    "emis.php"
                );
            } elseif ($actualDueDay - $currentDay <= 3 && $actualDueDay >= $currentDay) {
                // Due in 0-3 days
                $daysRemaining = $actualDueDay - $currentDay;
                $msg = ($daysRemaining === 0) ? "is due today!" : "is due in {$daysRemaining} day(s) (" . date('d M', strtotime($dueDateThisMonth)) . ")!";
                log_notification(
                    $userId,
                    "Upcoming EMI: {$emi['loan_name']}",
                    "Your monthly EMI of " . format_currency($emi['emi_amount']) . " " . $msg,
                    "warning",
                    "emis.php"
                );
            }
        }
    }

    // 2. Check Recurring Bills due soon
    $stmtRec = $db->prepare("SELECT * FROM recurring_transactions WHERE user_id = ? AND is_active = 1 AND next_due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)");
    $stmtRec->execute([$userId]);
    $recurring = $stmtRec->fetchAll();

    foreach ($recurring as $rec) {
        $dueDate = $rec['next_due_date'];
        if ($dueDate < $today) {
            log_notification(
                $userId,
                "Overdue Bill: {$rec['title']}",
                "Your recurring bill '{$rec['title']}' of " . format_currency($rec['amount']) . " was due on " . date('d M', strtotime($dueDate)),
                "danger",
                "recurring.php"
            );
        } else {
            log_notification(
                $userId,
                "Upcoming Bill: {$rec['title']}",
                "Your recurring payment of " . format_currency($rec['amount']) . " is due on " . date('d M', strtotime($dueDate)),
                "info",
                "recurring.php"
            );
        }
    }
}

function handle_file_upload(array $file, string $subfolder = 'receipts', array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf']): ?string {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        throw new Exception("Invalid file type: .{$ext} is not allowed. Allowed types: " . implode(', ', $allowedExtensions));
    }

    // Check size limit: 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("File too large. Maximum file size allowed is 5MB.");
    }

    $uploadDir = __DIR__ . '/../assets/uploads/' . $subfolder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid($subfolder . '_', true) . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to move uploaded file.");
    }

    return 'assets/uploads/' . $subfolder . '/' . $filename;
}

