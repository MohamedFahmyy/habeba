<?php
/**
 * Habeba - Romantic Memories Platform
 * High-Conversion Bilingual Advertising Landing Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/I18n.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/GiftService.php';
require_once __DIR__ . '/core/ThemeRegistry.php';

$isLoggedIn = Auth::check();
$ctaDestination = $isLoggedIn ? 'dashboard.php' : 'register.php?return_to=' . urlencode('wizard.php');
$allThemes = ThemeRegistry::getAll();
$demoGifts = GiftService::listShowcaseGifts();
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::getLang(); ?>" dir="<?php echo I18n::getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('site_title'); ?> - <?php echo __('site_tagline'); ?></title>

    <meta name="description" content="<?php echo __('hero_subtitle'); ?>">
    <meta property="og:title" content="<?php echo __('site_title'); ?>">
    <meta property="og:description" content="<?php echo __('hero_subtitle'); ?>">
    <meta property="og:image" content="<?php echo BASE_URL; ?>/assets/images/2.jpg">
    <meta property="og:type" content="website">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    <!-- Platform Stylesheet -->
    <link rel="stylesheet" href="css/platform.css">

    <style>
        .home-hero {
            min-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 130px 20px 80px;
            position: relative;
            background: radial-gradient(circle at 50% 25%, rgba(217, 138, 155, 0.22) 0%, transparent 70%);
        }

        .home-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 22px;
            background: var(--theme-card-bg);
            border: 1px solid var(--theme-glass-border);
            border-radius: 30px;
            font-size: 0.95rem;
            color: var(--theme-primary);
            font-weight: 700;
            margin-bottom: 25px;
            box-shadow: var(--shadow-soft);
        }

        .home-title {
            font-size: clamp(2.4rem, 5.5vw, 4.4rem);
            color: var(--theme-deep);
            line-height: 1.25;
            margin-bottom: 22px;
            max-width: 950px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 800;
        }

        .home-subtitle {
            font-size: clamp(1.1rem, 2.2vw, 1.45rem);
            color: var(--theme-text-muted);
            max-width: 760px;
            margin: 0 auto 38px;
            line-height: 1.75;
        }

        .demo-frame-wrapper {
            background: #FFF;
            border-radius: 28px;
            border: 1px solid var(--theme-glass-border);
            box-shadow: var(--shadow-elevated);
            overflow: hidden;
            max-width: 950px;
            margin: 0 auto;
            position: relative;
        }

        .step-box-card {
            background: var(--theme-card-bg);
            border: 1px solid var(--theme-glass-border);
            border-radius: 20px;
            padding: 35px 25px;
            text-align: center;
            box-shadow: var(--shadow-soft);
            transition: var(--transition-smooth);
            position: relative;
        }

        .step-box-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-elevated);
        }

        .step-num-badge {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--theme-primary);
            color: #FFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0 auto 18px;
            box-shadow: 0 0 15px var(--theme-accent-glow);
        }

        .theme-item-card {
            background: var(--theme-card-bg);
            border: 1px solid var(--theme-glass-border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: var(--shadow-soft);
            transition: var(--transition-smooth);
            display: flex;
            flex-direction: column;
        }

        .theme-item-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-elevated);
        }

        .theme-swatch {
            height: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFF;
            position: relative;
        }

        .faq-item {
            background: var(--theme-card-bg);
            border: 1px solid var(--theme-glass-border);
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-soft);
            text-align: <?php echo I18n::isRtl() ? 'right' : 'left'; ?>;
        }

        .faq-q {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--theme-deep);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .faq-a {
            color: var(--theme-text-muted);
            line-height: 1.65;
            font-size: 0.98rem;
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

        <div style="display: flex; gap: 14px; align-items: center;">
            <!-- Language Switcher -->
            <a href="<?php echo I18n::getSwitcherUrl(); ?>" class="btn-romantic-outline btn-romantic-sm">
                <i class="fas fa-globe"></i> <?php echo __('other_lang_name'); ?>
            </a>

            <?php if ($isLoggedIn): ?>
            <a href="dashboard.php" class="btn-romantic-outline btn-romantic-sm">
                <i class="fas fa-columns"></i> <?php echo __('nav_dashboard'); ?>
            </a>
            <a href="wizard.php" class="btn-romantic btn-romantic-sm">
                <i class="fas fa-magic"></i> <?php echo __('nav_create'); ?>
            </a>
            <?php else: ?>
            <a href="login.php" class="btn-romantic-outline btn-romantic-sm hide-mobile">
                <i class="fas fa-sign-in-alt"></i> <?php echo __('nav_login'); ?>
            </a>
            <a href="<?php echo $ctaDestination; ?>" class="btn-romantic btn-romantic-sm">
                <i class="fas fa-heart"></i> <?php echo __('hero_cta_primary'); ?>
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- SECTION 1: HERO -->
    <section class="home-hero">
        <div style="z-index: 1;">
            <div class="home-badge">
                <i class="fas fa-sparkles"></i> <?php echo __('hero_badge'); ?>
            </div>

            <h1 class="home-title">
                <?php echo __('hero_title'); ?>
            </h1>

            <p class="home-subtitle">
                <?php echo __('hero_subtitle'); ?>
            </p>

            <div style="display: flex; gap: 18px; justify-content: center; flex-wrap: wrap; margin-bottom: 25px;">
                <a href="<?php echo $ctaDestination; ?>" class="btn-romantic" style="font-size: 1.25rem; padding: 18px 45px;">
                    <i class="fas fa-heart"></i> <?php echo __('hero_cta_primary'); ?>
                </a>
                <a href="#demo-section" class="btn-romantic-outline" style="font-size: 1.15rem; padding: 16px 34px;">
                    <i class="fas fa-play-circle"></i> <?php echo __('hero_cta_secondary'); ?>
                </a>
            </div>

            <div style="font-size: 0.92rem; color: var(--theme-text-muted); display: flex; align-items: center; justify-content: center; gap: 8px;">
                <?php echo __('hero_reassurance'); ?>
            </div>
        </div>
    </section>

    <!-- SECTION 2: INTERACTIVE VISUAL DEMO -->
    <section id="demo-section" style="padding: 70px 5%; background: rgba(255, 255, 255, 0.4); border-top: 1px solid var(--theme-glass-border); border-bottom: 1px solid var(--theme-glass-border);">
        <div style="max-width: 1100px; margin: 0 auto; text-align: center;">
            <div class="home-badge" style="background: rgba(217, 138, 155, 0.15);">
                <i class="fas fa-eye"></i> <?php echo __('demo_badge'); ?>
            </div>
            <h2 class="font-script" style="font-size: clamp(2.8rem, 6vw, 4.2rem); color: var(--theme-deep); margin-bottom: 12px;">
                <?php echo __('demo_title'); ?>
            </h2>
            <p style="color: var(--theme-text-muted); font-size: 1.15rem; max-width: 700px; margin: 0 auto 40px; line-height: 1.7;">
                <?php echo __('demo_subtitle'); ?>
            </p>

            <div class="demo-frame-wrapper">
                <div style="background: #1A162B; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; color: #FFF;">
                    <div style="display: flex; gap: 8px;">
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #FF5F56;"></span>
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #FFBD2E;"></span>
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #27C93F;"></span>
                    </div>
                    <div style="background: rgba(255,255,255,0.1); padding: 4px 18px; border-radius: 20px; font-size: 0.82rem; color: #BBB;">
                        <i class="fas fa-lock" style="font-size: 0.75rem;"></i> <?php echo BASE_URL; ?>/gift/salma-and-mohamed
                    </div>
                    <span style="font-size: 0.85rem; color: var(--theme-primary); font-weight: 600;">
                        <?php echo __('demo_tag_flagship'); ?>
                    </span>
                </div>
                
                <div style="height: 480px; position: relative;">
                    <iframe src="gift.php?slug=salma-and-mohamed" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>

            <div style="margin-top: 35px;">
                <a href="gift.php?slug=salma-and-mohamed" target="_blank" class="btn-romantic">
                    <i class="fas fa-external-link-alt"></i> <?php echo __('demo_btn_view'); ?>
                </a>
            </div>
        </div>
    </section>

    <!-- SECTION 3: HOW IT WORKS (3 SIMPLE STEPS) -->
    <section id="how-it-works" style="padding: 90px 5%; max-width: 1250px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 60px;">
            <h2 class="font-script" style="font-size: clamp(2.8rem, 6vw, 4.2rem); color: var(--theme-deep); margin-bottom: 12px;">
                <?php echo __('how_title'); ?>
            </h2>
            <p style="color: var(--theme-text-muted); font-size: 1.15rem; max-width: 650px; margin: 0 auto;">
                <?php echo __('how_subtitle'); ?>
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <div class="step-box-card">
                <div class="step-num-badge">1</div>
                <h3 style="font-size: 1.35rem; color: var(--theme-deep); margin-bottom: 12px; font-weight: 700;">
                    <?php echo __('step1_title'); ?>
                </h3>
                <p style="color: var(--theme-text-muted); font-size: 0.95rem; line-height: 1.7;">
                    <?php echo __('step1_desc'); ?>
                </p>
            </div>

            <div class="step-box-card">
                <div class="step-num-badge">2</div>
                <h3 style="font-size: 1.35rem; color: var(--theme-deep); margin-bottom: 12px; font-weight: 700;">
                    <?php echo __('step2_title'); ?>
                </h3>
                <p style="color: var(--theme-text-muted); font-size: 0.95rem; line-height: 1.7;">
                    <?php echo __('step2_desc'); ?>
                </p>
            </div>

            <div class="step-box-card">
                <div class="step-num-badge">3</div>
                <h3 style="font-size: 1.35rem; color: var(--theme-deep); margin-bottom: 12px; font-weight: 700;">
                    <?php echo __('step3_title'); ?>
                </h3>
                <p style="color: var(--theme-text-muted); font-size: 0.95rem; line-height: 1.7;">
                    <?php echo __('step3_desc'); ?>
                </p>
            </div>
        </div>
    </section>

    <!-- SECTION 4: THEMES SHOWCASE (9 GENUINE TEMPLATES) -->
    <section id="themes" style="padding: 90px 5%; background: rgba(255, 255, 255, 0.4); border-top: 1px solid var(--theme-glass-border); border-bottom: 1px solid var(--theme-glass-border);">
        <div style="max-width: 1250px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 60px;">
                <h2 class="font-script" style="font-size: clamp(2.8rem, 6vw, 4.2rem); color: var(--theme-deep); margin-bottom: 12px;">
                    <?php echo __('themes_section_title'); ?>
                </h2>
                <p style="color: var(--theme-text-muted); font-size: 1.15rem; max-width: 650px; margin: 0 auto;">
                    <?php echo __('themes_section_subtitle'); ?>
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px;">
                <?php foreach ($allThemes as $tKey => $t): ?>
                <div class="theme-item-card">
                    <div class="theme-swatch" style="background: <?php echo $t['bg_gradient']; ?>;">
                        <i class="fas fa-heart" style="font-size: 2.2rem; filter: drop-shadow(0 0 10px rgba(0,0,0,0.4));"></i>
                        <span style="position: absolute; bottom: 10px; right: 15px; font-size: 0.75rem; background: rgba(0,0,0,0.5); padding: 2px 10px; border-radius: 12px;">
                            <?php echo __('theme_preview_badge'); ?>
                        </span>
                    </div>
                    <div style="padding: 22px; flex: 1; display: flex; flex-direction: column;">
                        <h4 style="font-size: 1.25rem; color: var(--theme-deep); margin-bottom: 8px;">
                            <?php echo htmlspecialchars(I18n::isRtl() ? $t['name_ar'] : $t['name_en']); ?>
                        </h4>
                        <p style="color: var(--theme-text-muted); font-size: 0.9rem; line-height: 1.6; flex: 1;">
                            <?php echo htmlspecialchars(I18n::isRtl() ? $t['desc_ar'] : $t['desc_en']); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECTION 5: KEY FEATURES & PRIVACY REASSURANCE -->
    <section id="features" style="padding: 90px 5%; max-width: 1250px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 60px;">
            <h2 class="font-script" style="font-size: clamp(2.8rem, 6vw, 4.2rem); color: var(--theme-deep); margin-bottom: 12px;">
                <?php echo __('features_title'); ?>
            </h2>
            <p style="color: var(--theme-text-muted); font-size: 1.15rem; max-width: 650px; margin: 0 auto;">
                <?php echo __('features_subtitle'); ?>
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
            <div class="step-box-card">
                <i class="fas fa-music" style="font-size: 2.4rem; color: var(--theme-primary); margin-bottom: 15px;"></i>
                <h3 style="font-size: 1.3rem; color: var(--theme-deep); margin-bottom: 10px;"><?php echo __('feat_music_title'); ?></h3>
                <p style="color: var(--theme-text-muted); font-size: 0.95rem; line-height: 1.65;"><?php echo __('feat_music_desc'); ?></p>
            </div>

            <div class="step-box-card">
                <i class="fas fa-images" style="font-size: 2.4rem; color: var(--theme-primary); margin-bottom: 15px;"></i>
                <h3 style="font-size: 1.3rem; color: var(--theme-deep); margin-bottom: 10px;"><?php echo __('feat_memories_title'); ?></h3>
                <p style="color: var(--theme-text-muted); font-size: 0.95rem; line-height: 1.65;"><?php echo __('feat_memories_desc'); ?></p>
            </div>

            <div class="step-box-card">
                <i class="fas fa-envelope-open-text" style="font-size: 2.4rem; color: var(--theme-primary); margin-bottom: 15px;"></i>
                <h3 style="font-size: 1.3rem; color: var(--theme-deep); margin-bottom: 10px;"><?php echo __('feat_letter_title'); ?></h3>
                <p style="color: var(--theme-text-muted); font-size: 0.95rem; line-height: 1.65;"><?php echo __('feat_letter_desc'); ?></p>
            </div>

            <div class="step-box-card">
                <i class="fas fa-heart-pulse" style="font-size: 2.4rem; color: var(--theme-primary); margin-bottom: 15px;"></i>
                <h3 style="font-size: 1.3rem; color: var(--theme-deep); margin-bottom: 10px;"><?php echo __('feat_timeline_title'); ?></h3>
                <p style="color: var(--theme-text-muted); font-size: 0.95rem; line-height: 1.65;"><?php echo __('feat_timeline_desc'); ?></p>
            </div>

            <div class="step-box-card">
                <i class="fas fa-user-shield" style="font-size: 2.4rem; color: var(--theme-primary); margin-bottom: 15px;"></i>
                <h3 style="font-size: 1.3rem; color: var(--theme-deep); margin-bottom: 10px;"><?php echo __('feat_privacy_title'); ?></h3>
                <p style="color: var(--theme-text-muted); font-size: 0.95rem; line-height: 1.65;"><?php echo __('feat_privacy_desc'); ?></p>
            </div>

            <div class="step-box-card">
                <i class="fas fa-mobile-screen-button" style="font-size: 2.4rem; color: var(--theme-primary); margin-bottom: 15px;"></i>
                <h3 style="font-size: 1.3rem; color: var(--theme-deep); margin-bottom: 10px;"><?php echo __('feat_mobile_title'); ?></h3>
                <p style="color: var(--theme-text-muted); font-size: 0.95rem; line-height: 1.65;"><?php echo __('feat_mobile_desc'); ?></p>
            </div>
        </div>
    </section>

    <!-- SECTION 6: FAQ ACCORDION -->
    <section id="faq" style="padding: 80px 5%; background: rgba(255, 255, 255, 0.4); border-top: 1px solid var(--theme-glass-border); border-bottom: 1px solid var(--theme-glass-border);">
        <div style="max-width: 850px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 50px;">
                <h2 class="font-script" style="font-size: clamp(2.8rem, 6vw, 4.2rem); color: var(--theme-deep); margin-bottom: 10px;">
                    <?php echo __('faq_title'); ?>
                </h2>
                <p style="color: var(--theme-text-muted); font-size: 1.15rem;">
                    <?php echo __('faq_subtitle'); ?>
                </p>
            </div>

            <div class="faq-item">
                <div class="faq-q"><i class="fas fa-question-circle" style="color: var(--theme-primary);"></i> <?php echo __('faq_q1'); ?></div>
                <div class="faq-a"><?php echo __('faq_a1'); ?></div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><i class="fas fa-question-circle" style="color: var(--theme-primary);"></i> <?php echo __('faq_q2'); ?></div>
                <div class="faq-a"><?php echo __('faq_a2'); ?></div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><i class="fas fa-question-circle" style="color: var(--theme-primary);"></i> <?php echo __('faq_q3'); ?></div>
                <div class="faq-a"><?php echo __('faq_a3'); ?></div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><i class="fas fa-question-circle" style="color: var(--theme-primary);"></i> <?php echo __('faq_q4'); ?></div>
                <div class="faq-a"><?php echo __('faq_a4'); ?></div>
            </div>
        </div>
    </section>

    <!-- SECTION 7: FINAL CALL TO ACTION -->
    <section style="padding: 100px 20px; text-align: center;">
        <div class="glass-box" style="max-width: 850px; margin: 0 auto; padding: 60px 30px;">
            <i class="fas fa-heart" style="font-size: 4rem; color: var(--theme-primary); margin-bottom: 20px; animation: pulseHeart 1.6s infinite ease-in-out;"></i>
            <h2 class="font-script" style="font-size: clamp(2.6rem, 5.5vw, 4rem); color: var(--theme-deep); margin-bottom: 14px;">
                <?php echo __('cta_bottom_title'); ?>
            </h2>
            <p style="color: var(--theme-text-muted); font-size: 1.2rem; max-width: 650px; margin: 0 auto 35px; line-height: 1.7;">
                <?php echo __('cta_bottom_desc'); ?>
            </p>
            <a href="<?php echo $ctaDestination; ?>" class="btn-romantic" style="font-size: 1.25rem; padding: 18px 45px;">
                <i class="fas fa-magic"></i> <?php echo __('cta_bottom_btn'); ?>
            </a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer style="text-align: center; padding: 40px 20px; border-top: 1px solid var(--theme-glass-border); font-size: 0.95rem; color: var(--theme-text-muted);">
        <p><?php echo __('footer_text'); ?></p>
        <div style="margin-top: 15px; display: flex; justify-content: center; gap: 20px;">
            <a href="<?php echo I18n::getSwitcherUrl(); ?>" style="color: var(--theme-primary); text-decoration: none; font-weight: 600;">
                <i class="fas fa-globe"></i> <?php echo __('other_lang_name'); ?>
            </a>
            <a href="admin.php" style="color: var(--theme-text-muted); text-decoration: none;">
                <i class="fas fa-cog"></i> <?php echo __('nav_admin'); ?>
            </a>
        </div>
    </footer>

</body>
</html>
