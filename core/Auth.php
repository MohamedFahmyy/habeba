<?php
/**
 * Authentication & Security Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';

class Auth {

    public static function check(): bool {
        return !empty($_SESSION['user_id']);
    }

    public static function userId(): ?int {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }

    public static function isAdmin(): bool {
        return !empty($_SESSION['admin_logged_in']) || (self::check() && ($_SESSION['user_role'] ?? '') === 'admin');
    }

    public static function requireAuth(string $redirect = 'login.php'): void {
        if (!self::check()) {
            $returnTo = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
            header('Location: ' . $redirect . '?return_to=' . urlencode($returnTo));
            exit;
        }
    }

    public static function requireAdmin(string $redirect = 'admin.php'): void {
        if (!self::isAdmin()) {
            header('Location: ' . $redirect);
            exit;
        }
    }

    public static function register(string $name, string $email, string $password): array {
        $name = trim($name);
        $email = strtolower(trim($email));

        if (empty($name)) {
            return ['success' => false, 'error' => __('auth_error_invalid')];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => __('auth_error_invalid')];
        }

        if (mb_strlen($password) < 8) {
            return ['success' => false, 'error' => __('auth_error_password_length')];
        }

        $db = Database::getConnection();

        // Check unique email
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => __('auth_error_email_exists')];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (email, username, password_hash, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$email, $name, $hash]);

        $userId = (int)$db->lastInsertId();

        // Automatically log in
        self::loginUserSession($userId, $name, $email, 'user');

        return ['success' => true, 'user_id' => $userId];
    }

    public static function login(string $email, string $password): array {
        $email = strtolower(trim($email));

        // Rate limiting check per IP/Session
        if (self::isRateLimited()) {
            return ['success' => false, 'error' => 'Too many login attempts. Please wait 1 minute.'];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::recordFailedAttempt();
            return ['success' => false, 'error' => __('auth_error_invalid')];
        }

        self::clearFailedAttempts();
        self::loginUserSession((int)$user['id'], $user['username'], $user['email'], $user['role']);

        return ['success' => true, 'user' => $user];
    }

    private static function loginUserSession(int $id, string $name, string $email, string $role): void {
        if (!headers_sent()) {
            @session_regenerate_id(true);
        }
        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;

        // If user created gifts anonymously in this session, claim them
        if (!empty($_SESSION['editable_gifts']) && is_array($_SESSION['editable_gifts'])) {
            $db = Database::getConnection();
            $claimStmt = $db->prepare("UPDATE gift_websites SET user_id = ? WHERE id = ? AND edit_token = ?");
            foreach ($_SESSION['editable_gifts'] as $giftId => $token) {
                $claimStmt->execute([$id, $giftId, $token]);
            }
        }
    }

    public static function logout(): void {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role']);
        if (!headers_sent()) {
            @session_regenerate_id(true);
        }
    }

    // CSRF Protection
    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // Login Rate Limiter (Memory/Session based)
    private static function isRateLimited(): bool {
        $now = time();
        $attempts = $_SESSION['login_attempts'] ?? [];
        $recent = array_filter($attempts, fn($t) => ($now - $t) < 60);
        $_SESSION['login_attempts'] = $recent;
        return count($recent) >= 5;
    }

    private static function recordFailedAttempt(): void {
        $_SESSION['login_attempts'][] = time();
    }

    private static function clearFailedAttempts(): void {
        unset($_SESSION['login_attempts']);
    }
}
