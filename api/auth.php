<?php
/**
 * Authentication API Endpoint
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Public actions: register, login
if ($action === 'register') {
    require_csrf_token();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        json_response(false, 'Please fill in all required fields.', [], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, 'Invalid email address format.', [], 400);
    }

    if (strlen($password) < 6) {
        json_response(false, 'Password must be at least 6 characters long.', [], 400);
    }

    if ($password !== $confirmPassword) {
        json_response(false, 'Passwords do not match.', [], 400);
    }

    $db = get_db();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response(false, 'An account with this email already exists.', [], 400);
    }

    // Hash password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $email, $hashedPassword])) {
        $userId = (int)$db->lastInsertId();

        // Create default welcome notification
        log_notification($userId, "Welcome to SmartSpend! 🎉", "Your account has been created. Start tracking your expenses and manage your EMIs effortlessly.", "success", "dashboard.php");

        // Automatically log the user in
        login_user([
            'id' => $userId,
            'name' => $name,
            'email' => $email
        ]);

        json_response(true, 'Registration successful! Redirecting to dashboard...', ['redirect' => 'dashboard.php']);
    } else {
        json_response(false, 'Failed to create account. Please try again.', [], 500);
    }
}

if ($action === 'login') {
    require_csrf_token();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        json_response(false, 'Please provide both email and password.', [], 400);
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        json_response(false, 'Invalid email or password.', [], 401);
    }

    // Successful login
    login_user($user);

    json_response(true, 'Login successful! Redirecting...', ['redirect' => 'dashboard.php']);
}

// Protected actions below require auth
require_auth();
$userId = current_user_id();

if ($action === 'update_profile') {
    require_csrf_token();
    
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        json_response(false, 'Name cannot be empty.', [], 400);
    }

    $db = get_db();
    $avatarPath = null;

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        try {
            $avatarPath = handle_file_upload($_FILES['avatar'], 'avatars', ['jpg', 'jpeg', 'png', 'webp']);
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 400);
        }
    }

    if ($avatarPath) {
        $stmt = $db->prepare("UPDATE users SET name = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$name, $avatarPath, $userId]);
    } else {
        $stmt = $db->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$name, $userId]);
    }

    $_SESSION['user_name'] = $name;
    json_response(true, 'Profile updated successfully!', ['name' => $name, 'avatar' => $avatarPath]);
}

if ($action === 'change_password') {
    require_csrf_token();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        json_response(false, 'Please fill in all password fields.', [], 400);
    }

    if (strlen($newPassword) < 6) {
        json_response(false, 'New password must be at least 6 characters long.', [], 400);
    }

    if ($newPassword !== $confirmPassword) {
        json_response(false, 'New passwords do not match.', [], 400);
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($currentPassword, $user['password'])) {
        json_response(false, 'Current password is incorrect.', [], 400);
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $userId]);

    json_response(true, 'Password changed successfully!');
}

if ($action === 'update_preferences') {
    require_csrf_token();

    $currency = trim($_POST['currency'] ?? 'INR');
    $currencySymbol = trim($_POST['currency_symbol'] ?? '₹');
    $dateFormat = trim($_POST['date_format'] ?? 'd M Y');
    $theme = trim($_POST['theme'] ?? 'light');

    $db = get_db();
    $stmt = $db->prepare("UPDATE users SET currency = ?, currency_symbol = ?, date_format = ?, theme = ? WHERE id = ?");
    $stmt->execute([$currency, $currencySymbol, $dateFormat, $theme, $userId]);

    json_response(true, 'Preferences saved successfully!');
}

json_response(false, 'Invalid request action.', [], 400);

