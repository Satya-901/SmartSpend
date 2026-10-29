<?php
/**
 * CSV Export Utility
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();
$userId = current_user_id();
$type = $_GET['type'] ?? 'transactions'; // transactions, emis, payments
$db = get_db();

if ($type === 'transactions') {
    $filename = "transactions_export_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Microsoft Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV Headers
    fputcsv($output, ['ID', 'Date', 'Time', 'Type', 'Category', 'Amount', 'Payment Method', 'Notes', 'Created At']);

    $stmt = $db->prepare("
        SELECT t.id, t.date, t.time, t.type, COALESCE(c.name, 'Uncategorized') as category, t.amount, t.payment_method, t.notes, t.created_at
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        ORDER BY t.date DESC, t.id DESC
    ");
    $stmt->execute([$userId]);

    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['date'],
            $row['time'],
            ucfirst($row['type']),
            $row['category'],
            $row['amount'],
            $row['payment_method'],
            $row['notes'],
            $row['created_at']
        ]);
    }

    fclose($output);
    exit;
}

if ($type === 'emis') {
    $filename = "emis_export_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['ID', 'Loan Name', 'Lender', 'Principal Amount', 'Interest Rate (%)', 'Monthly EMI', 'Tenure (Months)', 'Start Date', 'Due Day', 'Outstanding Balance', 'Total Interest', 'Status', 'Notes']);

    $stmt = $db->prepare("SELECT * FROM emis WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$userId]);

    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['loan_name'],
            $row['lender'],
            $row['principal_amount'],
            $row['interest_rate'],
            $row['emi_amount'],
            $row['tenure_months'],
            $row['start_date'],
            $row['due_day'],
            $row['outstanding_balance'],
            $row['total_interest'],
            ucfirst($row['status']),
            $row['notes']
        ]);
    }

    fclose($output);
    exit;
}

die("Invalid export type.");

