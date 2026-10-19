<?php
/**
 * Customer Registration Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/I18n.php';
require_once __DIR__ . '/core/Auth.php';

// If already logged in, redirect to dashboard
if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$name = '';
$email = '';
$returnTo = $_GET['return_to'] ?? 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = __('auth_error_csrf');
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']);

        if (!$terms) {
            $error = __('auth_error_terms');
        } elseif ($pass !== $confirmPass) {
            $error = __('auth_error_password_match');
        } else {
            $result = Auth::register($name, $email, $pass);
            if ($result['success']) {
                $target = filter_var($returnTo, FILTER_SANITIZE_URL);
                header('Location: ' . ($target ?: 'dashboard.php'));
                exit;
            } else {
                $error = $result['error'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::getLang(); ?>" dir="<?php echo I18n::getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('auth_register_title'); ?> - <?php echo __('site_title'); ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Platform Stylesheet -->
    <link rel="stylesheet" href="css/platform.css">

    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
        }
        .auth-card {
            max-width: 480px;
            width: 100%;
            padding: 40px;
            text-align: <?php echo I18n::isRtl() ? 'right' : 'left'; ?>;
        }
    </style>
</head>
<body data-theme="luxury-cream" class="<?php echo I18n::getDir(); ?>">

    <!-- Top Navigation -->
    <nav class="platform-nav">
        <a href="index.php" class="platform-logo">
            <i class="fas fa-heart logo-icon"></i>
            <span class="logo-text"><?php echo __('site_title'); ?></span>
        </a>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="<?php echo I18n::getSwitcherUrl(); ?>" class="btn-romantic-outline btn-romantic-sm">
                <i class="fas fa-globe"></i> <?php echo __('other_lang_name'); ?>
            </a>
            <a href="login.php" class="btn-romantic btn-romantic-sm">
                <i class="fas fa-sign-in-alt"></i> <?php echo __('nav_login'); ?>
            </a>
        </div>
    </nav>

    <div class="auth-container">
        <div class="glass-box auth-card">
            
            <div style="text-align: center; margin-bottom: 25px;">
                <i class="fas fa-heart" style="font-size: 2.8rem; color: var(--theme-primary); margin-bottom: 12px;"></i>
                <h1 class="font-script" style="font-size: 2.6rem; color: var(--theme-deep); line-height: 1.1;">
                    <?php echo __('auth_register_title'); ?>
                </h1>
                <p style="color: var(--theme-text-muted); font-size: 0.95rem; margin-top: 6px;">
                    <?php echo __('auth_register_subtitle'); ?>
                </p>
            </div>

            <?php if (!empty($error)): ?>
            <div style="background: rgba(255, 82, 82, 0.12); border: 1px solid #ff5252; color: #d32f2f; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo Auth::csrfToken(); ?>">

                <div class="form-row">
                    <label class="form-label"><?php echo __('auth_full_name'); ?> *</label>
                    <input type="text" name="name" class="romantic-input" value="<?php echo htmlspecialchars($name); ?>" required placeholder="e.g. كريم أحمد / Kareem Ahmed">
                </div>

                <div class="form-row">
                    <label class="form-label"><?php echo __('auth_email'); ?> *</label>
                    <input type="email" name="email" class="romantic-input" value="<?php echo htmlspecialchars($email); ?>" required placeholder="name@example.com">
                </div>

                <div class="form-row">
                    <label class="form-label"><?php echo __('auth_password'); ?> (8+ أحرف) *</label>
                    <input type="password" name="password" class="romantic-input" required placeholder="••••••••">
                </div>

                <div class="form-row">
                    <label class="form-label"><?php echo __('auth_confirm_password'); ?> *</label>
                    <input type="password" name="confirm_password" class="romantic-input" required placeholder="••••••••">
                </div>

                <div class="form-row" style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="terms" id="terms" required style="width: 18px; height: 18px; accent-color: var(--theme-primary);">
                    <label for="terms" style="font-size: 0.85rem; color: var(--theme-text-muted); cursor: pointer;">
                        <?php echo __('auth_agree_terms'); ?>
                    </label>
                </div>

                <button type="submit" class="btn-romantic" style="width: 100%; margin-top: 10px; padding: 14px;">
                    <i class="fas fa-user-plus"></i> <?php echo __('auth_register_btn'); ?>
                </button>
            </form>

            <div style="text-align: center; margin-top: 25px; border-top: 1px solid var(--theme-glass-border); padding-top: 20px; font-size: 0.9rem; color: var(--theme-text-muted);">
                <?php echo __('auth_have_account'); ?>
                <a href="login.php?return_to=<?php echo urlencode($returnTo); ?>" style="color: var(--theme-primary); font-weight: 600; text-decoration: none;">
                    <?php echo __('auth_signin_now'); ?>
                </a>
            </div>

        </div>
    </div>

</body>
</html>
