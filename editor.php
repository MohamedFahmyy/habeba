<?php
/**
 * Visual Romantic Gift Website Editor
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/I18n.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/GiftService.php';
require_once __DIR__ . '/core/ThemeRegistry.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = trim($_GET['token'] ?? '');
$userId = Auth::userId();

$gift = null;
if ($id > 0) {
    $gift = GiftService::getGiftById($id, $userId, $token);
}

// Strict tenant isolation: do not fallback to arbitrary customer gifts
if (!$gift) {
    header('Location: ' . (Auth::check() ? 'dashboard.php' : 'login.php'));
    exit;
}

$allThemes = ThemeRegistry::getAll();
?>
<!DOCTYPE html>
<html lang="<?php echo I18n::getLang(); ?>" dir="<?php echo I18n::getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visual Editor - <?php echo htmlspecialchars($gift['title']); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Platform Stylesheet -->
    <link rel="stylesheet" href="css/platform.css">

    <style>
        body {
            background-color: #1a162b;
            color: #f1f0f5;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        /* Top Header */
        .editor-header {
            height: 65px;
            background: #110e20;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 100;
        }

        .editor-viewport-toggles {
            display: flex;
            background: rgba(255,255,255,0.08);
            border-radius: 30px;
            padding: 4px;
            gap: 4px;
        }

        .viewport-btn {
            background: none;
            border: none;
            color: #bbb;
            padding: 6px 14px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: var(--transition-smooth);
        }

        .viewport-btn.active, .viewport-btn:hover {
            background: var(--theme-primary);
            color: #FFF;
        }

        /* Main Workspace Split */
        .editor-workspace {
            display: flex;
            flex: 1;
            height: calc(100vh - 65px);
            overflow: hidden;
        }

        /* Left Side: Properties Panel */
        .editor-sidebar {
            width: 440px;
            background: #161228;
            border-right: 1px solid rgba(255,255,255,0.1);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .sidebar-tabs {
            display: flex;
            background: #0f0c1c;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            overflow-x: auto;
        }

        .tab-btn {
            flex: 1;
            padding: 14px 10px;
            background: none;
            border: none;
            color: #8f8ba3;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
            transition: var(--transition-smooth);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .tab-btn i { font-size: 1.1rem; }

        .tab-btn.active {
            color: #FFF;
            border-bottom-color: var(--theme-primary);
            background: rgba(255,255,255,0.03);
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* Right Side: Live Canvas Preview */
        .editor-canvas-container {
            flex: 1;
            background: #0a0815;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }

        .preview-iframe-wrapper {
            background: #FFF;
            border-radius: 12px;
            box-shadow: 0 15px 45px rgba(0,0,0,0.7);
            height: 100%;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
            transition: width 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
            position: relative;
        }

        .preview-iframe-wrapper.tablet {
            max-width: 768px;
        }

        .preview-iframe-wrapper.mobile {
            max-width: 390px;
            border-radius: 36px;
            border: 8px solid #2d264a;
        }

        #preview-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Editor Form Controls */
        .editor-group {
            margin-bottom: 20px;
        }

        .editor-label {
            display: block;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #b0a8c2;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .editor-input, .editor-textarea, .editor-select {
            width: 100%;
            background: #211c38;
            border: 1px solid rgba(255,255,255,0.12);
            color: #FFF;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            transition: var(--transition-smooth);
        }

        .editor-input:focus, .editor-textarea:focus, .editor-select:focus {
            border-color: var(--theme-primary);
            box-shadow: 0 0 0 2px rgba(217, 138, 155, 0.25);
        }

        .mem-item-row {
            background: #211c38;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 15px;
        }

        .switch-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .switch-toggle input {
            cursor: pointer;
            width: 18px;
            height: 18px;
            accent-color: var(--theme-primary);
        }
    </style>
</head>
<body data-theme="<?php echo htmlspecialchars($gift['theme_id']); ?>">

    <!-- Top Editor Header -->
    <header class="editor-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="index.php" style="color: #FFF; text-decoration: none; font-size: 1.1rem;" title="Home">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <strong style="font-size: 1rem;"><?php echo htmlspecialchars($gift['title']); ?></strong>
                <div style="font-size: 0.75rem; color: #8f8ba3;">
                    <span id="save-status-text"><i class="fas fa-check-circle" style="color: #4CAF50;"></i> All changes saved</span>
                </div>
            </div>
        </div>

        <!-- Viewport Mode Buttons -->
        <div class="editor-viewport-toggles">
            <button class="viewport-btn active" data-viewport="desktop" title="Desktop View">
                <i class="fas fa-desktop"></i>
            </button>
            <button class="viewport-btn" data-viewport="tablet" title="Tablet View">
                <i class="fas fa-tablet-alt"></i>
            </button>
            <button class="viewport-btn" data-viewport="mobile" title="Mobile View">
                <i class="fas fa-mobile-alt"></i>
            </button>
        </div>

        <!-- Top Right Actions -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="gift.php?slug=<?php echo urlencode($gift['slug']); ?>&token=<?php echo htmlspecialchars($gift['edit_token']); ?>" target="_blank" class="btn-romantic-outline btn-romantic-sm" style="color: #FFF !important; border-color: rgba(255,255,255,0.3);">
                <i class="fas fa-external-link-alt"></i> Preview as Recipient
            </a>
            <button id="btn-save-editor" class="btn-romantic btn-romantic-sm">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </header>

    <!-- Split Editor Workspace -->
    <div class="editor-workspace">
        
        <!-- Left Sidebar: Property Panels -->
        <aside class="editor-sidebar">
            <div class="sidebar-tabs">
                <button class="tab-btn active" data-tab="tab-content">
                    <i class="fas fa-heart"></i>
                    <span>Story</span>
                </button>
                <button class="tab-btn" data-tab="tab-theme">
                    <i class="fas fa-palette"></i>
                    <span>Theme</span>
                </button>
                <button class="tab-btn" data-tab="tab-music">
                    <i class="fas fa-music"></i>
                    <span>Music</span>
                </button>
                <button class="tab-btn" data-tab="tab-memories">
                    <i class="fas fa-images"></i>
                    <span>Memories</span>
                </button>
                <button class="tab-btn" data-tab="tab-sections">
                    <i class="fas fa-sliders-h"></i>
                    <span>Sections</span>
                </button>
            </div>

            <div class="sidebar-content">
                
                <!-- TAB 1: Story & Basic Content -->
                <div class="tab-pane active" id="tab-content">
                    <h3 style="font-size: 1.1rem; margin-bottom: 18px; color: #FFF;">Hero & Story</h3>
                    
                    <div class="editor-group">
                        <label class="editor-label">Sender Name</label>
                        <input type="text" id="ed_sender_name" class="editor-input" value="<?php echo htmlspecialchars($gift['sender_name']); ?>">
                    </div>

                    <div class="editor-group">
                        <label class="editor-label">Recipient Name</label>
                        <input type="text" id="ed_recipient_name" class="editor-input" value="<?php echo htmlspecialchars($gift['recipient_name']); ?>">
                    </div>

                    <div class="editor-group">
                        <label class="editor-label">Gift Title</label>
                        <input type="text" id="ed_title" class="editor-input" value="<?php echo htmlspecialchars($gift['title']); ?>">
                    </div>

                    <div class="editor-group">
                        <label class="editor-label">Welcome Message / Quote</label>
                        <textarea id="ed_welcome_message" rows="3" class="editor-textarea"><?php echo htmlspecialchars($gift['welcome_message']); ?></textarea>
                    </div>

                    <div class="editor-group">
                        <label class="editor-label">Love Letter Heading</label>
                        <input type="text" id="ed_letter_title" class="editor-input" value="<?php echo htmlspecialchars($gift['letter_title']); ?>">
                    </div>

                    <div class="editor-group">
                        <label class="editor-label">Love Letter Message</label>
                        <textarea id="ed_letter_content" rows="6" class="editor-textarea"><?php echo htmlspecialchars($gift['letter_content']); ?></textarea>
                    </div>

                    <div class="editor-group">
                        <label class="editor-label">Letter Signature</label>
                        <input type="text" id="ed_letter_signature" class="editor-input" value="<?php echo htmlspecialchars($gift['letter_signature']); ?>">
                    </div>

                    <div class="editor-group">
                        <label class="editor-label">Final Surprise Title</label>
                        <input type="text" id="ed_surprise_title" class="editor-input" value="<?php echo htmlspecialchars($gift['surprise_title']); ?>">
                    </div>

                    <div class="editor-group">
                        <label class="editor-label">Final Surprise Note</label>
                        <textarea id="ed_surprise_message" rows="3" class="editor-textarea"><?php echo htmlspecialchars($gift['surprise_message']); ?></textarea>
                    </div>
                </div>

                <!-- TAB 2: Theme & Appearance -->
                <div class="tab-pane" id="tab-theme">
                    <h3 style="font-size: 1.1rem; margin-bottom: 18px; color: #FFF;">Theme & Styling</h3>

                    <div class="editor-group">
                        <label class="editor-label">Visual Theme</label>
                        <select id="ed_theme_id" class="editor-select">
                            <?php foreach ($allThemes as $tId => $t): ?>
                            <option value="<?php echo $tId; ?>" <?php if ($gift['theme_id'] === $tId) echo 'selected'; ?>>
                                <?php echo htmlspecialchars(I18n::isRtl() ? $t['name_ar'] : $t['name_en']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="editor-group">
                        <label class="editor-label">Gallery Presentation Style</label>
                        <select id="ed_gallery_style" class="editor-select">
                            <option value="polaroid" <?php if ($gift['gallery_style'] === 'polaroid') echo 'selected'; ?>>Polaroid Scrapbook Cards</option>
                            <option value="masonry" <?php if ($gift['gallery_style'] === 'masonry') echo 'selected'; ?>>Modern Editorial Masonry Grid</option>
                        </select>
                    </div>
                </div>

                <!-- TAB 3: Music (YouTube) -->
                <div class="tab-pane" id="tab-music">
                    <h3 style="font-size: 1.1rem; margin-bottom: 18px; color: #FFF;">Background Music</h3>

                    <div class="editor-group">
                        <label class="editor-label">YouTube URL</label>
                        <input type="text" id="ed_music_url" class="editor-input" value="<?php echo htmlspecialchars($gift['music_url']); ?>" placeholder="https://www.youtube.com/watch?v=...">
                    </div>

                    <div class="editor-group">
                        <label class="editor-label">Song Dedication Title</label>
                        <input type="text" id="ed_music_title" class="editor-input" value="<?php echo htmlspecialchars($gift['music_title'] ?: 'Our Song'); ?>">
                    </div>

                    <button type="button" id="ed_test_music_btn" class="btn-romantic-outline btn-romantic-sm" style="width: 100%;">
                        <i class="fas fa-sync"></i> Refresh Song Player
                    </button>
                </div>

                <!-- TAB 4: Memories -->
                <div class="tab-pane" id="tab-memories">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                        <h3 style="font-size: 1.1rem; color: #FFF;">Photo Memories</h3>
                        <button type="button" id="btn-editor-add-mem" class="btn-romantic btn-romantic-sm" style="padding: 4px 10px; font-size: 0.8rem;">
                            <i class="fas fa-plus"></i> Add Photo
                        </button>
                    </div>

                    <div id="editor-memories-list">
                        <?php foreach ($gift['memories'] as $idx => $m): ?>
                        <div class="mem-item-row" data-id="<?php echo $m['id']; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <strong style="font-size: 0.85rem;"><i class="fas fa-camera"></i> Memory #<?php echo $idx + 1; ?></strong>
                                <button type="button" class="btn-del-mem" style="background:none; border:none; color: #ff5252; cursor: pointer; font-size: 0.85rem;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                                <img src="<?php echo htmlspecialchars($m['image_url']); ?>" class="mem-thumb" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                <div style="flex: 1;">
                                    <input type="file" accept="image/*" class="ed-file-upload" style="display: none;">
                                    <button type="button" class="btn-replace-img btn-romantic-outline btn-romantic-sm" style="padding: 4px 8px; font-size: 0.75rem;">Replace Image</button>
                                    <input type="hidden" class="ed-mem-img" value="<?php echo htmlspecialchars($m['image_url']); ?>">
                                </div>
                            </div>
                            <input type="text" class="editor-input ed-mem-title" placeholder="Title" value="<?php echo htmlspecialchars($m['title']); ?>" style="margin-bottom: 6px; font-size: 0.85rem; padding: 6px 10px;">
                            <input type="text" class="editor-input ed-mem-date" placeholder="Date" value="<?php echo htmlspecialchars($m['memory_date']); ?>" style="margin-bottom: 6px; font-size: 0.85rem; padding: 6px 10px;">
                            <textarea class="editor-textarea ed-mem-cap" rows="2" placeholder="Caption" style="font-size: 0.85rem; padding: 6px 10px;"><?php echo htmlspecialchars($m['caption']); ?></textarea>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- TAB 5: Section Visibility Toggles -->
                <div class="tab-pane" id="tab-sections">
                    <h3 style="font-size: 1.1rem; margin-bottom: 18px; color: #FFF;">Sections & Privacy</h3>

                    <div class="switch-toggle">
                        <span>Show Memories Gallery</span>
                        <input type="checkbox" id="ed_show_gallery" <?php if (!empty($gift['show_gallery'])) echo 'checked'; ?>>
                    </div>
                    <div class="switch-toggle">
                        <span>Show Our Story Timeline</span>
                        <input type="checkbox" id="ed_show_timeline" <?php if (!empty($gift['show_timeline'])) echo 'checked'; ?>>
                    </div>
                    <div class="switch-toggle">
                        <span>Show Love Letter</span>
                        <input type="checkbox" id="ed_show_letter" <?php if (!empty($gift['show_letter'])) echo 'checked'; ?>>
                    </div>
                    <div class="switch-toggle">
                        <span>Show Relationship Counters</span>
                        <input type="checkbox" id="ed_show_counters" <?php if (!empty($gift['show_counters'])) echo 'checked'; ?>>
                    </div>
                    <div class="switch-toggle">
                        <span>Show Final Surprise</span>
                        <input type="checkbox" id="ed_show_surprise" <?php if (!empty($gift['show_surprise'])) echo 'checked'; ?>>
                    </div>

                    <div class="editor-group" style="margin-top: 25px;">
                        <label class="editor-label">Publication Status</label>
                        <select id="ed_status" class="editor-select">
                            <option value="published" <?php if ($gift['status'] === 'published') echo 'selected'; ?>>Published (Live via Public Link)</option>
                            <option value="draft" <?php if ($gift['status'] === 'draft') echo 'selected'; ?>>Draft (Private only to you)</option>
                        </select>
                    </div>
                </div>

            </div>
        </aside>

        <!-- Right Canvas: Interactive Live Preview Frame -->
        <main class="editor-canvas-container">
            <div class="preview-iframe-wrapper" id="preview-wrapper">
                <iframe id="preview-iframe" src="gift.php?slug=<?php echo urlencode($gift['slug']); ?>&token=<?php echo htmlspecialchars($gift['edit_token']); ?>"></iframe>
            </div>
        </main>
    </div>

    <!-- Toast Notification -->
    <div class="romantic-toast" id="toast-msg">
        <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
        <span id="toast-text">Success</span>
    </div>

    <script>
        const giftId = <?php echo (int)$gift['id']; ?>;
        const editToken = <?php echo json_encode($gift['edit_token']); ?>;
        const slug = <?php echo json_encode($gift['slug']); ?>;

        // Viewport Switcher
        document.querySelectorAll('.viewport-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.viewport-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const vp = btn.getAttribute('data-viewport');
                const wrapper = document.getElementById('preview-wrapper');
                wrapper.className = 'preview-iframe-wrapper ' + (vp === 'desktop' ? '' : vp);
            });
        });

        // Tabs Switcher
        document.querySelectorAll('.tab-btn').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                const paneId = tab.getAttribute('data-tab');
                document.getElementById(paneId).classList.add('active');
            });
        });

        // Setup File Upload in Memories List
        function bindMemoryRow(row) {
            const btnReplace = row.querySelector('.btn-replace-img');
            const fileInput = row.querySelector('.ed-file-upload');
            const thumb = row.querySelector('.mem-thumb');
            const imgInput = row.querySelector('.ed-mem-img');
            const delBtn = row.querySelector('.btn-del-mem');

            if (btnReplace && fileInput) {
                btnReplace.addEventListener('click', () => fileInput.click());
                fileInput.addEventListener('change', () => {
                    if (fileInput.files && fileInput.files[0]) {
                        const fd = new FormData();
                        fd.append('image', fileInput.files[0]);
                        showToast('Uploading image...');
                        fetch('api/upload.php', { method: 'POST', body: fd })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    imgInput.value = data.url;
                                    thumb.src = data.url;
                                    showToast('Image updated! Click Save to apply.');
                                } else {
                                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                                }
                            });
                    }
                });
            }

            if (delBtn) {
                delBtn.addEventListener('click', () => row.remove());
            }
        }

        document.querySelectorAll('.mem-item-row').forEach(bindMemoryRow);

        // Add Memory in Editor
        document.getElementById('btn-editor-add-mem').addEventListener('click', () => {
            const container = document.getElementById('editor-memories-list');
            const count = container.querySelectorAll('.mem-item-row').length + 1;
            const row = document.createElement('div');
            row.className = 'mem-item-row';
            row.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <strong style="font-size: 0.85rem;"><i class="fas fa-camera"></i> Memory #${count}</strong>
                    <button type="button" class="btn-del-mem" style="background:none; border:none; color: #ff5252; cursor: pointer; font-size: 0.85rem;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                    <img src="assets/images/1.jpg" class="mem-thumb" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                    <div style="flex: 1;">
                        <input type="file" accept="image/*" class="ed-file-upload" style="display: none;">
                        <button type="button" class="btn-replace-img btn-romantic-outline btn-romantic-sm" style="padding: 4px 8px; font-size: 0.75rem;">Choose Image</button>
                        <input type="hidden" class="ed-mem-img" value="assets/images/1.jpg">
                    </div>
                </div>
                <input type="text" class="editor-input ed-mem-title" placeholder="Title" value="Our Memory #${count}" style="margin-bottom: 6px; font-size: 0.85rem; padding: 6px 10px;">
                <input type="text" class="editor-input ed-mem-date" placeholder="Date" value="" style="margin-bottom: 6px; font-size: 0.85rem; padding: 6px 10px;">
                <textarea class="editor-textarea ed-mem-cap" rows="2" placeholder="Caption" style="font-size: 0.85rem; padding: 6px 10px;"></textarea>
            `;
            container.appendChild(row);
            bindMemoryRow(row);
        });

        // Save Editor Changes
        document.getElementById('btn-save-editor').addEventListener('click', saveChanges);

        function saveChanges() {
            const statusEl = document.getElementById('save-status-text');
            statusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving changes...';

            const memories = [];
            document.querySelectorAll('.mem-item-row').forEach(row => {
                const img = row.querySelector('.ed-mem-img').value;
                const title = row.querySelector('.ed-mem-title').value;
                const date = row.querySelector('.ed-mem-date').value;
                const cap = row.querySelector('.ed-mem-cap').value;
                memories.push({ image_url: img, title: title, memory_date: date, caption: cap });
            });

            const payload = {
                id: giftId,
                edit_token: editToken,
                sender_name: document.getElementById('ed_sender_name').value.trim(),
                recipient_name: document.getElementById('ed_recipient_name').value.trim(),
                title: document.getElementById('ed_title').value.trim(),
                welcome_message: document.getElementById('ed_welcome_message').value.trim(),
                theme_id: document.getElementById('ed_theme_id').value,
                gallery_style: document.getElementById('ed_gallery_style').value,
                music_url: document.getElementById('ed_music_url').value.trim(),
                music_title: document.getElementById('ed_music_title').value.trim(),
                letter_title: document.getElementById('ed_letter_title').value.trim(),
                letter_content: document.getElementById('ed_letter_content').value.trim(),
                letter_signature: document.getElementById('ed_letter_signature').value.trim(),
                surprise_title: document.getElementById('ed_surprise_title').value.trim(),
                surprise_message: document.getElementById('ed_surprise_message').value.trim(),
                show_gallery: document.getElementById('ed_show_gallery').checked ? 1 : 0,
                show_timeline: document.getElementById('ed_show_timeline').checked ? 1 : 0,
                show_letter: document.getElementById('ed_show_letter').checked ? 1 : 0,
                show_counters: document.getElementById('ed_show_counters').checked ? 1 : 0,
                show_surprise: document.getElementById('ed_show_surprise').checked ? 1 : 0,
                status: document.getElementById('ed_status').value,
                memories: memories
            };

            fetch('api/save_gift.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    statusEl.innerHTML = '<i class="fas fa-check-circle" style="color: #4CAF50;"></i> All changes saved';
                    showToast('Saved successfully! Refreshing preview...');
                    // Reload preview iframe
                    const iframe = document.getElementById('preview-iframe');
                    iframe.src = iframe.src;
                } else {
                    statusEl.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #ff5252;"></i> Save error';
                    alert('Error saving: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                statusEl.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #ff5252;"></i> Connection error';
                alert('Network error: ' + err.message);
            });
        }

        // Refresh song player
        document.getElementById('ed_test_music_btn').addEventListener('click', saveChanges);

        function showToast(text) {
            const toast = document.getElementById('toast-msg');
            if (!toast) return;
            document.getElementById('toast-text').innerText = text;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3500);
        }
    </script>
</body>
</html>
