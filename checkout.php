<?php
/**
 * Payment & Checkout Portal
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/I18n.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/GiftService.php';
require_once __DIR__ . '/core/PaymentService.php';

Auth::requireAuth('login.php');
$userId = Auth::userId();

$giftId = isset($_GET['gift_id']) ? (int)$_GET['gift_id'] : 0;
$gift = GiftService::getGiftById($giftId, $userId);

if (!$gift) {
    header('Location: dashboard.php');
    exit;
}

$settings = PaymentService::getAllSettings();
$price = PaymentService::getPrice();
$currency = PaymentService::getCurrency();
$paymentsEnabled = PaymentService::isPaymentRequired();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = __('auth_error_csrf');
    } else {
        $provider = trim($_POST['provider'] ?? 'instapay');
        $reference = trim($_POST['reference'] ?? '');

        if (empty($reference)) {
            $error = 'يرجى كتابة رقم العملية أو رقم المحفظة المحول منها.';
        } else {
            // Handle optional receipt upload
            $receiptPath = null;
            if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['receipt'];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $safeName = 'receipt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                $target = UPLOAD_DIR . '/' . $safeName;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $receiptPath = 'uploads/' . $safeName;
                }
            }

            $res = PaymentService::submitManualPayment($giftId, $userId, $provider, $reference, $receiptPath);
            if ($res['success']) {
                $message = __('pay_status_review_desc');
                // Refresh gift
                $gift = GiftService::getGiftById($giftId, $userId);
            } else {
                $error = 'حدث خطأ أثناء تسجيل المعاملة.';
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
    <title><?php echo __('pay_checkout_title'); ?> - <?php echo __('site_title'); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Platform Stylesheet -->
    <link rel="stylesheet" href="css/platform.css">

    <style>
        .checkout-container {
            max-width: 750px;
            margin: 110px auto 60px;
            padding: 0 20px;
        }

        .payment-method-card {
            border: 2px solid var(--theme-glass-border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            cursor: pointer;
            transition: var(--transition-smooth);
            background: rgba(255, 255, 255, 0.7);
        }

        .payment-method-card.selected, .payment-method-card:hover {
            border-color: var(--theme-primary);
            background: #FFF;
            box-shadow: 0 0 15px var(--theme-accent-glow);
        }

        .instruction-box {
            background: #FFF8E7;
            border: 1px solid #FFE082;
            border-radius: 12px;
            padding: 16px;
            margin: 15px 0;
            font-size: 0.95rem;
            color: #5D4037;
            line-height: 1.6;
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
            <a href="dashboard.php" class="btn-romantic-outline btn-romantic-sm">
                <i class="fas fa-arrow-left"></i> <?php echo __('nav_dashboard'); ?>
            </a>
        </div>
    </nav>

    <div class="checkout-container">
        
        <?php if (!$paymentsEnabled): ?>
        <div class="glass-box" style="padding: 40px; text-align: center; margin-bottom: 30px;">
            <i class="fas fa-check-circle" style="font-size: 3.5rem; color: #4CAF50; margin-bottom: 15px;"></i>
            <h2 class="font-script" style="font-size: 3rem; color: var(--theme-deep); margin-bottom: 10px;">
                <?php echo __('dash_payment_free'); ?>
            </h2>
            <p style="color: var(--theme-text-muted); font-size: 1.1rem; margin-bottom: 25px;">
                <?php echo __('pay_free_mode_notice'); ?>
            </p>
            <a href="gift.php?slug=<?php echo urlencode($gift['slug']); ?>" class="btn-romantic">
                <i class="fas fa-eye"></i> معاينة موقع الهدية مباشرة
            </a>
        </div>
        <?php else: ?>

        <div class="glass-box" style="padding: 40px;">
            
            <h1 class="font-script" style="font-size: 3.2rem; color: var(--theme-deep); line-height: 1.1; margin-bottom: 10px;">
                <?php echo __('pay_checkout_title'); ?>
            </h1>
            <p style="color: var(--theme-text-muted); font-size: 1.05rem; margin-bottom: 30px;">
                <?php echo __('pay_checkout_subtitle'); ?>
            </p>

            <?php if (!empty($message)): ?>
            <div style="background: rgba(76, 175, 80, 0.15); border: 1px solid #4caf50; color: #2e7d32; padding: 18px; border-radius: 12px; margin-bottom: 25px;">
                <h4 style="font-size: 1.15rem; margin-bottom: 5px;"><i class="fas fa-clock"></i> <?php echo __('pay_status_review_title'); ?></h4>
                <p style="font-size: 0.95rem; margin: 0;"><?php echo htmlspecialchars($message); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div style="background: rgba(255, 82, 82, 0.15); border: 1px solid #ff5252; color: #d32f2f; padding: 14px 20px; border-radius: 12px; margin-bottom: 25px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- Order Summary -->
            <div style="background: rgba(255,255,255,0.6); border: 1px solid var(--theme-glass-border); padding: 20px; border-radius: 16px; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <strong><?php echo sprintf(__('pay_gift_name'), htmlspecialchars($gift['title'])); ?></strong>
                    <span style="font-size: 0.9rem; color: var(--theme-text-muted);"><?php echo htmlspecialchars($gift['sender_name']); ?> ❤️ <?php echo htmlspecialchars($gift['recipient_name']); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 1.25rem; font-weight: 700; color: var(--theme-deep); border-top: 1px solid var(--theme-glass-border); padding-top: 10px; margin-top: 10px;">
                    <span><?php echo __('pay_amount_due'); ?></span>
                    <span style="color: var(--theme-primary);"><?php echo $price; ?> <?php echo $currency; ?></span>
                </div>
            </div>

            <!-- Payment Submission Form -->
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo Auth::csrfToken(); ?>">
                <input type="hidden" name="provider" id="provider-input" value="instapay">

                <label class="form-label" style="font-size: 1.1rem; margin-bottom: 15px; display: block;">
                    اختر وسيلة الدفع المناسبة:
                </label>

                <!-- InstaPay Card -->
                <?php if (($settings['instapay_enabled'] ?? '1') === '1'): ?>
                <div class="payment-method-card selected" data-provider="instapay">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <strong><i class="fas fa-mobile-alt" style="color: #673AB7;"></i> <?php echo __('pay_method_instapay'); ?></strong>
                        <span style="font-size: 0.85rem; background: #EDE7F6; color: #512DA8; padding: 2px 10px; border-radius: 10px; font-weight: 600;">تحويل لحظي</span>
                    </div>
                    <div class="instruction-box">
                        <strong>عنوان انستاباي:</strong> <code style="font-size: 1.05rem; background: #FFF; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($settings['instapay_address'] ?? 'habeba@instapay'); ?></code>
                        <div style="margin-top: 6px;"><?php echo htmlspecialchars($settings['instapay_instructions'] ?? ''); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Vodafone Cash Card -->
                <?php if (($settings['vodafone_cash_enabled'] ?? '1') === '1'): ?>
                <div class="payment-method-card" data-provider="vodafone_cash">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <strong><i class="fas fa-wallet" style="color: #E53935;"></i> <?php echo __('pay_method_vodafone'); ?></strong>
                        <span style="font-size: 0.85rem; background: #FFEBEE; color: #C62828; padding: 2px 10px; border-radius: 10px; font-weight: 600;">محفظة كاش</span>
                    </div>
                    <div class="instruction-box">
                        <strong>رقم محفظة فودافون كاش:</strong> <code style="font-size: 1.05rem; background: #FFF; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($settings['vodafone_cash_number'] ?? '01000000000'); ?></code>
                        <div style="margin-top: 6px;"><?php echo htmlspecialchars($settings['vodafone_cash_instructions'] ?? ''); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Card Payment (Disabled placeholder if gateway not configured) -->
                <?php if (($settings['card_enabled'] ?? '0') === '1'): ?>
                <div class="payment-method-card" data-provider="card">
                    <strong><i class="fas fa-credit-card" style="color: #1565C0;"></i> <?php echo __('pay_method_card'); ?></strong>
                    <div class="instruction-box">الدفع الآمن بالبطاقات الائتمانية.</div>
                </div>
                <?php endif; ?>

                <!-- Transaction Reference Input -->
                <div class="form-row" style="margin-top: 25px;">
                    <label class="form-label"><?php echo __('pay_ref_label'); ?></label>
                    <input type="text" name="reference" class="romantic-input" required placeholder="مثال: رقم العملية من تطبيق انستاباي أو رقم محفظتك">
                </div>

                <!-- Receipt Screenshot -->
                <div class="form-row">
                    <label class="form-label"><?php echo __('pay_receipt_label'); ?></label>
                    <input type="file" name="receipt" accept="image/*" class="romantic-input" style="background:#FFF;">
                </div>

                <button type="submit" class="btn-romantic" style="width: 100%; padding: 16px; font-size: 1.15rem; margin-top: 15px;">
                    <i class="fas fa-paper-plane"></i> <?php echo __('pay_btn_submit'); ?>
                </button>
            </form>

        </div>
        <?php endif; ?>

    </div>

    <script>
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.payment-method-card').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                document.getElementById('provider-input').value = card.getAttribute('data-provider');
            });
        });
    </script>
</body>
</html>
