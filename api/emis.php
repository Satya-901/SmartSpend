<?php
/**
 * EMI & Loan Management API Endpoint
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();
header('Content-Type: application/json; charset=utf-8');

$userId = current_user_id();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db = get_db();

if ($action === 'add') {
    require_csrf_token();

    $loanName = trim($_POST['loan_name'] ?? '');
    $lender = trim($_POST['lender'] ?? '');
    $principal = (float)($_POST['principal_amount'] ?? 0);
    $interestRate = (float)($_POST['interest_rate'] ?? 0);
    $tenureMonths = (int)($_POST['tenure_months'] ?? 0);
    $customEmi = !empty($_POST['emi_amount']) ? (float)$_POST['emi_amount'] : 0;
    $startDate = trim($_POST['start_date'] ?? date('Y-m-d'));
    $dueDay = (int)($_POST['due_day'] ?? (int)date('d', strtotime($startDate)));
    $notes = trim($_POST['notes'] ?? '');

    if (empty($loanName) || empty($lender)) {
        json_response(false, 'Loan name and lender are required.', [], 400);
    }

    if ($principal <= 0 || $tenureMonths <= 0) {
        json_response(false, 'Principal amount and tenure in months must be greater than zero.', [], 400);
    }

    if ($dueDay < 1 || $dueDay > 31) {
        $dueDay = (int)date('d', strtotime($startDate));
    }

    // Calculate EMI if not explicitly provided
    if ($customEmi > 0) {
        $emiAmount = $customEmi;
    } else {
        if ($interestRate > 0) {
            $monthlyRate = ($interestRate / 12) / 100;
            $pow = pow(1 + $monthlyRate, $tenureMonths);
            $emiAmount = ($principal * $monthlyRate * $pow) / ($pow - 1);
        } else {
            $emiAmount = $principal / $tenureMonths;
        }
    }

    $emiAmount = round($emiAmount, 2);
    $totalPayable = $emiAmount * $tenureMonths;
    $totalInterest = max(0, $totalPayable - $principal);
    $outstandingBalance = $principal;

    $stmt = $db->prepare("
        INSERT INTO emis (user_id, loan_name, lender, principal_amount, interest_rate, emi_amount, tenure_months, start_date, due_day, outstanding_balance, total_interest, status, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
    ");

    $success = $stmt->execute([
        $userId, $loanName, $lender, $principal, $interestRate, $emiAmount,
        $tenureMonths, $startDate, $dueDay, $outstandingBalance, $totalInterest, $notes
    ]);

    if ($success) {
        $emiId = (int)$db->lastInsertId();

        // Check if there are already initial payments indicated
        $initialPaid = (int)($_POST['initial_paid_installments'] ?? 0);
        if ($initialPaid > 0 && $initialPaid < $tenureMonths) {
            for ($i = 1; $i <= $initialPaid; $i++) {
                $payDate = date('Y-m-d', strtotime("+$i month", strtotime($startDate)));
                $intComp = round($outstandingBalance * (($interestRate / 12) / 100), 2);
                $princComp = min($emiAmount - $intComp, $outstandingBalance);
                $outstandingBalance = max(0, $outstandingBalance - $princComp);

                $stmtPay = $db->prepare("
                    INSERT INTO emi_payments (emi_id, user_id, installment_number, payment_date, amount_paid, principal_component, interest_component, payment_method, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Bank Transfer', 'Past installment recorded at setup')
                ");
                $stmtPay->execute([$emiId, $userId, $i, $payDate, $emiAmount, $princComp, $intComp]);
            }

            // Update balance after initial payments
            $stmtUp = $db->prepare("UPDATE emis SET outstanding_balance = ? WHERE id = ?");
            $stmtUp->execute([$outstandingBalance, $emiId]);
        }

        json_response(true, 'EMI / Loan created successfully!', ['emi_id' => $emiId]);
    } else {
        json_response(false, 'Failed to create EMI.', [], 500);
    }
}

if ($action === 'record_payment') {
    require_csrf_token();

    $emiId = (int)($_POST['emi_id'] ?? 0);
    $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
    $amountPaid = (float)($_POST['amount_paid'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? 'Bank Transfer');
    $notes = trim($_POST['notes'] ?? '');

    // Fetch EMI details
    $stmt = $db->prepare("SELECT * FROM emis WHERE id = ? AND user_id = ?");
    $stmt->execute([$emiId, $userId]);
    $emi = $stmt->fetch();

    if (!$emi) {
        json_response(false, 'EMI record not found.', [], 404);
    }

    if ($emi['status'] !== 'active') {
        json_response(false, 'This EMI is marked as ' . $emi['status'] . '. Cannot record payments.', [], 400);
    }

    if ($amountPaid <= 0) {
        $amountPaid = (float)$emi['emi_amount'];
    }

    // Determine next installment number
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM emi_payments WHERE emi_id = ?");
    $stmtCount->execute([$emiId]);
    $paidCount = (int)$stmtCount->fetchColumn();
    $nextInstallment = $paidCount + 1;

    if ($nextInstallment > (int)$emi['tenure_months']) {
        json_response(false, 'All installments for this loan have already been completed.', [], 400);
    }

    // Double check duplicate installment
    $stmtDup = $db->prepare("SELECT id FROM emi_payments WHERE emi_id = ? AND installment_number = ?");
    $stmtDup->execute([$emiId, $nextInstallment]);
    if ($stmtDup->fetch()) {
        json_response(false, 'Installment #' . $nextInstallment . ' has already been recorded.', [], 400);
    }

    // Calculate interest & principal components
    $interestRate = (float)$emi['interest_rate'];
    $currentBalance = (float)$emi['outstanding_balance'];
    $monthlyRate = ($interestRate / 12) / 100;
    
    $interestComponent = round($currentBalance * $monthlyRate, 2);
    $principalComponent = max(0, min($amountPaid - $interestComponent, $currentBalance));
    $newBalance = max(0, $currentBalance - $principalComponent);

    // Find or create 'EMI & Loans' category for expenses
    $stmtCat = $db->prepare("SELECT id FROM categories WHERE name LIKE '%EMI%' LIMIT 1");
    $stmtCat->execute();
    $catId = $stmtCat->fetchColumn() ?: null;

    $db->beginTransaction();
    try {
        // 1. Record transaction in transactions table
        $txDesc = "EMI Payment: " . $emi['loan_name'] . " (" . $emi['lender'] . ") - Inst. #" . $nextInstallment . "/" . $emi['tenure_months'];
        $stmtTx = $db->prepare("
            INSERT INTO transactions (user_id, category_id, type, amount, date, time, payment_method, notes, emi_id)
            VALUES (?, ?, 'expense', ?, ?, CURTIME(), ?, ?, ?)
        ");
        $stmtTx->execute([$userId, $catId, $amountPaid, $paymentDate, $paymentMethod, $txDesc, $emiId]);
        $txId = (int)$db->lastInsertId();

        // 2. Record in emi_payments
        $stmtPay = $db->prepare("
            INSERT INTO emi_payments (emi_id, user_id, installment_number, payment_date, amount_paid, principal_component, interest_component, payment_method, transaction_id, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtPay->execute([$emiId, $userId, $nextInstallment, $paymentDate, $amountPaid, $principalComponent, $interestComponent, $paymentMethod, $txId, $notes]);

        // 3. Update EMI balance and status
        $newStatus = ($newBalance <= 0 || $nextInstallment >= (int)$emi['tenure_months']) ? 'completed' : 'active';
        $stmtUpdate = $db->prepare("UPDATE emis SET outstanding_balance = ?, status = ? WHERE id = ?");
        $stmtUpdate->execute([$newBalance, $newStatus, $emiId]);

        $db->commit();

        json_response(true, "Installment #{$nextInstallment} of " . format_currency($amountPaid) . " recorded successfully!", [
            'installment_number' => $nextInstallment,
            'new_balance' => $newBalance,
            'formatted_new_balance' => format_currency($newBalance),
            'status' => $newStatus
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        json_response(false, 'Failed to record payment: ' . $e->getMessage(), [], 500);
    }
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM emis WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $emi = $stmt->fetch();

    if (!$emi) {
        json_response(false, 'EMI not found.', [], 404);
    }

    // Fetch payments
    $stmtPay = $db->prepare("SELECT * FROM emi_payments WHERE emi_id = ? ORDER BY installment_number ASC");
    $stmtPay->execute([$id]);
    $payments = $stmtPay->fetchAll();

    $paidCount = count($payments);
    $remainingMonths = max(0, (int)$emi['tenure_months'] - $paidCount);
    $paidAmountTotal = array_sum(array_column($payments, 'amount_paid'));
    $progressPct = ($emi['tenure_months'] > 0) ? round(($paidCount / $emi['tenure_months']) * 100, 1) : 0;

    $emi['paid_count'] = $paidCount;
    $emi['remaining_months'] = $remainingMonths;
    $emi['paid_amount_total'] = $paidAmountTotal;
    $emi['progress_pct'] = $progressPct;
    $emi['formatted_principal'] = format_currency($emi['principal_amount']);
    $emi['formatted_emi'] = format_currency($emi['emi_amount']);
    $emi['formatted_outstanding'] = format_currency($emi['outstanding_balance']);
    $emi['formatted_total_interest'] = format_currency($emi['total_interest']);

    json_response(true, 'EMI details retrieved.', [
        'emi' => $emi,
        'payments' => $payments
    ]);
}

if ($action === 'edit') {
    require_csrf_token();

    $id = (int)($_POST['id'] ?? 0);
    $loanName = trim($_POST['loan_name'] ?? '');
    $lender = trim($_POST['lender'] ?? '');
    $dueDay = (int)($_POST['due_day'] ?? 1);
    $status = trim($_POST['status'] ?? 'active');
    $notes = trim($_POST['notes'] ?? '');

    $stmtCheck = $db->prepare("SELECT id FROM emis WHERE id = ? AND user_id = ?");
    $stmtCheck->execute([$id, $userId]);
    if (!$stmtCheck->fetch()) {
        json_response(false, 'EMI not found.', [], 404);
    }

    if (!in_array($status, ['active', 'completed', 'closed'], true)) {
        $status = 'active';
    }

    $stmt = $db->prepare("
        UPDATE emis SET loan_name = ?, lender = ?, due_day = ?, status = ?, notes = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$loanName, $lender, $dueDay, $status, $notes, $id, $userId]);

    json_response(true, 'EMI details updated successfully!');
}

if ($action === 'delete') {
    require_csrf_token();

    $id = (int)($_POST['id'] ?? 0);
    $stmtCheck = $db->prepare("SELECT id FROM emis WHERE id = ? AND user_id = ?");
    $stmtCheck->execute([$id, $userId]);
    if (!$stmtCheck->fetch()) {
        json_response(false, 'EMI not found.', [], 404);
    }

    $stmt = $db->prepare("DELETE FROM emis WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    json_response(true, 'EMI and associated payment history removed successfully.');
}

if ($action === 'list') {
    $status = trim($_GET['status'] ?? 'all');

    $sql = "SELECT * FROM emis WHERE user_id = ?";
    $params = [$userId];

    if ($status === 'active' || $status === 'completed' || $status === 'closed') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY status ASC, id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $emis = $stmt->fetchAll();

    $totalMonthlyCommitment = 0;
    $totalOutstanding = 0;
    $activeCount = 0;

    foreach ($emis as &$emi) {
        // Count paid installments
        $stmtPay = $db->prepare("SELECT COUNT(*), COALESCE(SUM(amount_paid), 0) FROM emi_payments WHERE emi_id = ?");
        $stmtPay->execute([$emi['id']]);
        list($paidCount, $paidSum) = $stmtPay->fetch(PDO::FETCH_NUM);

        $emi['paid_installments'] = (int)$paidCount;
        $emi['remaining_months'] = max(0, (int)$emi['tenure_months'] - (int)$paidCount);
        $emi['progress_pct'] = ((int)$emi['tenure_months'] > 0) ? round(((int)$paidCount / (int)$emi['tenure_months']) * 100) : 0;
        $emi['formatted_emi'] = format_currency($emi['emi_amount']);
        $emi['formatted_balance'] = format_currency($emi['outstanding_balance']);
        $emi['formatted_principal'] = format_currency($emi['principal_amount']);

        // Check if paid this current month
        $curMonth = (int)date('m');
        $curYear = (int)date('Y');
        $stmtCur = $db->prepare("SELECT id FROM emi_payments WHERE emi_id = ? AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?");
        $stmtCur->execute([$emi['id'], $curMonth, $curYear]);
        $emi['paid_this_month'] = (bool)$stmtCur->fetch();

        if ($emi['status'] === 'active') {
            $totalMonthlyCommitment += (float)$emi['emi_amount'];
            $totalOutstanding += (float)$emi['outstanding_balance'];
            $activeCount++;
        }
    }

    json_response(true, 'EMIs retrieved.', [
        'emis' => $emis,
        'active_count' => $activeCount,
        'total_monthly_commitment' => $totalMonthlyCommitment,
        'formatted_total_commitment' => format_currency($totalMonthlyCommitment),
        'total_outstanding' => $totalOutstanding,
        'formatted_total_outstanding' => format_currency($totalOutstanding)
    ]);
}

json_response(false, 'Invalid request action.', [], 400);

