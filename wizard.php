<?php
/**
 * Romantic Gift Creation Wizard (6 Guided Steps)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/I18n.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/GiftService.php';
require_once __DIR__ . '/core/ThemeRegistry.php';

$allThemes = ThemeRegistry::getAll();

// Check if pre-populating with template or editing
$templateSlug = trim($_GET['template'] ?? '');
$templateGift = null;
if (!empty($templateSlug)) {
    $templateGift = GiftService::getGiftBySlug($templateSlug);
}
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::getLang(); ?>" dir="<?php echo I18n::getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('wiz_title'); ?> - <?php echo __('site_title'); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Platform Stylesheet -->
    <link rel="stylesheet" href="css/platform.css">

    <style>
        .wizard-container {
            max-width: 900px;
            margin: 110px auto 60px;
            padding: 0 20px;
        }

        .wizard-progress {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            position: relative;
        }

        .wizard-progress::before {
            content: '';
            position: absolute;
            top: 22px;
            left: 20px;
            right: 20px;
            height: 3px;
            background: var(--theme-glass-border);
            z-index: 0;
        }

        .progress-bar-fill {
            position: absolute;
            top: 22px;
            left: 20px;
            height: 3px;
            background: var(--theme-primary);
            width: 0%;
            z-index: 1;
            transition: width 0.4s ease;
        }

        .progress-step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
        }

        .step-circle {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--theme-card-bg);
            border: 2px solid var(--theme-glass-border);
            color: var(--theme-text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
            transition: var(--transition-smooth);
        }

        .progress-step.active .step-circle {
            border-color: var(--theme-primary);
            background: var(--theme-primary);
            color: #FFF;
            box-shadow: 0 0 15px var(--theme-accent-glow);
        }

        .progress-step.completed .step-circle {
            border-color: var(--theme-primary);
            background: var(--theme-card-bg);
            color: var(--theme-primary);
        }

        .step-label {
            font-size: 0.82rem;
            margin-top: 8px;
            color: var(--theme-text-muted);
            font-weight: 500;
            text-align: center;
        }

        .progress-step.active .step-label {
            color: var(--theme-deep);
            font-weight: 700;
        }

        .wizard-card {
            background: var(--theme-card-bg);
            border: 1px solid var(--theme-glass-border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow-elevated);
            margin-bottom: 30px;
        }

        .step-panel {
            display: none;
        }

        .step-panel.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .theme-option-card {
            border: 2px solid var(--theme-glass-border);
            border-radius: 16px;
            padding: 16px;
            cursor: pointer;
            transition: var(--transition-smooth);
            text-align: center;
        }

        .theme-option-card.selected, .theme-option-card:hover {
            border-color: var(--theme-primary);
            box-shadow: 0 0 15px var(--theme-accent-glow);
            transform: translateY(-3px);
        }

        .memory-card-item {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid var(--theme-glass-border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
        }

        .image-dropzone {
            border: 2px dashed var(--theme-primary);
            border-radius: 14px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.5);
            transition: var(--transition-smooth);
        }

        .image-dropzone:hover {
            background: rgba(217, 138, 155, 0.08);
        }

        .image-preview-box {
            width: 100%;
            height: 180px;
            border-radius: 12px;
            overflow: hidden;
            display: none;
            position: relative;
            margin-top: 10px;
        }

        .image-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .form-row {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--theme-deep);
        }

        .form-hint {
            font-size: 0.8rem;
            color: var(--theme-text-muted);
            margin-top: 4px;
        }
    </style>
</head>
<body data-theme="luxury-cream">

    <!-- Top Platform Nav -->
    <nav class="platform-nav">
        <a href="index.php" class="platform-logo">
            <i class="fas fa-heart logo-icon"></i>
            <span class="logo-text">Romantic Memories</span>
        </a>
        <div>
            <a href="index.php" class="btn-romantic-outline btn-romantic-sm">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </nav>

    <div class="wizard-container">
        <!-- Progress Stepper -->
        <div class="wizard-progress">
            <div class="progress-bar-fill" id="progress-fill"></div>
            
            <div class="progress-step active" data-step="1">
                <div class="step-circle"><i class="fas fa-heart"></i></div>
                <span class="step-label">1. Basics</span>
            </div>
            <div class="progress-step" data-step="2">
                <div class="step-circle"><i class="fas fa-palette"></i></div>
                <span class="step-label">2. Theme</span>
            </div>
            <div class="progress-step" data-step="3">
                <div class="step-circle"><i class="fas fa-music"></i></div>
                <span class="step-label">3. Music</span>
            </div>
            <div class="progress-step" data-step="4">
                <div class="step-circle"><i class="fas fa-images"></i></div>
                <span class="step-label">4. Memories</span>
            </div>
            <div class="progress-step" data-step="5">
                <div class="step-circle"><i class="fas fa-envelope-open-text"></i></div>
                <span class="step-label">5. Story & Letter</span>
            </div>
            <div class="progress-step" data-step="6">
                <div class="step-circle"><i class="fas fa-rocket"></i></div>
                <span class="step-label">6. Publish</span>
            </div>
        </div>

        <form id="wizard-form">
            <div class="wizard-card">
                
                <!-- STEP 1: Basic Information -->
                <div class="step-panel active" id="step-1">
                    <h2 class="font-script" style="font-size: 3rem; color: var(--theme-deep); margin-bottom: 10px;">
                        Step 1 — Basic Information
                    </h2>
                    <p style="color: var(--theme-text-muted); margin-bottom: 30px;">
                        Tell us who this special gift is for and set the opening romantic atmosphere.
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-row">
                            <label class="form-label">Your Name (Sender) *</label>
                            <input type="text" name="sender_name" id="sender_name" class="romantic-input" placeholder="e.g. Mohamed or Ahmed" required>
                        </div>
                        <div class="form-row">
                            <label class="form-label">Partner's Name (Recipient) *</label>
                            <input type="text" name="recipient_name" id="recipient_name" class="romantic-input" placeholder="e.g. Salma or Habiba" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="form-label">Website Title</label>
                        <input type="text" name="title" id="gift_title" class="romantic-input" placeholder="e.g. Mohamed & Salma - Our Love Story">
                        <div class="form-hint">Displayed in browser tab and social previews.</div>
                    </div>

                    <div class="form-row">
                        <label class="form-label">Personalized Welcome Message</label>
                        <textarea name="welcome_message" id="welcome_message" rows="3" class="romantic-textarea" placeholder="Every moment with you became my favorite memory. | كل لحظة معاكي بقت أجمل ذكرى في حياتي.">Every moment with you became my favorite memory.</textarea>
                    </div>

                    <div class="form-row">
                        <label class="form-label">Custom Link Slug (Optional)</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: var(--theme-text-muted); font-size: 0.9rem;"><?php echo BASE_URL; ?>/gift/</span>
                            <input type="text" name="slug" id="slug" class="romantic-input" placeholder="our-special-story">
                        </div>
                        <div class="form-hint">Leave blank to automatically generate from your names.</div>
                    </div>
                </div>

                <!-- STEP 2: Theme and Appearance -->
                <div class="step-panel" id="step-2">
                    <h2 class="font-script" style="font-size: 3rem; color: var(--theme-deep); margin-bottom: 10px;">
                        Step 2 — Theme & Aesthetics
                    </h2>
                    <p style="color: var(--theme-text-muted); margin-bottom: 30px;">
                        Choose an emotional color palette and gallery arrangement for your partner.
                    </p>

                    <input type="hidden" name="theme_id" id="theme_id" value="luxury-cream">
                    <label class="form-label" style="margin-bottom: 15px;">Select Visual Palette:</label>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 35px;">
                        <?php foreach ($allThemes as $tId => $t): ?>
                        <div class="theme-option-card <?php echo ($tId === 'luxury-cream') ? 'selected' : ''; ?>" data-theme="<?php echo $tId; ?>">
                            <div style="height: 60px; border-radius: 10px; background: <?php echo $t['bg_gradient']; ?>; margin-bottom: 10px;"></div>
                            <strong style="color: var(--theme-deep);"><?php echo htmlspecialchars(I18n::isRtl() ? $t['name_ar'] : $t['name_en']); ?></strong>
                            <div style="font-size: 0.75rem; color: var(--theme-text-muted); margin-top: 4px;"><?php echo htmlspecialchars(I18n::isRtl() ? $t['desc_ar'] : $t['desc_en']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-row">
                        <label class="form-label">Memories Gallery Style:</label>
                        <select name="gallery_style" id="gallery_style" class="romantic-select">
                            <option value="polaroid">Polaroid Scrapbook (Tilted photo cards with handwritten notes)</option>
                            <option value="masonry">Editorial Masonry (Modern dynamic photo grid with hover details)</option>
                        </select>
                    </div>
                </div>

                <!-- STEP 3: Music (YouTube Integration) -->
                <div class="step-panel" id="step-3">
                    <h2 class="font-script" style="font-size: 3rem; color: var(--theme-deep); margin-bottom: 10px;">
                        Step 3 — Background Song
                    </h2>
                    <p style="color: var(--theme-text-muted); margin-bottom: 30px;">
                        Integrate your special song using any YouTube link (full video, shorts, or share link).
                    </p>

                    <div class="form-row">
                        <label class="form-label">YouTube URL</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" name="music_url" id="music_url" class="romantic-input" placeholder="https://www.youtube.com/watch?v=g7Zw5x6GKlE" value="https://www.youtube.com/watch?v=g7Zw5x6GKlE">
                            <button type="button" id="btn-preview-yt" class="btn-romantic btn-romantic-sm" style="white-space: nowrap;">
                                <i class="fas fa-play"></i> Test Link
                            </button>
                        </div>
                        <div class="form-hint">Supports: youtube.com/watch?v=..., youtu.be/..., and youtube.com/shorts/...</div>
                    </div>

                    <div class="form-row">
                        <label class="form-label">Song Title / Dedication</label>
                        <input type="text" name="music_title" id="music_title" class="romantic-input" placeholder="e.g. Perfect - Ed Sheeran / أغنيتنا المفضلة" value="Our Song ❤️">
                    </div>

                    <!-- YouTube Live Preview Frame -->
                    <div id="yt-preview-wrapper" style="margin-top: 25px; border-radius: 16px; overflow: hidden; background: #000; height: 260px; display: flex; align-items: center; justify-content: center;">
                        <iframe id="yt-preview-iframe" width="100%" height="100%" src="https://www.youtube-nocookie.com/embed/g7Zw5x6GKlE?enablejsapi=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                    <div id="yt-status-msg" style="margin-top: 10px; font-size: 0.9rem; color: #4CAF50; text-align: center;">
                        <i class="fas fa-check-circle"></i> Song connected and ready.
                    </div>
                </div>

                <!-- STEP 4: Memories Gallery (5 Initial Cards) -->
                <div class="step-panel" id="step-4">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h2 class="font-script" style="font-size: 3rem; color: var(--theme-deep);">
                            Step 4 — Memories Gallery
                        </h2>
                        <button type="button" id="btn-add-memory" class="btn-romantic btn-romantic-sm">
                            <i class="fas fa-plus"></i> Add Memory Card
                        </button>
                    </div>
                    <p style="color: var(--theme-text-muted); margin-bottom: 25px;">
                        Configure your 5 initial romantic memories. You can upload photos, add captions, dates, and create unlimited additional cards.
                    </p>

                    <div id="memories-container">
                        <!-- Default 5 memory cards will be populated via JS -->
                    </div>
                </div>

                <!-- STEP 5: Story & Love Letter -->
                <div class="step-panel" id="step-5">
                    <h2 class="font-script" style="font-size: 3rem; color: var(--theme-deep); margin-bottom: 10px;">
                        Step 5 — Story Timeline & Love Letter
                    </h2>
                    <p style="color: var(--theme-text-muted); margin-bottom: 30px;">
                        Add relationship milestones and pen your personal love letter.
                    </p>

                    <div style="margin-bottom: 40px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h3 style="font-size: 1.4rem; color: var(--theme-deep);"><i class="fas fa-stream"></i> Relationship Milestones</h3>
                            <button type="button" id="btn-add-milestone" class="btn-romantic-outline btn-romantic-sm"><i class="fas fa-plus"></i> Add Milestone</button>
                        </div>
                        <div id="timeline-container">
                            <!-- Milestone 1 -->
                            <div class="memory-card-item" style="padding: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                                    <input type="text" class="romantic-input tl-title" placeholder="Milestone Title (e.g. The Day We Met)" value="The Day We Met">
                                    <input type="text" class="romantic-input tl-date" placeholder="Date (e.g. August 14, 2024)" value="August 14, 2024">
                                </div>
                                <textarea class="romantic-textarea tl-desc" rows="2" placeholder="Description of this special milestone">The exact second I saw you, the world seemed to stand still.</textarea>
                            </div>
                            <!-- Milestone 2 -->
                            <div class="memory-card-item" style="padding: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                                    <input type="text" class="romantic-input tl-title" placeholder="Milestone Title" value="Our First Date">
                                    <input type="text" class="romantic-input tl-date" placeholder="Date" value="September 2, 2024">
                                </div>
                                <textarea class="romantic-textarea tl-desc" rows="2" placeholder="Description">We talked for hours and forgot that time even existed.</textarea>
                            </div>
                        </div>
                    </div>

                    <hr style="border: 0; border-top: 1px solid var(--theme-glass-border); margin: 35px 0;">

                    <div style="margin-bottom: 35px;">
                        <h3 style="font-size: 1.4rem; color: var(--theme-deep); margin-bottom: 15px;"><i class="fas fa-envelope-open-text"></i> Handwritten Love Letter</h3>
                        <div class="form-row">
                            <label class="form-label">Letter Title</label>
                            <input type="text" name="letter_title" id="letter_title" class="romantic-input" value="A Letter From My Heart">
                        </div>
                        <div class="form-row">
                            <label class="form-label">Letter Message</label>
                            <textarea name="letter_content" id="letter_content" rows="6" class="romantic-textarea">My Love,

Thank you for bringing so much peace, laughter, and joy into my world. Every single day with you reminds me how lucky I am to have found my soulmate.

No matter where life takes us, my heart will always belong to you.</textarea>
                        </div>
                        <div class="form-row">
                            <label class="form-label">Signature</label>
                            <input type="text" name="letter_signature" id="letter_signature" class="romantic-input" value="Forever Yours ❤️">
                        </div>
                    </div>

                    <hr style="border: 0; border-top: 1px solid var(--theme-glass-border); margin: 35px 0;">

                    <div>
                        <h3 style="font-size: 1.4rem; color: var(--theme-deep); margin-bottom: 15px;"><i class="fas fa-gem"></i> Final Surprise & Anniversary</h3>
                        <div class="form-row">
                            <label class="form-label">Surprise Headline</label>
                            <input type="text" name="surprise_title" id="surprise_title" class="romantic-input" value="Forever Starts With You ❤️">
                        </div>
                        <div class="form-row">
                            <label class="form-label">Surprise Love Note</label>
                            <textarea name="surprise_message" id="surprise_message" rows="2" class="romantic-textarea">Every single day with you is my greatest adventure. I love you more with every heartbeat.</textarea>
                        </div>
                    </div>
                </div>

                <!-- STEP 6: Preview & Publish -->
                <div class="step-panel" id="step-6">
                    <h2 class="font-script" style="font-size: 3rem; color: var(--theme-deep); margin-bottom: 10px;">
                        Step 6 — Review & Publish
                    </h2>
                    <p style="color: var(--theme-text-muted); margin-bottom: 30px;">
                        You are ready to create your timeless romantic digital gift! Choose whether to save as a draft or publish immediately.
                    </p>

                    <div class="glass-box" style="padding: 25px; margin-bottom: 30px;">
                        <h4 style="font-size: 1.3rem; color: var(--theme-deep); margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: var(--theme-primary);"></i> Gift Summary</h4>
                        <ul style="list-style: none; line-height: 2; color: var(--theme-text-main);">
                            <li><strong>Sender:</strong> <span id="summary-sender">-</span></li>
                            <li><strong>Recipient:</strong> <span id="summary-recipient">-</span></li>
                            <li><strong>Website Title:</strong> <span id="summary-title">-</span></li>
                            <li><strong>Theme:</strong> <span id="summary-theme">Luxury Cream</span></li>
                            <li><strong>Song:</strong> <span id="summary-music">YouTube Audio Connected</span></li>
                            <li><strong>Memories Configured:</strong> <span id="summary-memories-count">5 memory cards</span></li>
                        </ul>
                    </div>

                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" id="btn-save-draft" class="btn-romantic-outline">
                            <i class="fas fa-save"></i> Save as Draft
                        </button>
                        <button type="button" id="btn-publish-gift" class="btn-romantic">
                            <i class="fas fa-rocket"></i> Publish My Gift Website
                        </button>
                    </div>
                </div>

                <!-- Wizard Navigation Buttons -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; border-top: 1px solid var(--theme-glass-border); padding-top: 25px;">
                    <button type="button" id="btn-prev-step" class="btn-romantic-outline btn-romantic-sm" style="display: none;">
                        <i class="fas fa-arrow-left"></i> Previous Step
                    </button>
                    <div style="margin-left: auto;">
                        <button type="button" id="btn-next-step" class="btn-romantic">
                            Next Step <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- Publishing Success Modal -->
    <div class="romantic-modal-overlay" id="publish-success-modal">
        <div class="romantic-modal-card">
            <i class="fas fa-heart" style="font-size: 4rem; color: var(--theme-primary); animation: pulseHeart 1.5s infinite ease-in-out; margin-bottom: 15px;"></i>
            <h2 class="font-script" style="font-size: 3rem; color: var(--theme-deep); margin-bottom: 10px;">Your Gift Is Published!</h2>
            <p style="color: var(--theme-text-muted); margin-bottom: 25px;">
                Your unique romantic website is ready to be shared with your partner.
            </p>
            <div style="margin-bottom: 25px;">
                <input type="text" id="final-gift-url" class="romantic-input" readonly style="font-size: 1rem; text-align: center; margin-bottom: 10px;">
                <button type="button" id="btn-copy-published-url" class="btn-romantic btn-romantic-sm">
                    <i class="fas fa-copy"></i> Copy Shareable URL
                </button>
            </div>
            <div style="display: flex; justify-content: center; gap: 12px; margin-bottom: 25px;">
                <a id="btn-view-gift" href="#" target="_blank" class="btn-romantic">
                    <i class="fas fa-eye"></i> View Live Gift
                </a>
                <a id="btn-open-editor" href="#" class="btn-romantic-outline">
                    <i class="fas fa-pencil-alt"></i> Open Visual Editor
                </a>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="romantic-toast" id="toast-msg">
        <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
        <span id="toast-text">Success</span>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 6;

        // Default 5 initial memories
        const initialMemories = [
            { title: "Pure Joy", caption: "The moment our laughter lit up the entire day.", date: "Memory #1", img: "assets/images/6.jpg" },
            { title: "Quiet Romance", caption: "In your quiet gaze, I found my entire world.", date: "Memory #2", img: "assets/images/7.jpg" },
            { title: "Under The Golden Sky", caption: "Walking by the sea, dreaming of our future together.", date: "Memory #3", img: "assets/images/8.jpg" },
            { title: "Unforgettable Smile", caption: "Your smile heals everything inside my heart.", date: "Memory #4", img: "assets/images/11.jpg" },
            { title: "Forever & Always", caption: "With you, every simple moment turns into poetry.", date: "Memory #5", img: "assets/images/12.jpg" }
        ];

        function renderMemories() {
            const container = document.getElementById('memories-container');
            container.innerHTML = '';
            initialMemories.forEach((mem, index) => {
                appendMemoryCard(mem.title, mem.caption, mem.date, mem.img, index + 1);
            });
        }

        function appendMemoryCard(title = '', caption = '', date = '', img = '', num = 1) {
            const container = document.getElementById('memories-container');
            const card = document.createElement('div');
            card.className = 'memory-card-item';
            card.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <strong><i class="fas fa-image"></i> Memory Card #${num}</strong>
                    <button type="button" class="btn-romantic-outline btn-romantic-sm btn-delete-mem" style="padding: 4px 10px; font-size: 0.8rem; border-color: #ff5252; color: #ff5252 !important;">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div style="display: grid; grid-template-columns: 240px 1fr; gap: 20px;">
                    <div>
                        <div class="image-dropzone">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 1.8rem; color: var(--theme-primary); margin-bottom: 6px;"></i>
                            <div style="font-size: 0.85rem; font-weight: 600;">Click to upload photo</div>
                            <div style="font-size: 0.75rem; color: #888;">JPG, PNG, WEBP (Max 10MB)</div>
                            <input type="file" accept="image/*" class="mem-file-input" style="display: none;">
                        </div>
                        <div class="image-preview-box" style="${img ? 'display:block;' : ''}">
                            <img src="${img}" alt="Preview" class="mem-preview-img">
                        </div>
                        <input type="hidden" class="mem-img-url" value="${img}">
                    </div>
                    <div>
                        <div class="form-row" style="margin-bottom: 12px;">
                            <label class="form-label" style="font-size: 0.85rem;">Memory Title</label>
                            <input type="text" class="romantic-input mem-title" placeholder="e.g. Our First Trip" value="${escapeHtml(title)}">
                        </div>
                        <div class="form-row" style="margin-bottom: 12px;">
                            <label class="form-label" style="font-size: 0.85rem;">Date or Milestone Tag</label>
                            <input type="text" class="romantic-input mem-date" placeholder="e.g. July 2024 or Memory #1" value="${escapeHtml(date)}">
                        </div>
                        <div class="form-row" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 0.85rem;">Personal Message / Caption</label>
                            <textarea class="romantic-textarea mem-caption" rows="2" placeholder="Write a sweet memory note">${escapeHtml(caption)}</textarea>
                        </div>
                    </div>
                </div>
            `;

            // Setup file upload
            const dropzone = card.querySelector('.image-dropzone');
            const fileInput = card.querySelector('.mem-file-input');
            const previewBox = card.querySelector('.image-preview-box');
            const previewImg = card.querySelector('.mem-preview-img');
            const urlInput = card.querySelector('.mem-img-url');
            const deleteBtn = card.querySelector('.btn-delete-mem');

            dropzone.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', () => {
                if (fileInput.files && fileInput.files[0]) {
                    uploadImage(fileInput.files[0], (uploadedUrl) => {
                        urlInput.value = uploadedUrl;
                        previewImg.src = uploadedUrl;
                        previewBox.style.display = 'block';
                        showToast('Photo uploaded successfully!');
                    });
                }
            });

            deleteBtn.addEventListener('click', () => {
                card.remove();
                renumberMemories();
            });

            container.appendChild(card);
        }

        function renumberMemories() {
            const cards = document.querySelectorAll('.memory-card-item');
            cards.forEach((c, idx) => {
                const label = c.querySelector('strong');
                if (label && label.innerText.includes('Memory Card')) {
                    label.innerHTML = `<i class="fas fa-image"></i> Memory Card #${idx + 1}`;
                }
            });
        }

        function uploadImage(file, callback) {
            const formData = new FormData();
            formData.append('image', file);
            showToast('Uploading photo...');

            fetch('api/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    callback(data.url);
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                alert('Upload network error: ' + err.message);
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function updateStep(step) {
            currentStep = step;
            // Panels
            document.querySelectorAll('.step-panel').forEach((panel, i) => {
                panel.classList.toggle('active', i + 1 === step);
            });
            // Stepper indicators
            document.querySelectorAll('.progress-step').forEach((el, i) => {
                const stepNum = i + 1;
                el.classList.toggle('active', stepNum === step);
                el.classList.toggle('completed', stepNum < step);
            });
            // Fill bar
            const percent = ((step - 1) / (totalSteps - 1)) * 100;
            document.getElementById('progress-fill').style.width = percent + '%';

            // Buttons visibility
            document.getElementById('btn-prev-step').style.display = step > 1 ? 'inline-flex' : 'none';
            document.getElementById('btn-next-step').style.display = step < totalSteps ? 'inline-flex' : 'none';

            if (step === 6) {
                updateSummary();
            }

            window.scrollTo({ top: 80, behavior: 'smooth' });
        }

        function updateSummary() {
            document.getElementById('summary-sender').innerText = document.getElementById('sender_name').value || 'Me';
            document.getElementById('summary-recipient').innerText = document.getElementById('recipient_name').value || 'My Love';
            document.getElementById('summary-title').innerText = document.getElementById('gift_title').value || (document.getElementById('sender_name').value + ' & ' + document.getElementById('recipient_name').value);
            document.getElementById('summary-theme').innerText = document.getElementById('theme_id').value;
            const count = document.querySelectorAll('#memories-container .memory-card-item').length;
            document.getElementById('summary-memories-count').innerText = count + ' memory cards configured';
        }

        // Stepper click
        document.querySelectorAll('.progress-step').forEach(stepEl => {
            stepEl.addEventListener('click', () => {
                const target = parseInt(stepEl.getAttribute('data-step'));
                if (validateStep(currentStep)) {
                    updateStep(target);
                }
            });
        });

        document.getElementById('btn-prev-step').addEventListener('click', () => {
            if (currentStep > 1) updateStep(currentStep - 1);
        });

        document.getElementById('btn-next-step').addEventListener('click', () => {
            if (validateStep(currentStep)) {
                updateStep(currentStep + 1);
            }
        });

        function validateStep(step) {
            if (step === 1) {
                const sender = document.getElementById('sender_name').value.trim();
                const recipient = document.getElementById('recipient_name').value.trim();
                if (!sender || !recipient) {
                    alert('Please enter both your name and your partner’s name.');
                    return false;
                }
            }
            return true;
        }

        // Theme selection
        document.querySelectorAll('.theme-option-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.theme-option-card').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                const t = card.getAttribute('data-theme');
                document.getElementById('theme_id').value = t;
                document.body.setAttribute('data-theme', t);
            });
        });

        // Add memory button
        document.getElementById('btn-add-memory').addEventListener('click', () => {
            const count = document.querySelectorAll('#memories-container .memory-card-item').length;
            appendMemoryCard('', '', `Memory #${count + 1}`, '', count + 1);
        });

        // Add milestone button
        document.getElementById('btn-add-milestone').addEventListener('click', () => {
            const container = document.getElementById('timeline-container');
            const item = document.createElement('div');
            item.className = 'memory-card-item';
            item.style.padding = '16px';
            item.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <strong>New Milestone</strong>
                    <button type="button" class="btn-romantic-outline btn-romantic-sm" onclick="this.closest('.memory-card-item').remove()" style="padding: 2px 8px; font-size: 0.75rem; color:#ff5252; border-color:#ff5252;">Remove</button>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                    <input type="text" class="romantic-input tl-title" placeholder="Milestone Title">
                    <input type="text" class="romantic-input tl-date" placeholder="Date">
                </div>
                <textarea class="romantic-textarea tl-desc" rows="2" placeholder="Description of this special milestone"></textarea>
            `;
            container.appendChild(item);
        });

        // YouTube Preview Button
        document.getElementById('btn-preview-yt').addEventListener('click', () => {
            const url = document.getElementById('music_url').value.trim();
            const match = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/)([^"&?\/ ]{11})/i);
            const id = match ? match[1] : (url.length === 11 ? url : null);
            if (id) {
                document.getElementById('yt-preview-iframe').src = `https://www.youtube-nocookie.com/embed/${id}?enablejsapi=1`;
                document.getElementById('yt-status-msg').innerHTML = `<i class="fas fa-check-circle" style="color:#4CAF50;"></i> Song connected: YouTube ID [${id}]`;
            } else {
                alert('Could not find YouTube video ID. Please check the URL.');
            }
        });

        // Collect payload
        function buildPayload(status) {
            const memories = [];
            document.querySelectorAll('#memories-container .memory-card-item').forEach(card => {
                const img = card.querySelector('.mem-img-url').value.trim();
                const title = card.querySelector('.mem-title').value.trim();
                const date = card.querySelector('.mem-date').value.trim();
                const caption = card.querySelector('.mem-caption').value.trim();
                if (img || title || caption) {
                    memories.push({ image_url: img || 'assets/images/1.jpg', title, caption, memory_date: date });
                }
            });

            const timeline = [];
            document.querySelectorAll('#timeline-container .memory-card-item').forEach(card => {
                const title = card.querySelector('.tl-title').value.trim();
                const date = card.querySelector('.tl-date').value.trim();
                const desc = card.querySelector('.tl-desc').value.trim();
                if (title || desc) {
                    timeline.push({ title, description: desc, event_date: date });
                }
            });

            return {
                sender_name: document.getElementById('sender_name').value.trim(),
                recipient_name: document.getElementById('recipient_name').value.trim(),
                title: document.getElementById('gift_title').value.trim(),
                welcome_message: document.getElementById('welcome_message').value.trim(),
                slug: document.getElementById('slug').value.trim(),
                theme_id: document.getElementById('theme_id').value,
                gallery_style: document.getElementById('gallery_style').value,
                music_url: document.getElementById('music_url').value.trim(),
                music_title: document.getElementById('music_title').value.trim(),
                letter_title: document.getElementById('letter_title').value.trim(),
                letter_content: document.getElementById('letter_content').value.trim(),
                letter_signature: document.getElementById('letter_signature').value.trim(),
                surprise_title: document.getElementById('surprise_title').value.trim(),
                surprise_message: document.getElementById('surprise_message').value.trim(),
                status: status,
                memories: memories,
                timeline: timeline
            };
        }

        function submitGift(status) {
            if (!validateStep(1)) {
                updateStep(1);
                return;
            }

            const payload = buildPayload(status);
            showToast(status === 'published' ? 'Publishing your romantic gift...' : 'Saving draft...');

            fetch('api/save_gift.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.payment_alert) {
                        alert(data.payment_alert.message);
                        window.location.href = data.payment_alert.checkout_url;
                        return;
                    }
                    const modal = document.getElementById('publish-success-modal');
                    document.getElementById('final-gift-url').value = data.url;
                    document.getElementById('btn-view-gift').href = data.url;
                    document.getElementById('btn-open-editor').href = data.editor_url;
                    modal.classList.add('active');
                } else {
                    alert('Error: ' + (data.error || 'Could not save gift.'));
                }
            })
            .catch(err => {
                alert('Connection error: ' + err.message);
            });
        }

        document.getElementById('btn-publish-gift').addEventListener('click', () => submitGift('published'));
        document.getElementById('btn-save-draft').addEventListener('click', () => submitGift('draft'));

        // Copy URL button
        document.getElementById('btn-copy-published-url').addEventListener('click', () => {
            const input = document.getElementById('final-gift-url');
            navigator.clipboard.writeText(input.value).then(() => {
                showToast('URL copied to clipboard!');
            }).catch(() => {
                input.select();
                document.execCommand('copy');
                showToast('URL copied!');
            });
        });

        function showToast(text) {
            const toast = document.getElementById('toast-msg');
            if (!toast) return;
            document.getElementById('toast-text').innerText = text;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3500);
        }

        // Initialize default 5 memory cards
        renderMemories();
    </script>
</body>
</html>
