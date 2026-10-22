<?php
/**
 * Public Romantic Gift Viewer
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/I18n.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/GiftService.php';

$slug = trim($_GET['slug'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = trim($_GET['token'] ?? '');
$userId = Auth::userId();

$gift = null;
if (!empty($slug)) {
    $gift = GiftService::getGiftBySlug($slug, $token, $userId);
} elseif ($id > 0) {
    $gift = GiftService::getGiftById($id, $userId, $token);
}

// Fallback to flagship demo ONLY if visiting gift.php without parameters
if (!$gift && empty($slug) && $id === 0) {
    $gift = GiftService::getGiftBySlug('salma-and-mohamed');
}

if (!$gift) {
    // Elegant Not Found / Private Screen
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gift Not Found - Romantic Memories Platform</title>
        <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@400;600&family=Cairo:wght@400;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="css/platform.css">
        <style>
            body { display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center; padding: 20px; }
            .not-found-card { max-width: 550px; padding: 50px 30px; }
        </style>
    </head>
    <body data-theme="luxury-cream">
        <div class="glass-box not-found-card">
            <i class="fas fa-heart-broken" style="font-size: 4rem; color: var(--theme-primary); margin-bottom: 20px;"></i>
            <h1 class="font-script" style="font-size: 3.5rem; margin-bottom: 15px;">Gift Page Not Found</h1>
            <p style="color: var(--theme-text-muted); margin-bottom: 30px;">
                This romantic memory website might still be in draft mode or the link may be incorrect.
            </p>
            <a href="index.php" class="btn-romantic"><i class="fas fa-home"></i> Back to Home</a>
            <a href="wizard.php" class="btn-romantic-outline" style="margin-left: 10px;"><i class="fas fa-magic"></i> Create Your Own Gift</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$themeId = $gift['theme_id'] ?? 'luxury-cream';
$galleryStyle = $gift['gallery_style'] ?? 'polaroid';
$previewImage = !empty($gift['memories']) ? $gift['memories'][0]['image_url'] : 'assets/images/1.jpg';
if (!preg_match('/^https?:\/\//', $previewImage)) {
    $previewImage = BASE_URL . '/' . ltrim($previewImage, '/');
}
$musicVideoId = !empty($gift['music_video_id']) ? $gift['music_video_id'] : GiftService::extractYouTubeId($gift['music_url'] ?? null);
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::getLang(); ?>" dir="<?php echo I18n::getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($gift['title']); ?></title>
    
    <!-- OpenGraph / Social Metadata -->
    <meta property="og:title" content="<?php echo htmlspecialchars($gift['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(mb_substr($gift['welcome_message'], 0, 160)); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($previewImage); ?>">
    <meta property="og:url" content="<?php echo BASE_URL . '/gift.php?slug=' . urlencode($gift['slug']); ?>">
    <meta property="og:type" content="website">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- GSAP Animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <!-- Platform Styles -->
    <link rel="stylesheet" href="css/platform.css">
</head>
<body data-theme="<?php echo htmlspecialchars($themeId); ?>" class="<?php echo I18n::getDir(); ?>">

    <!-- Autoplay blocked prompt banner (appears if browser policy requires a user click) -->
    <div id="autoplay-prompt" class="autoplay-alert" style="display: none;">
        <i class="fas fa-music" style="color: var(--theme-primary); font-size: 1.3rem;"></i>
        <span>Play our romantic background music?</span>
        <button id="btn-start-audio" class="btn-romantic btn-romantic-sm">
            <i class="fas fa-play"></i> Play Song
        </button>
        <button id="btn-dismiss-audio" style="background:none;border:none;color:var(--theme-text-muted);cursor:pointer;padding:4px;">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Floating Elegant Music Bar -->
    <?php if (!empty($musicVideoId)): ?>
    <div class="floating-music-bar" id="music-bar">
        <div class="music-disc" id="music-disc">
            <i class="fas fa-compact-disc"></i>
        </div>
        <div class="music-info">
            <span class="music-title-text"><?php echo htmlspecialchars($gift['music_title'] ?: 'Our Song'); ?></span>
            <span class="music-status-text" id="music-status">Ready to Play</span>
        </div>
        <button class="music-btn" id="btn-toggle-play" title="Play / Pause">
            <i class="fas fa-play" id="play-icon"></i>
        </button>
        <button class="music-btn" id="btn-toggle-mute" title="Mute / Unmute">
            <i class="fas fa-volume-up" id="mute-icon"></i>
        </button>
    </div>
    <div id="yt-player-container" style="position:fixed;top:-9999px;left:-9999px;width:240px;height:240px;opacity:0;pointer-events:none;z-index:-999;"></div>
    <?php endif; ?>

    <!-- Particles Container -->
    <div id="ambient-particles" style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0;overflow:hidden;"></div>

    <!-- Top Action Nav -->
    <div style="position:fixed;top:20px;left:20px;z-index:1000;display:flex;gap:10px;">
        <a href="index.php" class="btn-romantic-outline btn-romantic-sm" style="background:var(--theme-card-bg);">
            <i class="fas fa-arrow-left"></i> Home
        </a>
        <button id="btn-open-share" class="btn-romantic btn-romantic-sm">
            <i class="fas fa-share-alt"></i> Share
        </button>
        <?php if (!empty($_SESSION['editable_gifts'][$gift['id']]) || !empty($_SESSION['admin_logged_in'])): ?>
        <a href="editor.php?id=<?php echo $gift['id']; ?>&token=<?php echo htmlspecialchars($gift['edit_token']); ?>" class="btn-romantic btn-romantic-sm" style="background:var(--theme-accent);">
            <i class="fas fa-edit"></i> Edit Gift
        </a>
        <?php endif; ?>
    </div>

    <!-- Hero Section -->
    <header class="hero-gift-section" id="hero">
        <div style="max-width: 950px; z-index: 1;">
            <div style="font-size: 1.1rem; letter-spacing: 3px; text-transform: uppercase; color: var(--theme-primary); margin-bottom: 15px; font-weight: 600;">
                A Special Digital Gift For You
            </div>
            
            <h1 class="hero-names">
                <?php echo htmlspecialchars($gift['sender_name']); ?> ❤️ <?php echo htmlspecialchars($gift['recipient_name']); ?>
            </h1>
            
            <p class="hero-quote">
                "<?php echo nl2br(htmlspecialchars($gift['welcome_message'])); ?>"
            </p>

            <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
                <a href="#memories-section" class="btn-romantic">
                    <i class="fas fa-heart"></i> Open Your Gift
                </a>
                <?php if (!empty($musicVideoId)): ?>
                <button id="hero-play-song" class="btn-romantic-outline">
                    <i class="fas fa-music"></i> Play Our Song
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div style="position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); font-size: 1.4rem; color: var(--theme-primary); animation: bounce 2s infinite;">
            <a href="#memories-section" style="color: inherit; text-decoration: none;"><i class="fas fa-chevron-down"></i></a>
        </div>
    </header>

    <!-- Memories Gallery Section -->
    <?php if (!empty($gift['show_gallery']) && !empty($gift['memories'])): ?>
    <section id="memories-section" style="padding: 90px 5%; position: relative; z-index: 1;">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 class="font-script" style="font-size: clamp(2.8rem, 6vw, 4.5rem); color: var(--theme-deep);">
                Treasured Moments
            </h2>
            <p style="color: var(--theme-text-muted); font-size: 1.2rem; font-style: italic;">
                "Every picture holds a feeling that never fades."
            </p>
        </div>

        <?php if ($galleryStyle === 'polaroid'): ?>
        <!-- Polaroid Style -->
        <div class="gallery-polaroid-grid">
            <?php foreach ($gift['memories'] as $m): ?>
            <div class="polaroid-card js-lightbox-trigger" data-img="<?php echo htmlspecialchars($m['image_url']); ?>" data-caption="<?php echo htmlspecialchars($m['caption'] ?? $m['title']); ?>">
                <div class="polaroid-pin"></div>
                <div class="polaroid-img-wrapper">
                    <img src="<?php echo htmlspecialchars($m['image_url']); ?>" alt="<?php echo htmlspecialchars($m['title'] ?: 'Romantic Memory'); ?>" loading="lazy">
                </div>
                <div class="polaroid-caption"><?php echo htmlspecialchars($m['title'] ?: 'Our Memory'); ?></div>
                <?php if (!empty($m['caption'])): ?>
                <p style="font-size: 0.95rem; color: #555; text-align: center; margin-top: 5px;">
                    <?php echo htmlspecialchars($m['caption']); ?>
                </p>
                <?php endif; ?>
                <?php if (!empty($m['memory_date'])): ?>
                <div class="polaroid-date"><i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars($m['memory_date']); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- Masonry / Editorial Style -->
        <div class="gallery-masonry-grid">
            <?php foreach ($gift['memories'] as $m): ?>
            <div class="masonry-item js-lightbox-trigger" data-img="<?php echo htmlspecialchars($m['image_url']); ?>" data-caption="<?php echo htmlspecialchars($m['caption'] ?? $m['title']); ?>">
                <img src="<?php echo htmlspecialchars($m['image_url']); ?>" alt="<?php echo htmlspecialchars($m['title'] ?: 'Romantic Memory'); ?>" loading="lazy">
                <div class="masonry-overlay">
                    <h4 style="font-family: var(--font-script); font-size: 2rem; margin-bottom: 5px;">
                        <?php echo htmlspecialchars($m['title'] ?: 'Cherished Moment'); ?>
                    </h4>
                    <?php if (!empty($m['caption'])): ?>
                    <p style="font-size: 0.95rem; opacity: 0.9;"><?php echo htmlspecialchars($m['caption']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($m['memory_date'])): ?>
                    <span style="font-size: 0.8rem; margin-top: 8px; opacity: 0.75;"><i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars($m['memory_date']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- Our Story Timeline Section -->
    <?php if (!empty($gift['show_timeline']) && !empty($gift['timeline'])): ?>
    <section id="timeline-section" style="padding: 80px 5%; position: relative; z-index: 1;">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 class="font-script" style="font-size: clamp(2.8rem, 6vw, 4.5rem); color: var(--theme-deep);">
                Our Journey Together
            </h2>
            <p style="color: var(--theme-text-muted); font-size: 1.2rem; font-style: italic;">
                The milestones that made us who we are today
            </p>
        </div>

        <div class="romantic-timeline">
            <?php foreach ($gift['timeline'] as $t): ?>
            <div class="timeline-row">
                <div class="timeline-badge">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="timeline-node">
                    <div class="timeline-card-box">
                        <?php if (!empty($t['event_date'])): ?>
                        <span class="timeline-date-label"><?php echo htmlspecialchars($t['event_date']); ?></span>
                        <?php endif; ?>
                        <h3 style="font-size: 1.4rem; color: var(--theme-deep); margin-bottom: 8px;">
                            <?php echo htmlspecialchars($t['title']); ?>
                        </h3>
                        <p style="color: var(--theme-text-muted); font-size: 1.05rem; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($t['description'])); ?>
                        </p>
                        <?php if (!empty($t['image_url'])): ?>
                        <div style="margin-top: 15px; border-radius: 10px; overflow: hidden; max-height: 200px;">
                            <img src="<?php echo htmlspecialchars($t['image_url']); ?>" alt="Milestone" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Love Letter Section -->
    <?php if (!empty($gift['show_letter']) && !empty($gift['letter_content'])): ?>
    <section id="letter-section" style="padding: 90px 5%; position: relative; z-index: 1;">
        <div style="text-align: center; margin-bottom: 40px;">
            <h2 class="font-script" style="font-size: clamp(2.8rem, 6vw, 4.5rem); color: var(--theme-deep);">
                <?php echo htmlspecialchars($gift['letter_title'] ?: 'A Letter To You'); ?>
            </h2>
        </div>

        <div class="envelope-wrapper">
            <div class="letter-sheet">
                <div class="letter-heading">
                    Dearest <?php echo htmlspecialchars($gift['recipient_name']); ?>,
                </div>
                <div class="letter-body">
                    <?php echo nl2br(htmlspecialchars($gift['letter_content'])); ?>
                </div>
                <div class="letter-sign">
                    <?php echo htmlspecialchars($gift['letter_signature'] ?: ('Forever, ' . $gift['sender_name'])); ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Relationship Milestone Counters -->
    <?php if (!empty($gift['show_counters']) && !empty($gift['counters'])): ?>
    <section id="counters-section" style="padding: 70px 5%; text-align: center; position: relative; z-index: 1;">
        <div style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; max-width: 1100px; margin: 0 auto;">
            <?php foreach ($gift['counters'] as $cnt): ?>
            <div class="glass-box" style="padding: 30px; width: 220px; border-radius: 20px;">
                <i class="fas <?php echo htmlspecialchars($cnt['icon'] ?: 'fa-heart'); ?>" style="font-size: 2.4rem; color: var(--theme-primary); margin-bottom: 12px;"></i>
                <div class="js-counter-number" data-target="<?php echo (int)$cnt['target_value']; ?>" style="font-size: 2.8rem; font-weight: 700; color: var(--theme-deep); margin-bottom: 6px;">
                    0
                </div>
                <div style="font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--theme-text-muted);">
                    <?php echo htmlspecialchars($cnt['label']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Final Surprise Section -->
    <?php if (!empty($gift['show_surprise'])): ?>
    <section id="surprise-section" style="padding: 110px 5% 90px; text-align: center; position: relative; z-index: 1; background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.06));">
        <i class="fas fa-heart" style="font-size: 5rem; color: var(--theme-primary); animation: pulseHeart 1.8s infinite ease-in-out; margin-bottom: 25px; filter: drop-shadow(0 0 25px var(--theme-accent-glow));"></i>
        
        <h2 class="font-script" style="font-size: clamp(3rem, 7vw, 5.5rem); color: var(--theme-deep); margin-bottom: 15px;">
            <?php echo htmlspecialchars($gift['surprise_title'] ?: 'Forever Starts With You ❤️'); ?>
        </h2>

        <?php if (!empty($gift['surprise_message'])): ?>
        <p style="font-size: 1.35rem; color: var(--theme-text-muted); max-width: 700px; margin: 0 auto 35px; line-height: 1.7;">
            <?php echo nl2br(htmlspecialchars($gift['surprise_message'])); ?>
        </p>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <button class="btn-romantic" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
                <i class="fas fa-arrow-up"></i> Relive Our Story From The Beginning
            </button>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer style="text-align: center; padding: 40px 20px; font-size: 0.9rem; color: var(--theme-text-muted); position: relative; z-index: 1; border-top: 1px solid var(--theme-glass-border);">
        <p>Created with heartfelt love on <a href="index.php" style="color: var(--theme-primary); text-decoration: none; font-weight: 600;">Romantic Memories</a> ❤️</p>
    </footer>

    <!-- Lightbox Modal -->
    <div class="gift-lightbox" id="gift-lightbox">
        <div class="gift-lightbox-inner">
            <span class="gift-lightbox-close" id="lightbox-close">&times;</span>
            <img src="" alt="Enlarged Memory" class="gift-lightbox-img" id="lightbox-img">
            <p id="lightbox-caption" style="color: #FFF; font-family: var(--font-script); font-size: 2rem; margin-top: 15px;"></p>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="romantic-modal-overlay" id="share-modal">
        <div class="romantic-modal-card">
            <h3 class="font-script" style="font-size: 2.8rem; color: var(--theme-deep); margin-bottom: 10px;">Share Your Gift</h3>
            <p style="color: var(--theme-text-muted); margin-bottom: 25px;">
                Send this unique romantic link to your partner or save it as a timeless keepsake.
            </p>
            <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                <input type="text" id="share-link-input" class="romantic-input" value="<?php echo BASE_URL . '/gift.php?slug=' . urlencode($gift['slug']); ?>" readonly style="font-size: 0.95rem;">
                <button id="btn-copy-link" class="btn-romantic btn-romantic-sm"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 25px;">
                <a id="share-whatsapp" href="#" target="_blank" class="btn-romantic-outline btn-romantic-sm" style="color: #25D366 !important; border-color: #25D366;">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a id="share-telegram" href="#" target="_blank" class="btn-romantic-outline btn-romantic-sm" style="color: #0088cc !important; border-color: #0088cc;">
                    <i class="fab fa-telegram"></i> Telegram
                </a>
            </div>
            <button id="btn-close-share" class="btn-romantic-outline btn-romantic-sm">Close</button>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="romantic-toast" id="toast-msg">
        <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
        <span id="toast-text">Link copied to clipboard!</span>
    </div>

    <!-- Official YouTube Player API & Interactive Script -->
    <script>
        const giftConfig = {
            videoId: <?php echo json_encode($musicVideoId ?? null); ?>,
            hasMusic: <?php echo !empty($musicVideoId) ? 'true' : 'false'; ?>,
            pageUrl: <?php echo json_encode(BASE_URL . '/gift.php?slug=' . urlencode($gift['slug'])); ?>,
            title: <?php echo json_encode($gift['title']); ?>
        };

        // YouTube IFrame Player API
        let ytPlayer = null;
        let isMusicPlaying = false;
        let isMuted = false;
        let userInteracted = false;
        let manuallyPaused = false;

        function startPlayingMusic() {
            if (manuallyPaused) return;
            if (ytPlayer && typeof ytPlayer.playVideo === 'function') {
                ytPlayer.unMute();
                ytPlayer.setVolume(65);
                ytPlayer.playVideo();
            }
            const prompt = document.getElementById('autoplay-prompt');
            if (prompt) prompt.style.display = 'none';
        }

        // Global first-interaction unlock (click, touch, scroll, keydown)
        const unlockAudioOnGesture = () => {
            userInteracted = true;
            if (!isMusicPlaying && !manuallyPaused) {
                startPlayingMusic();
            }
            ['click', 'touchstart', 'scroll', 'keydown'].forEach(evt => {
                window.removeEventListener(evt, unlockAudioOnGesture);
            });
        };

        ['click', 'touchstart', 'scroll', 'keydown'].forEach(evt => {
            window.addEventListener(evt, unlockAudioOnGesture, { passive: true });
        });

        function onYouTubeIframeAPIReady() {
            if (!giftConfig.hasMusic || !giftConfig.videoId) return;

            ytPlayer = new YT.Player('yt-player-container', {
                height: '240',
                width: '240',
                videoId: giftConfig.videoId,
                playerVars: {
                    'autoplay': 1,
                    'controls': 0,
                    'loop': 1,
                    'playlist': giftConfig.videoId,
                    'playsinline': 1,
                    'rel': 0,
                    'origin': window.location.origin
                },
                events: {
                    'onReady': onPlayerReady,
                    'onStateChange': onPlayerStateChange,
                    'onError': onPlayerError
                }
            });
        }

        function onPlayerReady(event) {
            event.target.unMute();
            event.target.setVolume(65);
            
            if (userInteracted) {
                startPlayingMusic();
            } else {
                // Attempt autoplay
                event.target.playVideo();
            }
            
            // Check after a brief delay if playing started
            setTimeout(() => {
                if (ytPlayer && typeof ytPlayer.getPlayerState === 'function') {
                    const state = ytPlayer.getPlayerState();
                    if (state !== YT.PlayerState.PLAYING && !manuallyPaused) {
                        // Browser autoplay policy prevented playback; show friendly prompt
                        const prompt = document.getElementById('autoplay-prompt');
                        if (prompt) prompt.style.display = 'flex';
                    }
                }
            }, 1400);
        }

        function onPlayerStateChange(event) {
            const disc = document.getElementById('music-disc');
            const playIcon = document.getElementById('play-icon');
            const status = document.getElementById('music-status');
            const prompt = document.getElementById('autoplay-prompt');

            if (event.data === YT.PlayerState.PLAYING) {
                isMusicPlaying = true;
                if (disc) disc.classList.add('playing');
                if (playIcon) playIcon.className = 'fas fa-pause';
                if (status) status.innerText = 'Playing...';
                if (prompt) prompt.style.display = 'none';
            } else {
                isMusicPlaying = false;
                if (disc) disc.classList.remove('playing');
                if (playIcon) playIcon.className = 'fas fa-play';
                if (status) status.innerText = 'Paused';
            }
        }

        function onPlayerError(e) {
            console.warn('YouTube Player notice/error:', e.data);
            const status = document.getElementById('music-status');
            if (status) status.innerText = 'Song unavailable';
        }

        // Load YouTube Script
        if (giftConfig.hasMusic) {
            const tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            document.head.appendChild(tag);
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Ambient floating particles
            const pContainer = document.getElementById('ambient-particles');
            if (pContainer) {
                for (let i = 0; i < 25; i++) {
                    const el = document.createElement('div');
                    el.style.position = 'absolute';
                    el.style.bottom = '-30px';
                    el.style.left = Math.random() * 100 + 'vw';
                    el.style.color = 'var(--theme-primary)';
                    el.style.opacity = Math.random() * 0.45 + 0.15;
                    el.style.fontSize = (Math.random() * 18 + 10) + 'px';
                    el.innerHTML = Math.random() > 0.4 ? '❤️' : '✨';
                    
                    const duration = Math.random() * 12 + 10;
                    const delay = Math.random() * 8;
                    el.style.animation = `floatParticle ${duration}s infinite linear ${delay}s`;
                    pContainer.appendChild(el);
                }
                const style = document.createElement('style');
                style.innerHTML = `@keyframes floatParticle {
                    0% { transform: translateY(0) rotate(0deg); opacity: 0; }
                    20% { opacity: 0.5; }
                    80% { opacity: 0.5; }
                    100% { transform: translateY(-110vh) rotate(360deg); opacity: 0; }
                }`;
                document.head.appendChild(style);
            }

            // Music Controls
            const btnTogglePlay = document.getElementById('btn-toggle-play');
            const btnToggleMute = document.getElementById('btn-toggle-mute');
            const btnStartAudio = document.getElementById('btn-start-audio');
            const btnDismissAudio = document.getElementById('btn-dismiss-audio');
            const heroPlaySong = document.getElementById('hero-play-song');

            if (btnTogglePlay) {
                btnTogglePlay.addEventListener('click', () => {
                    if (!ytPlayer || typeof ytPlayer.playVideo !== 'function') return;
                    if (isMusicPlaying) {
                        manuallyPaused = true;
                        ytPlayer.pauseVideo();
                    } else {
                        manuallyPaused = false;
                        ytPlayer.playVideo();
                    }
                });
            }

            if (btnToggleMute) {
                btnToggleMute.addEventListener('click', () => {
                    if (!ytPlayer || typeof ytPlayer.mute !== 'function') return;
                    const muteIcon = document.getElementById('mute-icon');
                    if (isMuted) {
                        ytPlayer.unMute();
                        isMuted = false;
                        if (muteIcon) muteIcon.className = 'fas fa-volume-up';
                    } else {
                        ytPlayer.mute();
                        isMuted = true;
                        if (muteIcon) muteIcon.className = 'fas fa-volume-mute';
                    }
                });
            }

            if (btnStartAudio) btnStartAudio.addEventListener('click', () => {
                manuallyPaused = false;
                startPlayingMusic();
            });
            if (heroPlaySong) heroPlaySong.addEventListener('click', () => {
                manuallyPaused = false;
                startPlayingMusic();
            });
            if (btnDismissAudio) {
                btnDismissAudio.addEventListener('click', () => {
                    manuallyPaused = true;
                    document.getElementById('autoplay-prompt').style.display = 'none';
                });
            }

            // Lightbox
            const lightbox = document.getElementById('gift-lightbox');
            const lightboxImg = document.getElementById('lightbox-img');
            const lightboxCaption = document.getElementById('lightbox-caption');
            const lightboxClose = document.getElementById('lightbox-close');

            document.querySelectorAll('.js-lightbox-trigger').forEach(item => {
                item.addEventListener('click', () => {
                    const src = item.getAttribute('data-img');
                    const cap = item.getAttribute('data-caption') || '';
                    if (lightbox && lightboxImg) {
                        lightboxImg.src = src;
                        lightboxCaption.innerText = cap;
                        lightbox.classList.add('active');
                    }
                });
            });

            if (lightboxClose) {
                lightboxClose.addEventListener('click', () => lightbox.classList.remove('active'));
            }
            if (lightbox) {
                lightbox.addEventListener('click', (e) => {
                    if (e.target === lightbox) lightbox.classList.remove('active');
                });
            }

            // Animated Counters
            if (window.gsap && window.ScrollTrigger) {
                gsap.registerPlugin(ScrollTrigger);
                ScrollTrigger.create({
                    trigger: "#counters-section",
                    start: "top 80%",
                    once: true,
                    onEnter: () => {
                        document.querySelectorAll('.js-counter-number').forEach(counter => {
                            const target = +counter.getAttribute('data-target') || 0;
                            const obj = { val: 0 };
                            gsap.to(obj, {
                                val: target,
                                duration: 2.2,
                                ease: "power2.out",
                                onUpdate: () => {
                                    counter.innerText = Math.round(obj.val).toLocaleString();
                                }
                            });
                        });
                    }
                });
            }

            // Share Modal
            const shareModal = document.getElementById('share-modal');
            const btnOpenShare = document.getElementById('btn-open-share');
            const btnCloseShare = document.getElementById('btn-close-share');
            const btnCopyLink = document.getElementById('btn-copy-link');
            const shareInput = document.getElementById('share-link-input');
            const toast = document.getElementById('toast-msg');

            if (btnOpenShare && shareModal) {
                btnOpenShare.addEventListener('click', () => {
                    shareModal.classList.add('active');
                    // Setup share links
                    const encUrl = encodeURIComponent(shareInput.value);
                    const encText = encodeURIComponent("Here is a romantic digital gift for you ❤️ " + giftConfig.title);
                    document.getElementById('share-whatsapp').href = `https://api.whatsapp.com/send?text=${encText}%20${encUrl}`;
                    document.getElementById('share-telegram').href = `https://t.me/share/url?url=${encUrl}&text=${encText}`;
                });
            }
            if (btnCloseShare && shareModal) {
                btnCloseShare.addEventListener('click', () => shareModal.classList.remove('active'));
            }
            if (shareModal) {
                shareModal.addEventListener('click', (e) => {
                    if (e.target === shareModal) shareModal.classList.remove('active');
                });
            }

            if (btnCopyLink && shareInput) {
                btnCopyLink.addEventListener('click', () => {
                    navigator.clipboard.writeText(shareInput.value).then(() => {
                        showToast('Gift link copied to clipboard!');
                    }).catch(() => {
                        shareInput.select();
                        document.execCommand('copy');
                        showToast('Gift link copied!');
                    });
                });
            }

            function showToast(text) {
                if (!toast) return;
                document.getElementById('toast-text').innerText = text;
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3500);
            }
        });
    </script>
</body>
</html>
