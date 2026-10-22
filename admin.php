<?php
/**
 * Platform Administration & Content / Payment Moderation Dashboard
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/I18n.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/GiftService.php';
require_once __DIR__ . '/core/PaymentService.php';

$error = '';
$message = '';

// Handle Admin Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

// Handle Admin Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $password = $_POST['admin_password'] ?? '';
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'كلمة مرور المشرف غير صحيحة.';
    }
}

$isAdmin = !empty($_SESSION['admin_logged_in']) || (Auth::check() && ($_SESSION['user_role'] ?? '') === 'admin');

if ($isAdmin) {
    $db = Database::getConnection();

    // 1. Handle Payment Review Actions (Approve / Reject)
    if (isset($_POST['action_payment'])) {
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            $error = __('auth_error_csrf');
        } else {
            $paymentId = (int)$_POST['payment_id'];
            $decision = $_POST['action_payment']; // 'approve' or 'reject'
            $adminId = Auth::userId() ?: 1;
            $notes = trim($_POST['admin_notes'] ?? '');

            if ($decision === 'approve') {
                if (PaymentService::approvePayment($paymentId, $adminId, $notes)) {
                    $message = 'تمت الموافقة على التحويل وتفعيل الهدية للنشر بنجاح!';
                } else {
                    $error = 'فشلت عملية اعتماد التحويل.';
                }
            } elseif ($decision === 'reject') {
                if (PaymentService::rejectPayment($paymentId, $adminId, $notes)) {
                    $message = 'تم رفض التحويل وتحديث حالة الهدية إلى غير مدفوع.';
                } else {
                    $error = 'فشلت عملية رفض التحويل.';
                }
            }
        }
    }

    // 2. Handle Settings Update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            $error = __('auth_error_csrf');
        } else {
            PaymentService::setSetting('payments_enabled', isset($_POST['payments_enabled']) ? '1' : '0');
            PaymentService::setSetting('payment_price', trim($_POST['payment_price'] ?? '149'));
            PaymentService::setSetting('payment_currency', trim($_POST['payment_currency'] ?? 'EGP'));
            PaymentService::setSetting('instapay_enabled', isset($_POST['instapay_enabled']) ? '1' : '0');
            PaymentService::setSetting('instapay_address', trim($_POST['instapay_address'] ?? ''));
            PaymentService::setSetting('instapay_instructions', trim($_POST['instapay_instructions'] ?? ''));
            PaymentService::setSetting('vodafone_cash_enabled', isset($_POST['vodafone_cash_enabled']) ? '1' : '0');
            PaymentService::setSetting('vodafone_cash_number', trim($_POST['vodafone_cash_number'] ?? ''));
            PaymentService::setSetting('vodafone_cash_instructions', trim($_POST['vodafone_cash_instructions'] ?? ''));
            PaymentService::setSetting('card_enabled', isset($_POST['card_enabled']) ? '1' : '0');

            $message = 'تم حفظ إعدادات المنصة وطرق الدفع بنجاح!';
        }
    }

    // 3. Handle Gift Actions (Toggle status / Delete)
    if (isset($_GET['toggle_status'])) {
        $giftId = (int)$_GET['toggle_status'];
        $stmt = $db->prepare("SELECT status FROM gift_websites WHERE id = ?");
        $stmt->execute([$giftId]);
        $row = $stmt->fetch();
        if ($row) {
            $newStatus = ($row['status'] === 'published') ? 'draft' : 'published';
            $db->prepare("UPDATE gift_websites SET status = ? WHERE id = ?")->execute([$newStatus, $giftId]);
            $message = "تم تغيير حالة الهدية إلى '$newStatus'.";
        }
    }

    if (isset($_GET['delete_gift'])) {
        $giftId = (int)$_GET['delete_gift'];
        $stmt = $db->prepare("SELECT edit_token FROM gift_websites WHERE id = ?");
        $stmt->execute([$giftId]);
        $token = $stmt->fetchColumn();
        if ($token) {
            GiftService::deleteGift($giftId, null, $token);
            $message = "تم حذف الهدية #$giftId بنجاح.";
        }
    }

    // Metrics
    $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalGifts = (int)$db->query("SELECT COUNT(*) FROM gift_websites")->fetchColumn();
    $pendingPaymentsCount = (int)$db->query("SELECT COUNT(*) FROM payments WHERE status = 'awaiting_review'")->fetchColumn();
    $publishedGifts = (int)$db->query("SELECT COUNT(*) FROM gift_websites WHERE status = 'published'")->fetchColumn();

    // Data Lists
    $search = trim($_GET['search'] ?? '');
    $gifts = GiftService::listAllGiftsAdmin(100, $search);
    $users = $db->query("SELECT u.*, (SELECT COUNT(*) FROM gift_websites WHERE user_id = u.id) as gifts_count FROM users u ORDER BY u.created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    $pendingPayments = PaymentService::listPayments('awaiting_review');
    $allPayments = PaymentService::listPayments();
    $settings = PaymentService::getAllSettings();
}
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::getLang(); ?>" dir="<?php echo I18n::getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('adm_title'); ?> - <?php echo __('site_title'); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Platform Stylesheet -->
    <link rel="stylesheet" href="css/platform.css">

    <style>
        .admin-nav-tabs {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid var(--theme-glass-border);
            margin-bottom: 30px;
            overflow-x: auto;
        }

        .adm-tab-btn {
            padding: 12px 22px;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 700;
            color: var(--theme-text-muted);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .adm-tab-btn.active, .adm-tab-btn:hover {
            color: var(--theme-primary);
            border-bottom-color: var(--theme-primary);
        }

        .adm-tab-pane {
            display: none;
        }

        .adm-tab-pane.active {
            display: block;
        }

        .admin-stat-card {
            background: var(--theme-card-bg);
            border: 1px solid var(--theme-glass-border);
            padding: 24px;
            border-radius: 18px;
            box-shadow: var(--shadow-soft);
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--theme-card-bg);
            border: 1px solid var(--theme-glass-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-soft);
        }

        .admin-table th, .admin-table td {
            padding: 14px 18px;
            text-align: <?php echo I18n::isRtl() ? 'right' : 'left'; ?>;
            border-bottom: 1px solid var(--theme-glass-border);
            font-size: 0.92rem;
        }

        .admin-table th {
            background: rgba(84, 40, 56, 0.05);
            font-weight: 700;
            color: var(--theme-deep);
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .badge-published {
            background: rgba(76, 175, 80, 0.15);
            color: #2E7D32;
        }

        .badge-draft {
            background: rgba(255, 152, 0, 0.15);
            color: #EF6C00;
        }
    </style>
</head>
<body data-theme="luxury-cream" class="<?php echo I18n::getDir(); ?>">

    <!-- Top Platform Navigation -->
    <nav class="platform-nav">
        <a href="index.php" class="platform-logo">
            <i class="fas fa-heart logo-icon"></i>
            <span class="logo-text"><?php echo __('site_title'); ?></span>
        </a>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="<?php echo I18n::getSwitcherUrl(); ?>" class="btn-romantic-outline btn-romantic-sm">
                <i class="fas fa-globe"></i> <?php echo __('other_lang_name'); ?>
            </a>
            <a href="index.php" class="btn-romantic-outline btn-romantic-sm">
                <i class="fas fa-home"></i> <?php echo __('nav_home'); ?>
            </a>
            <?php if ($isAdmin): ?>
            <a href="admin.php?action=logout" class="btn-romantic-outline btn-romantic-sm" style="border-color: #ff5252; color: #ff5252 !important;">
                <i class="fas fa-sign-out-alt"></i> <?php echo __('nav_logout'); ?>
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <div style="max-width: 1250px; margin: 110px auto 60px; padding: 0 20px;">
        
        <?php if (!$isAdmin): ?>
        <!-- Admin Login Screen -->
        <div style="max-width: 440px; margin: 80px auto;">
            <div class="glass-box" style="padding: 40px; text-align: center;">
                <i class="fas fa-user-shield" style="font-size: 3.5rem; color: var(--theme-primary); margin-bottom: 20px;"></i>
                <h2 class="font-script" style="font-size: 3rem; color: var(--theme-deep); margin-bottom: 10px;">
                    <?php echo __('adm_title'); ?>
                </h2>
                <p style="color: var(--theme-text-muted); font-size: 0.95rem; margin-bottom: 25px;">
                    أدخل كلمة المرور الإدارية للوصول إلى لوحة التحكم وإدارة المدفوعات.
                </p>

                <?php if (!empty($error)): ?>
                <div style="background: rgba(255, 82, 82, 0.15); border: 1px solid #ff5252; color: #d32f2f; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div style="margin-bottom: 20px;">
                        <input type="password" name="admin_password" class="romantic-input" placeholder="Admin Password" required style="text-align: center;">
                    </div>
                    <button type="submit" name="admin_login" class="btn-romantic" style="width: 100%;">
                        <i class="fas fa-lock-open"></i> الدخول للوحة الإدارة
                    </button>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- Authenticated Admin Dashboard -->

        <?php if (!empty($message)): ?>
        <div style="background: rgba(76, 175, 80, 0.15); border: 1px solid #4caf50; color: #2e7d32; padding: 14px 20px; border-radius: 12px; margin-bottom: 25px;">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
        <div style="background: rgba(255, 82, 82, 0.15); border: 1px solid #ff5252; color: #d32f2f; padding: 14px 20px; border-radius: 12px; margin-bottom: 25px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 class="font-script" style="font-size: 3.5rem; color: var(--theme-deep); line-height: 1;">
                    <?php echo __('adm_title'); ?>
                </h1>
                <p style="color: var(--theme-text-muted); font-size: 1.05rem; margin-top: 5px;">
                    إدارة حسابات المستخدمين، الهدايا، مراجعة تحويلات الدفع، وإعدادات المنصة.
                </p>
            </div>
        </div>

        <!-- Metric Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 20px; margin-bottom: 35px;">
            <div class="admin-stat-card">
                <i class="fas fa-users" style="font-size: 2rem; color: #2196F3;"></i>
                <div>
                    <div style="font-size: 1.7rem; font-weight: 700; color: var(--theme-deep);"><?php echo $totalUsers; ?></div>
                    <div style="font-size: 0.85rem; color: var(--theme-text-muted);"><?php echo __('adm_stat_users'); ?></div>
                </div>
            </div>

            <div class="admin-stat-card">
                <i class="fas fa-gift" style="font-size: 2rem; color: var(--theme-primary);"></i>
                <div>
                    <div style="font-size: 1.7rem; font-weight: 700; color: var(--theme-deep);"><?php echo $totalGifts; ?></div>
                    <div style="font-size: 0.85rem; color: var(--theme-text-muted);"><?php echo __('adm_stat_gifts'); ?></div>
                </div>
            </div>

            <div class="admin-stat-card">
                <i class="fas fa-hourglass-half" style="font-size: 2rem; color: #FF9800;"></i>
                <div>
                    <div style="font-size: 1.7rem; font-weight: 700; color: #EF6C00;"><?php echo $pendingPaymentsCount; ?></div>
                    <div style="font-size: 0.85rem; color: var(--theme-text-muted);"><?php echo __('adm_stat_pending_pay'); ?></div>
                </div>
            </div>

            <div class="admin-stat-card">
                <i class="fas fa-check-double" style="font-size: 2rem; color: #4CAF50;"></i>
                <div>
                    <div style="font-size: 1.7rem; font-weight: 700; color: #2E7D32;"><?php echo $publishedGifts; ?></div>
                    <div style="font-size: 0.85rem; color: var(--theme-text-muted);"><?php echo __('dash_stat_published'); ?></div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="admin-nav-tabs">
            <button class="adm-tab-btn active" data-tab="tab-payments">
                <i class="fas fa-money-check-alt"></i> <?php echo __('adm_nav_payments'); ?>
                <?php if ($pendingPaymentsCount > 0): ?>
                <span style="background: #E53935; color: #FFF; font-size: 0.75rem; padding: 2px 8px; border-radius: 12px;"><?php echo $pendingPaymentsCount; ?></span>
                <?php endif; ?>
            </button>
            <button class="adm-tab-btn" data-tab="tab-gifts">
                <i class="fas fa-gift"></i> <?php echo __('adm_nav_gifts'); ?>
            </button>
            <button class="adm-tab-btn" data-tab="tab-users">
                <i class="fas fa-users"></i> <?php echo __('adm_nav_users'); ?>
            </button>
            <button class="adm-tab-btn" data-tab="tab-settings">
                <i class="fas fa-cog"></i> <?php echo __('adm_nav_settings'); ?>
            </button>
        </div>

        <!-- TAB 1: PAYMENTS QUEUE -->
        <div class="adm-tab-pane active" id="tab-payments">
            <h3 style="font-size: 1.3rem; color: var(--theme-deep); margin-bottom: 20px;">
                طلبات التحويل بانتظار المراجعة (InstaPay & Vodafone Cash)
            </h3>

            <?php if (empty($pendingPayments)): ?>
            <div class="glass-box" style="padding: 40px; text-align: center; color: var(--theme-text-muted);">
                <i class="fas fa-check-circle" style="font-size: 2.5rem; color: #4CAF50; margin-bottom: 10px;"></i>
                <p>لا توجد أي تحويلات معلقة للمراجعة حالياً.</p>
            </div>
            <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>الهدية</th>
                            <th>وسيلة الدفع</th>
                            <th>رقم العملية / المحفظة</th>
                            <th>المبلغ</th>
                            <th>الإيصال</th>
                            <th>التاريخ</th>
                            <th style="text-align: center;">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingPayments as $p): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($p['user_name']); ?></strong>
                                <div style="font-size: 0.8rem; color: var(--theme-text-muted);"><?php echo htmlspecialchars($p['user_email']); ?></div>
                            </td>
                            <td>
                                <a href="gift.php?slug=<?php echo urlencode($p['gift_slug']); ?>" target="_blank" style="color: var(--theme-deep); font-weight: 600;">
                                    <?php echo htmlspecialchars($p['gift_title']); ?>
                                </a>
                            </td>
                            <td>
                                <span style="font-weight: 600; text-transform: uppercase;"><?php echo htmlspecialchars($p['provider']); ?></span>
                            </td>
                            <td>
                                <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($p['transaction_reference']); ?></code>
                            </td>
                            <td>
                                <strong><?php echo $p['amount']; ?> <?php echo htmlspecialchars($p['currency']); ?></strong>
                            </td>
                            <td>
                                <?php if (!empty($p['receipt_path'])): ?>
                                <a href="<?php echo htmlspecialchars($p['receipt_path']); ?>" target="_blank" class="btn-romantic-outline btn-romantic-sm">
                                    <i class="fas fa-image"></i> عرض الإيصال
                                </a>
                                <?php else: ?>
                                <span style="color: #888;">بدون صورة</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--theme-text-muted);">
                                <?php echo date('M j, Y H:i', strtotime($p['created_at'])); ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <form method="POST" style="display: inline-flex; gap: 6px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo Auth::csrfToken(); ?>">
                                    <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" name="action_payment" value="approve" class="btn-romantic btn-romantic-sm" style="background: #4CAF50;">
                                        <i class="fas fa-check"></i> اعتماد وتفعيل
                                    </button>
                                    <button type="submit" name="action_payment" value="reject" class="btn-romantic-outline btn-romantic-sm" style="color: #ff5252 !important; border-color: #ff5252;">
                                        <i class="fas fa-times"></i> رفض
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Previous Payments Archive -->
            <div style="margin-top: 45px; padding-top: 25px; border-top: 1px solid var(--theme-glass-border);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h3 style="font-size: 1.25rem; color: var(--theme-deep); margin: 0;">
                        <i class="fas fa-history" style="color: var(--theme-primary);"></i> سجل كافة المدفوعات السابقة والمعتمدة
                    </h3>
                    <span style="font-size: 0.85rem; color: var(--theme-text-muted);">
                        إجمالي المعاملات: <?php echo count($allPayments); ?>
                    </span>
                </div>

                <?php if (empty($allPayments)): ?>
                <div class="glass-box" style="padding: 30px; text-align: center; color: var(--theme-text-muted); font-size: 0.95rem;">
                    لا توجد أي معاملات سابقة مسجلة.
                </div>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>العميل</th>
                                <th>الهدية</th>
                                <th>وسيلة الدفع</th>
                                <th>المبلغ</th>
                                <th>رقم العملية (Ref)</th>
                                <th>الإيصال</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allPayments as $ap): ?>
                            <tr>
                                <td><?php echo $ap['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($ap['user_name'] ?? ''); ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--theme-text-muted);"><?php echo htmlspecialchars($ap['user_email'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <a href="gift.php?slug=<?php echo urlencode($ap['gift_slug'] ?? ''); ?>" target="_blank" style="color: var(--theme-deep); font-weight: 600;">
                                        <?php echo htmlspecialchars($ap['gift_title'] ?? ('#' . $ap['gift_id'])); ?>
                                    </a>
                                </td>
                                <td>
                                    <span style="font-weight: 600; text-transform: uppercase;"><?php echo htmlspecialchars($ap['provider']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo $ap['amount']; ?> <?php echo htmlspecialchars($ap['currency']); ?></strong>
                                </td>
                                <td>
                                    <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($ap['transaction_reference'] ?? '-'); ?></code>
                                </td>
                                <td>
                                    <?php if (!empty($ap['receipt_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($ap['receipt_path']); ?>" target="_blank" class="btn-romantic-outline btn-romantic-sm">
                                        <i class="fas fa-image"></i> عرض الإيصال
                                    </a>
                                    <?php else: ?>
                                    <span style="color: #999; font-size: 0.8rem;">بدون إيصال</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-status <?php echo ($ap['status'] === 'approved') ? 'badge-published' : (($ap['status'] === 'rejected') ? 'badge-draft' : ''); ?>" style="<?php echo ($ap['status'] === 'approved') ? 'background: #e8f5e9; color: #2e7d32;' : (($ap['status'] === 'rejected') ? 'background: #ffebee; color: #c62828;' : 'background: #fff8e1; color: #f57f17;'); ?>">
                                        <?php 
                                            echo ($ap['status'] === 'approved') ? 'معتمد (Approved)' : (($ap['status'] === 'rejected') ? 'مرفوض (Rejected)' : 'قيد المراجعة'); 
                                        ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.8rem; color: var(--theme-text-muted);">
                                    <?php echo date('Y-m-d H:i', strtotime($ap['created_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 2: GIFTS LIST -->
        <div class="adm-tab-pane" id="tab-gifts">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 1.3rem; color: var(--theme-deep);">كل الهدايا المسجلة</h3>
                <form method="GET" style="display: flex; gap: 8px;">
                    <input type="text" name="search" class="romantic-input" placeholder="بحث بالاسم أو الرابط..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 6px 12px; font-size: 0.85rem;">
                    <button type="submit" class="btn-romantic btn-romantic-sm"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>الهدية</th>
                            <th>المالك</th>
                            <th>الشريكان</th>
                            <th>القالب</th>
                            <th>الحالة</th>
                            <th>الدفع</th>
                            <th style="text-align: center;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gifts as $g): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($g['title']); ?></strong>
                                <div style="font-size: 0.78rem; color: var(--theme-text-muted);">/gift/<?php echo htmlspecialchars($g['slug']); ?></div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($g['owner_name'] ?: 'Demo/Guest'); ?>
                                <div style="font-size: 0.75rem; color: var(--theme-text-muted);"><?php echo htmlspecialchars($g['owner_email'] ?? ''); ?></div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($g['sender_name']); ?> ❤️ <?php echo htmlspecialchars($g['recipient_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($g['theme_id']); ?></td>
                            <td>
                                <span class="badge-status <?php echo ($g['status'] === 'published') ? 'badge-published' : 'badge-draft'; ?>">
                                    <?php echo ucfirst($g['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.82rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($g['payment_status'] ?? 'free'); ?>
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="gift.php?slug=<?php echo urlencode($g['slug']); ?>" target="_blank" class="btn-romantic-outline btn-romantic-sm" title="معاينة"><i class="fas fa-eye"></i></a>
                                <a href="editor.php?id=<?php echo $g['id']; ?>&token=<?php echo htmlspecialchars($g['edit_token']); ?>" class="btn-romantic-outline btn-romantic-sm" title="تعديل"><i class="fas fa-pencil-alt"></i></a>
                                <a href="admin.php?toggle_status=<?php echo $g['id']; ?>" class="btn-romantic-outline btn-romantic-sm" title="تغيير النشر"><i class="fas fa-toggle-on"></i></a>
                                <a href="admin.php?delete_gift=<?php echo $g['id']; ?>" onclick="return confirm('حذف نهائي؟');" class="btn-romantic-outline btn-romantic-sm" style="color: #ff5252 !important; border-color: #ff5252;" title="حذف"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 3: USERS LIST -->
        <div class="adm-tab-pane" id="tab-users">
            <h3 style="font-size: 1.3rem; color: var(--theme-deep); margin-bottom: 20px;">حسابات العملاء المسجلين</h3>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>الدور</th>
                            <th>الهدايا المنشأة</th>
                            <th>تاريخ التسجيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><span class="badge-status" style="background: rgba(0,0,0,0.06);"><?php echo htmlspecialchars($u['role']); ?></span></td>
                            <td><strong><?php echo (int)$u['gifts_count']; ?></strong> هدية</td>
                            <td style="font-size: 0.82rem; color: var(--theme-text-muted);"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 4: SETTINGS -->
        <div class="adm-tab-pane" id="tab-settings">
            <h3 style="font-size: 1.3rem; color: var(--theme-deep); margin-bottom: 20px;">إعدادات الدفع والنظام</h3>
            
            <div class="glass-box" style="padding: 30px; max-width: 800px;">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo Auth::csrfToken(); ?>">

                    <div style="background: #E8F5E9; border: 1px solid #C8E6C9; padding: 16px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <strong><?php echo __('adm_setting_enable_payments'); ?></strong>
                            <div style="font-size: 0.85rem; color: #2E7D32;">في وضع الاختبار الحالي الدفع معطل ويتاح النشر مجاناً. تفعيل هذا الخيار سيمنع النشر حتى يتم تأكيد الدفع.</div>
                        </div>
                        <input type="checkbox" name="payments_enabled" style="width: 22px; height: 22px; accent-color: var(--theme-primary);" <?php if (($settings['payments_enabled'] ?? '0') === '1') echo 'checked'; ?>>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                        <div class="form-row">
                            <label class="form-label"><?php echo __('adm_setting_price'); ?></label>
                            <input type="number" name="payment_price" class="romantic-input" value="<?php echo htmlspecialchars($settings['payment_price'] ?? '149'); ?>">
                        </div>
                        <div class="form-row">
                            <label class="form-label"><?php echo __('adm_setting_currency'); ?></label>
                            <input type="text" name="payment_currency" class="romantic-input" value="<?php echo htmlspecialchars($settings['payment_currency'] ?? 'EGP'); ?>">
                        </div>
                    </div>

                    <hr style="border: 0; border-top: 1px solid var(--theme-glass-border); margin: 25px 0;">

                    <!-- InstaPay Config -->
                    <div style="margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <strong><?php echo __('adm_setting_instapay'); ?></strong>
                            <input type="checkbox" name="instapay_enabled" style="width: 20px; height: 20px; accent-color: var(--theme-primary);" <?php if (($settings['instapay_enabled'] ?? '1') === '1') echo 'checked'; ?>>
                        </div>
                        <div class="form-row">
                            <label class="form-label">عنوان انستاباي (InstaPay Address)</label>
                            <input type="text" name="instapay_address" class="romantic-input" value="<?php echo htmlspecialchars($settings['instapay_address'] ?? 'habeba@instapay'); ?>">
                        </div>
                        <div class="form-row">
                            <label class="form-label">تعليمات التحويل للعميل</label>
                            <textarea name="instapay_instructions" rows="2" class="romantic-textarea"><?php echo htmlspecialchars($settings['instapay_instructions'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <hr style="border: 0; border-top: 1px solid var(--theme-glass-border); margin: 25px 0;">

                    <!-- Vodafone Cash Config -->
                    <div style="margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <strong><?php echo __('adm_setting_vodafone'); ?></strong>
                            <input type="checkbox" name="vodafone_cash_enabled" style="width: 20px; height: 20px; accent-color: var(--theme-primary);" <?php if (($settings['vodafone_cash_enabled'] ?? '1') === '1') echo 'checked'; ?>>
                        </div>
                        <div class="form-row">
                            <label class="form-label">رقم محفظة فودافون كاش</label>
                            <input type="text" name="vodafone_cash_number" class="romantic-input" value="<?php echo htmlspecialchars($settings['vodafone_cash_number'] ?? '01000000000'); ?>">
                        </div>
                        <div class="form-row">
                            <label class="form-label">تعليمات التحويل للعميل</label>
                            <textarea name="vodafone_cash_instructions" rows="2" class="romantic-textarea"><?php echo htmlspecialchars($settings['vodafone_cash_instructions'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <hr style="border: 0; border-top: 1px solid var(--theme-glass-border); margin: 25px 0;">

                    <!-- Card Payments Config -->
                    <div style="margin-bottom: 25px; opacity: 0.85;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong><?php echo __('adm_setting_card'); ?></strong>
                            <input type="checkbox" name="card_enabled" style="width: 20px; height: 20px; accent-color: var(--theme-primary);" <?php if (($settings['card_enabled'] ?? '0') === '1') echo 'checked'; ?>>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--theme-text-muted); margin: 0;">معطل افتراضياً حتى يتم توفير مفاتيح بوابة الدفع الإلكتروني المعتمدة.</p>
                    </div>

                    <button type="submit" name="save_settings" class="btn-romantic" style="width: 100%; padding: 14px; font-size: 1.1rem;">
                        <i class="fas fa-save"></i> <?php echo __('adm_save_settings'); ?>
                    </button>
                </form>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('.adm-tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.adm-tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.adm-tab-pane').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                const paneId = btn.getAttribute('data-tab');
                document.getElementById(paneId).classList.add('active');
            });
        });
    </script>
</body>
</html>
