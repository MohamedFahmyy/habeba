<?php
/**
 * Customer Private Dashboard (Strictly Isolated Workspace)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/I18n.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/GiftService.php';
require_once __DIR__ . '/core/ThemeRegistry.php';
require_once __DIR__ . '/core/PaymentService.php';

Auth::requireAuth('login.php');
$currentUser = Auth::user();
$userId = (int)$currentUser['id'];

$message = '';
$error = '';

// Handle Actions (Duplicate / Delete)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $giftId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $csrf = $_GET['csrf'] ?? '';

    if (!Auth::verifyCsrf($csrf)) {
        $error = __('auth_error_csrf');
    } else {
        if ($action === 'delete') {
            if (GiftService::deleteGift($giftId, $userId)) {
                $message = 'تم حذف الهدية بنجاح.';
            } else {
                $error = 'تعذر حذف الهدية أو ليس لديك صلاحية.';
            }
        } elseif ($action === 'duplicate') {
            $copy = GiftService::duplicateGift($giftId, $userId);
            if ($copy) {
                $message = 'تم تكرار الهدية بنجاح كمسودة جديدة!';
            } else {
                $error = 'تعذر نسخ الهدية.';
            }
        }
    }
}

// Fetch only this customer's gifts
$stats = GiftService::countUserGifts($userId);
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$gifts = GiftService::listUserGifts($userId, $page, 20);
$paymentsRequired = PaymentService::isPaymentRequired();
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::getLang(); ?>" dir="<?php echo I18n::getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('dash_title'); ?> - <?php echo __('site_title'); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Platform Stylesheet -->
    <link rel="stylesheet" href="css/platform.css">

    <style>
        .dash-container {
            max-width: 1250px;
            margin: 110px auto 60px;
            padding: 0 20px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .dash-card {
            background: var(--theme-card-bg);
            border: 1px solid var(--theme-glass-border);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow-soft);
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .gift-workspace-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
        }

        .gift-box-item {
            background: var(--theme-card-bg);
            border: 1px solid var(--theme-glass-border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-soft);
            transition: var(--transition-smooth);
            display: flex;
            flex-direction: column;
        }

        .gift-box-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-elevated);
        }

        .gift-box-header {
            height: 120px;
            position: relative;
            padding: 16px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .badge-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .badge-published {
            background: rgba(76, 175, 80, 0.2);
            color: #2E7D32;
            border: 1px solid rgba(76, 175, 80, 0.4);
        }

        .badge-draft {
            background: rgba(255, 152, 0, 0.2);
            color: #EF6C00;
            border: 1px solid rgba(255, 152, 0, 0.4);
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
            <span style="font-size: 0.9rem; color: var(--theme-text-muted); font-weight: 600;" class="hide-mobile">
                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
            </span>
            <a href="wizard.php" class="btn-romantic btn-romantic-sm">
                <i class="fas fa-magic"></i> <?php echo __('nav_create'); ?>
            </a>
            <a href="logout.php" class="btn-romantic-outline btn-romantic-sm" style="border-color: rgba(255,82,82,0.4); color: #ff5252 !important;">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </nav>

    <div class="dash-container">
        
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

        <!-- Welcome Banner -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 35px; flex-wrap: wrap; gap: 20px;">
            <div>
                <h1 class="font-script" style="font-size: clamp(2.8rem, 5vw, 4rem); color: var(--theme-deep); line-height: 1.1;">
                    <?php echo sprintf(__('dash_welcome'), htmlspecialchars($currentUser['name'])); ?>
                </h1>
                <p style="color: var(--theme-text-muted); font-size: 1.05rem; margin-top: 6px;">
                    <?php echo __('dash_subtitle'); ?>
                </p>
            </div>
            <a href="wizard.php" class="btn-romantic" style="font-size: 1.05rem; padding: 14px 30px;">
                <i class="fas fa-plus-circle"></i> <?php echo __('dash_btn_create'); ?>
            </a>
        </div>

        <!-- Metric Statistics Cards -->
        <div class="stat-grid">
            <div class="dash-card">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(217, 138, 155, 0.15); color: var(--theme-primary); display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                    <i class="fas fa-gift"></i>
                </div>
                <div>
                    <div style="font-size: 1.7rem; font-weight: 700; color: var(--theme-deep);"><?php echo $stats['total']; ?></div>
                    <div style="font-size: 0.85rem; color: var(--theme-text-muted);"><?php echo __('dash_stat_total'); ?></div>
                </div>
            </div>

            <div class="dash-card">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(76, 175, 80, 0.15); color: #2E7D32; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div style="font-size: 1.7rem; font-weight: 700; color: #2E7D32;"><?php echo $stats['published']; ?></div>
                    <div style="font-size: 0.85rem; color: var(--theme-text-muted);"><?php echo __('dash_stat_published'); ?></div>
                </div>
            </div>

            <div class="dash-card">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(255, 152, 0, 0.15); color: #EF6C00; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                    <i class="fas fa-pencil-ruler"></i>
                </div>
                <div>
                    <div style="font-size: 1.7rem; font-weight: 700; color: #EF6C00;"><?php echo $stats['drafts']; ?></div>
                    <div style="font-size: 0.85rem; color: var(--theme-text-muted);"><?php echo __('dash_stat_drafts'); ?></div>
                </div>
            </div>

            <div class="dash-card">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(33, 150, 243, 0.15); color: #1565C0; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                    <i class="fas fa-eye"></i>
                </div>
                <div>
                    <div style="font-size: 1.7rem; font-weight: 700; color: #1565C0;"><?php echo $stats['views']; ?></div>
                    <div style="font-size: 0.85rem; color: var(--theme-text-muted);"><?php echo __('dash_stat_views'); ?></div>
                </div>
            </div>
        </div>

        <!-- Customer Gifts Workspace -->
        <?php if (empty($gifts)): ?>
        <div class="glass-box" style="padding: 60px 30px; text-align: center; max-width: 650px; margin: 40px auto;">
            <i class="fas fa-heart-broken" style="font-size: 3.5rem; color: var(--theme-primary); margin-bottom: 18px;"></i>
            <h3 class="font-script" style="font-size: 2.8rem; color: var(--theme-deep); margin-bottom: 10px;">
                <?php echo __('dash_no_gifts_title'); ?>
            </h3>
            <p style="color: var(--theme-text-muted); font-size: 1rem; margin-bottom: 30px; line-height: 1.6;">
                <?php echo __('dash_no_gifts_desc'); ?>
            </p>
            <a href="wizard.php" class="btn-romantic" style="padding: 14px 34px;">
                <i class="fas fa-magic"></i> <?php echo __('dash_btn_create'); ?>
            </a>
        </div>
        <?php else: ?>
        <div class="gift-workspace-grid">
            <?php foreach ($gifts as $g): 
                $themeMeta = ThemeRegistry::get($g['theme_id']);
                $themeBg = $themeMeta['bg_gradient'] ?? 'linear-gradient(135deg, #FFF8F0, #D98A9B)';
                $isPublished = ($g['status'] === 'published');
                $publicUrl = BASE_URL . '/gift.php?slug=' . urlencode($g['slug']);
                $editorUrl = BASE_URL . '/editor.php?id=' . $g['id'] . '&token=' . $g['edit_token'];
            ?>
            <div class="gift-box-item">
                <div class="gift-box-header" style="background: <?php echo $themeBg; ?>;">
                    <span class="badge-pill" style="background: rgba(0,0,0,0.5); color: #FFF;">
                        <i class="fas fa-palette"></i> <?php echo htmlspecialchars(I18n::isRtl() ? $themeMeta['name_ar'] : $themeMeta['name_en']); ?>
                    </span>
                    <span class="badge-pill <?php echo $isPublished ? 'badge-published' : 'badge-draft'; ?>">
                        <?php echo $isPublished ? __('dash_status_published') : __('dash_status_draft'); ?>
                    </span>
                </div>

                <div style="padding: 22px; display: flex; flex-direction: column; flex: 1;">
                    <h3 style="font-size: 1.25rem; color: var(--theme-deep); margin-bottom: 6px;">
                        <?php echo htmlspecialchars($g['title']); ?>
                    </h3>
                    <div style="font-size: 0.95rem; font-weight: 600; color: var(--theme-primary); margin-bottom: 12px;">
                        <?php echo htmlspecialchars($g['sender_name']); ?> ❤️ <?php echo htmlspecialchars($g['recipient_name']); ?>
                    </div>
                    <p style="color: var(--theme-text-muted); font-size: 0.88rem; margin-bottom: 18px; line-height: 1.5; flex: 1;">
                        "<?php echo htmlspecialchars(mb_substr($g['welcome_message'], 0, 75)); ?>..."
                    </p>

                    <!-- Payment Status Badge if payments enabled -->
                    <?php if ($paymentsRequired): ?>
                    <div style="margin-bottom: 15px; font-size: 0.85rem;">
                        <?php if ($g['payment_status'] === 'paid'): ?>
                        <span style="color: #2E7D32;"><i class="fas fa-check-circle"></i> <?php echo __('dash_payment_paid'); ?></span>
                        <?php elseif ($g['payment_status'] === 'pending_review'): ?>
                        <span style="color: #EF6C00;"><i class="fas fa-clock"></i> <?php echo __('dash_payment_pending'); ?></span>
                        <?php else: ?>
                        <a href="checkout.php?gift_id=<?php echo $g['id']; ?>" class="btn-romantic btn-romantic-sm" style="display: inline-flex; width: 100%; justify-content: center; margin-bottom: 6px;">
                            <i class="fas fa-credit-card"></i> <?php echo __('dash_action_checkout'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; border-top: 1px solid var(--theme-glass-border); padding-top: 15px;">
                        <a href="<?php echo $editorUrl; ?>" class="btn-romantic btn-romantic-sm" style="flex: 1; text-align: center;">
                            <i class="fas fa-pencil-alt"></i> <?php echo __('dash_action_edit'); ?>
                        </a>
                        <a href="<?php echo $publicUrl; ?>" target="_blank" class="btn-romantic-outline btn-romantic-sm" title="<?php echo __('dash_action_preview'); ?>">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button type="button" class="btn-romantic-outline btn-romantic-sm btn-copy-link" data-url="<?php echo $publicUrl; ?>" title="<?php echo __('dash_action_copy'); ?>">
                            <i class="fas fa-copy"></i>
                        </button>
                        <a href="dashboard.php?action=duplicate&id=<?php echo $g['id']; ?>&csrf=<?php echo Auth::csrfToken(); ?>" class="btn-romantic-outline btn-romantic-sm" title="<?php echo __('dash_action_duplicate'); ?>">
                            <i class="fas fa-clone"></i>
                        </a>
                        <a href="dashboard.php?action=delete&id=<?php echo $g['id']; ?>&csrf=<?php echo Auth::csrfToken(); ?>" onclick="return confirm('<?php echo __('dash_confirm_delete'); ?>');" class="btn-romantic-outline btn-romantic-sm" style="color: #ff5252 !important; border-color: #ff5252;" title="<?php echo __('dash_action_delete'); ?>">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Toast Notification -->
    <div class="romantic-toast" id="toast-msg">
        <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
        <span id="toast-text"><?php echo __('dash_copy_success'); ?></span>
    </div>

    <script>
        document.querySelectorAll('.btn-copy-link').forEach(btn => {
            btn.addEventListener('click', () => {
                const url = btn.getAttribute('data-url');
                navigator.clipboard.writeText(url).then(() => {
                    const toast = document.getElementById('toast-msg');
                    toast.classList.add('show');
                    setTimeout(() => toast.classList.remove('show'), 3000);
                });
            });
        });
    </script>
</body>
</html>
