<?php
/**
 * Authentication Helpers
 */

require_once __DIR__ . '/../config/app.php';

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function require_auth(): void {
    if (!is_logged_in()) {
        if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.', 'redirect' => 'login.php']);
            exit;
        }
        header("Location: login.php");
        exit;
    }
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function current_user(): ?array {
    static $cached_user = null;
    if ($cached_user !== null) {
        return $cached_user;
    }

    $userId = current_user_id();
    if (!$userId) {
        return null;
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT id, name, email, avatar, currency, currency_symbol, date_format, theme, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        // User no longer exists in db, clear session
        session_unset();
        session_destroy();
        return null;
    }

    $cached_user = $user;
    return $user;
}

function login_user(array $user): void {
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

