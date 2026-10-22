<?php
/**
 * GiftService - Logic for managing Romantic Gift Websites & Tenant Isolation
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PublicationPolicy.php';
require_once __DIR__ . '/PaymentService.php';

class GiftService {
    
    /**
     * Parse YouTube video ID from various URL formats
     */
    public static function extractYouTubeId(?string $url): ?string {
        if (empty($url)) {
            return null;
        }
        $url = trim($url);

        // Direct 11-character ID
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }

        // Standard, share, embed, shorts URLs
        $patterns = [
            '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/)([^"&?\/ ]{11})/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Generate a unique slug for a gift
     */
    public static function generateUniqueSlug(string $title, ?string $sender = null, ?string $recipient = null, ?int $excludeId = null): string {
        $db = Database::getConnection();
        
        $base = '';
        if (!empty($sender) && !empty($recipient)) {
            $base = $sender . '-and-' . $recipient;
        } else {
            $base = $title;
        }

        // Transliterate / clean
        $slug = mb_strtolower(trim($base), 'UTF-8');
        // Replace non-alphanumeric (allowing Arabic letters and standard letters/numbers)
        $slug = preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $slug);
        $slug = trim($slug, '-');

        if (empty($slug)) {
            $slug = 'our-love-story-' . substr(bin2hex(random_bytes(3)), 0, 6);
        }

        $checkSlug = $slug;
        $counter = 1;

        while (true) {
            $sql = "SELECT id FROM gift_websites WHERE slug = ?";
            $params = [$checkSlug];
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if (!$stmt->fetch()) {
                return $checkSlug;
            }
            $checkSlug = $slug . '-' . $counter;
            $counter++;
        }
    }

    /**
     * Strict Authorization & Ownership Check
     * Accepts either full gift array or integer ID
     */
    public static function canAccessGift($gift, ?int $userId = null, ?string $token = null): bool {
        if (is_int($gift) || is_numeric($gift)) {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM gift_websites WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$gift]);
            $gift = $stmt->fetch();
            if (!$gift) {
                return false;
            }
        }

        if (!is_array($gift)) {
            return false;
        }

        // Admin always has access
        if (!empty($_SESSION['admin_logged_in']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')) {
            return true;
        }

        // Authenticated customer check: if user_id is set on the gift, only that user or admin has access
        if (!empty($gift['user_id'])) {
            return ($userId !== null && (int)$gift['user_id'] === $userId);
        }

        // If the requesting user is logged in, but the gift has no user_id, they can claim/access if they hold the session token
        if ($userId !== null && empty($gift['user_id'])) {
            if (!empty($token) && !empty($gift['edit_token']) && hash_equals($gift['edit_token'], $token)) {
                return true;
            }
            if (!empty($gift['id']) && !empty($_SESSION['editable_gifts'][$gift['id']]) && !empty($gift['edit_token']) && hash_equals($gift['edit_token'], $_SESSION['editable_gifts'][$gift['id']])) {
                return true;
            }
        }

        // If an explicit token was passed, validate it strictly
        if (!empty($token)) {
            return !empty($gift['edit_token']) && hash_equals($gift['edit_token'], $token);
        }

        // Session-authorized creator for unassigned gifts (no token passed)
        if (!empty($gift['id']) && !empty($_SESSION['editable_gifts'][$gift['id']]) && !empty($gift['edit_token']) && hash_equals($gift['edit_token'], $_SESSION['editable_gifts'][$gift['id']])) {
            return true;
        }

        return false;
    }

    /**
     * Create a new gift website
     */
    public static function createGift(array $data, ?int $userId = null): array {
        $db = Database::getConnection();

        $sender = trim($data['sender_name'] ?? 'Me');
        $recipient = trim($data['recipient_name'] ?? 'My Love');
        $title = trim($data['title'] ?? ($sender . ' & ' . $recipient . ' - Our Love Story'));
        
        $customSlug = trim($data['slug'] ?? '');
        if (!empty($customSlug)) {
            $slug = self::generateUniqueSlug($customSlug, null, null);
        } else {
            $slug = self::generateUniqueSlug($title, $sender, $recipient);
        }

        $editToken = bin2hex(random_bytes(24));
        $musicUrl = trim($data['music_url'] ?? '');
        $musicVideoId = self::extractYouTubeId($musicUrl);
        $musicTitle = trim($data['music_title'] ?? 'Our Song');

        $themeId = $data['theme_id'] ?? 'luxury-cream';
        $galleryStyle = $data['gallery_style'] ?? 'polaroid';
        $welcomeMessage = $data['welcome_message'] ?? 'Every moment with you is a gift.';
        
        $letterTitle = $data['letter_title'] ?? 'A Letter From My Heart';
        $letterContent = $data['letter_content'] ?? "To the one who holds my heart,\n\nThank you for filling my world with light and warmth.\nForever grateful for you.";
        $letterSignature = $data['letter_signature'] ?? ('With all my love, ' . $sender);

        $surpriseTitle = $data['surprise_title'] ?? 'Forever Starts With You ❤️';
        $surpriseMessage = $data['surprise_message'] ?? 'Every single day with you is my greatest adventure.';
        $anniversaryDate = $data['anniversary_date'] ?? date('Y-m-d');
        
        $requestedStatus = 'published';
        if (isset($data['status'])) {
            $requestedStatus = in_array($data['status'], ['draft', 'published']) ? $data['status'] : 'published';
        } elseif (isset($data['is_published']) && !$data['is_published']) {
            $requestedStatus = 'draft';
        }
        $initialStatus = 'draft';

        $stmt = $db->prepare("INSERT INTO gift_websites (
            user_id, slug, edit_token, title, sender_name, recipient_name, welcome_message,
            theme_id, gallery_style, music_url, music_video_id, music_title,
            letter_title, letter_content, letter_signature,
            surprise_title, surprise_message, anniversary_date,
            show_timeline, show_gallery, show_letter, show_counters, show_surprise,
            status, is_demo, payment_status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, 0, 'free'
        )");

        $stmt->execute([
            $userId, $slug, $editToken, $title, $sender, $recipient, $welcomeMessage,
            $themeId, $galleryStyle, $musicUrl, $musicVideoId, $musicTitle,
            $letterTitle, $letterContent, $letterSignature,
            $surpriseTitle, $surpriseMessage, $anniversaryDate,
            isset($data['show_timeline']) ? (int)$data['show_timeline'] : 1,
            isset($data['show_gallery']) ? (int)$data['show_gallery'] : 1,
            isset($data['show_letter']) ? (int)$data['show_letter'] : 1,
            isset($data['show_counters']) ? (int)$data['show_counters'] : 1,
            isset($data['show_surprise']) ? (int)$data['show_surprise'] : 1,
            $initialStatus
        ]);

        $giftId = (int)$db->lastInsertId();

        // Save Memories
        if (!empty($data['memories']) && is_array($data['memories'])) {
            self::saveMemories($giftId, $data['memories']);
        }

        // Save Timeline Entries
        if (!empty($data['timeline']) && is_array($data['timeline'])) {
            self::saveTimelineEntries($giftId, $data['timeline']);
        }

        // Save Counters
        if (!empty($data['counters']) && is_array($data['counters'])) {
            self::saveCounters($giftId, $data['counters']);
        } else {
            self::saveCounters($giftId, [
                ['label' => 'Days of Love', 'target_value' => 365, 'icon' => 'fa-heart'],
                ['label' => 'Shared Smiles', 'target_value' => 1000, 'icon' => 'fa-smile-beam'],
                ['label' => 'Photos Taken', 'target_value' => 50, 'icon' => 'fa-camera-retro'],
                ['label' => 'Sweet Memories', 'target_value' => 999, 'icon' => 'fa-infinity']
            ]);
        }

        // Store editToken in creator's session
        $_SESSION['editable_gifts'][$giftId] = $editToken;

        // Publication Check
        $finalStatus = 'draft';
        $paymentAlert = null;
        if ($requestedStatus === 'published') {
            $pubCheck = PublicationPolicy::canPublish($giftId, $userId);
            if ($pubCheck['allowed']) {
                $db->prepare("UPDATE gift_websites SET status = 'published' WHERE id = ?")->execute([$giftId]);
                $finalStatus = 'published';
            } else {
                $paymentAlert = $pubCheck;
            }
        }

        return [
            'success' => true,
            'id' => $giftId,
            'slug' => $slug,
            'edit_token' => $editToken,
            'url' => BASE_URL . '/gift.php?slug=' . urlencode($slug),
            'editor_url' => BASE_URL . '/editor.php?id=' . $giftId . '&token=' . $editToken,
            'status' => $finalStatus,
            'payment_alert' => $paymentAlert
        ];
    }

    /**
     * Update an existing gift website with strict ownership check
     */
    public static function updateGift(int $id, ?int $userId, ?string $editToken, array $data): array {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM gift_websites WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            return ['success' => false, 'error' => 'Gift not found.'];
        }

        // Strict authorization check
        if (!self::canAccessGift($existing, $userId, $editToken)) {
            return ['success' => false, 'error' => 'Unauthorized: You do not own this gift.'];
        }

        $fields = [];
        $params = [];

        if (isset($data['title'])) { $fields[] = 'title = ?'; $params[] = trim($data['title']); }
        if (isset($data['sender_name'])) { $fields[] = 'sender_name = ?'; $params[] = trim($data['sender_name']); }
        if (isset($data['recipient_name'])) { $fields[] = 'recipient_name = ?'; $params[] = trim($data['recipient_name']); }
        if (isset($data['welcome_message'])) { $fields[] = 'welcome_message = ?'; $params[] = trim($data['welcome_message']); }
        if (isset($data['theme_id'])) { $fields[] = 'theme_id = ?'; $params[] = trim($data['theme_id']); }
        if (isset($data['gallery_style'])) { $fields[] = 'gallery_style = ?'; $params[] = trim($data['gallery_style']); }
        
        if (isset($data['music_url'])) { 
            $fields[] = 'music_url = ?'; 
            $params[] = trim($data['music_url']); 
            $fields[] = 'music_video_id = ?'; 
            $params[] = self::extractYouTubeId($data['music_url']);
        }
        if (isset($data['music_title'])) { $fields[] = 'music_title = ?'; $params[] = trim($data['music_title']); }
        
        if (isset($data['letter_title'])) { $fields[] = 'letter_title = ?'; $params[] = trim($data['letter_title']); }
        if (isset($data['letter_content'])) { $fields[] = 'letter_content = ?'; $params[] = trim($data['letter_content']); }
        if (isset($data['letter_signature'])) { $fields[] = 'letter_signature = ?'; $params[] = trim($data['letter_signature']); }

        if (isset($data['surprise_title'])) { $fields[] = 'surprise_title = ?'; $params[] = trim($data['surprise_title']); }
        if (isset($data['surprise_message'])) { $fields[] = 'surprise_message = ?'; $params[] = trim($data['surprise_message']); }
        if (isset($data['anniversary_date'])) { $fields[] = 'anniversary_date = ?'; $params[] = trim($data['anniversary_date']); }

        if (isset($data['show_timeline'])) { $fields[] = 'show_timeline = ?'; $params[] = (int)$data['show_timeline']; }
        if (isset($data['show_gallery'])) { $fields[] = 'show_gallery = ?'; $params[] = (int)$data['show_gallery']; }
        if (isset($data['show_letter'])) { $fields[] = 'show_letter = ?'; $params[] = (int)$data['show_letter']; }
        if (isset($data['show_counters'])) { $fields[] = 'show_counters = ?'; $params[] = (int)$data['show_counters']; }
        if (isset($data['show_surprise'])) { $fields[] = 'show_surprise = ?'; $params[] = (int)$data['show_surprise']; }
        
        // Handle publication status & payment enforcement
        $paymentAlert = null;
        if (isset($data['status']) && in_array($data['status'], ['draft', 'published'])) {
            if ($data['status'] === 'published') {
                $pubCheck = PublicationPolicy::canPublish($id, $userId ?: ($existing['user_id'] ? (int)$existing['user_id'] : null));
                if ($pubCheck['allowed']) {
                    $fields[] = 'status = ?';
                    $params[] = 'published';
                } else {
                    $fields[] = 'status = ?';
                    $params[] = 'draft'; // Prevent bypass
                    $paymentAlert = $pubCheck;
                }
            } else {
                $fields[] = 'status = ?';
                $params[] = 'draft';
            }
        }

        if (isset($data['slug']) && !empty($data['slug']) && $data['slug'] !== $existing['slug']) {
            $newSlug = self::generateUniqueSlug($data['slug'], null, null, $id);
            $fields[] = 'slug = ?';
            $params[] = $newSlug;
        }

        $fields[] = 'updated_at = CURRENT_TIMESTAMP';

        if (!empty($fields)) {
            $sql = "UPDATE gift_websites SET " . implode(', ', $fields) . " WHERE id = ?";
            $params[] = $id;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }

        // Update Memories
        if (isset($data['memories']) && is_array($data['memories'])) {
            self::saveMemories($id, $data['memories']);
        }

        // Update Timeline
        if (isset($data['timeline']) && is_array($data['timeline'])) {
            self::saveTimelineEntries($id, $data['timeline']);
        }

        // Update Counters
        if (isset($data['counters']) && is_array($data['counters'])) {
            self::saveCounters($id, $data['counters']);
        }

        return [
            'success' => true,
            'payment_alert' => $paymentAlert
        ];
    }

    /**
     * Duplicate a gift website for the owner
     */
    public static function duplicateGift(int $id, int $userId): ?array {
        $source = self::getGiftById($id, $userId);
        if (!$source) {
            return null;
        }

        $copyData = $source;
        unset($copyData['id'], $copyData['edit_token']);
        $copyData['title'] = $source['title'] . ' (نسخة)';
        $copyData['slug'] = $source['slug'] . '-copy';
        $copyData['status'] = 'draft';

        return self::createGift($copyData, $userId);
    }

    /**
     * Publish a gift with strict ownership & publication policy check
     */
    public static function publishGift(int $id, ?int $userId = null, ?string $token = null): array {
        return self::updateGift($id, $userId, $token, ['status' => 'published']);
    }

    /**
     * Retrieve complete gift data by ID with strict tenant isolation
     */
    public static function getGiftById(int $id, ?int $userId = null, ?string $token = null): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM gift_websites WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $gift = $stmt->fetch();

        if (!$gift) {
            return null;
        }

        if (!self::canAccessGift($gift, $userId, $token)) {
            return null;
        }

        return self::attachRelations($gift);
    }

    /**
     * Retrieve complete gift data by slug (Public view)
     */
    public static function getGiftBySlug(string $slug, ?string $token = null, ?int $userId = null): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM gift_websites WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $gift = $stmt->fetch();

        if (!$gift) {
            return null;
        }

        // If draft, only allow owner or admin
        if ($gift['status'] === 'draft') {
            $isOwner = self::canAccessGift($gift, $userId, $token);
            if (!$isOwner) {
                return null;
            }
        }

        // Increment view count
        $db->prepare("UPDATE gift_websites SET views_count = views_count + 1 WHERE id = ?")->execute([$gift['id']]);

        return self::attachRelations($gift);
    }

    /**
     * List only gifts owned by the authenticated customer (Tenant isolation)
     */
    public static function listUserGifts(int $userId, int $page = 1, int $limit = 20): array {
        $db = Database::getConnection();
        $offset = max(0, ($page - 1) * $limit);

        $sql = "SELECT g.*, 
                (SELECT COUNT(*) FROM memories WHERE gift_id = g.id) AS memories_count,
                (SELECT COUNT(*) FROM timeline_entries WHERE gift_id = g.id) AS timeline_count
                FROM gift_websites g 
                WHERE g.user_id = ? AND (g.is_demo = 0 OR g.is_demo IS NULL)
                ORDER BY g.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count customer's gifts
     */
    public static function countUserGifts(int $userId): array {
        $db = Database::getConnection();
        $total = $db->prepare("SELECT COUNT(*) FROM gift_websites WHERE user_id = ? AND (is_demo = 0 OR is_demo IS NULL)");
        $total->execute([$userId]);
        $totalCount = (int)$total->fetchColumn();

        $pub = $db->prepare("SELECT COUNT(*) FROM gift_websites WHERE user_id = ? AND status = 'published' AND (is_demo = 0 OR is_demo IS NULL)");
        $pub->execute([$userId]);
        $publishedCount = (int)$pub->fetchColumn();

        $drafts = $db->prepare("SELECT COUNT(*) FROM gift_websites WHERE user_id = ? AND status = 'draft' AND (is_demo = 0 OR is_demo IS NULL)");
        $drafts->execute([$userId]);
        $draftCount = (int)$drafts->fetchColumn();

        $views = $db->prepare("SELECT SUM(views_count) FROM gift_websites WHERE user_id = ? AND (is_demo = 0 OR is_demo IS NULL)");
        $views->execute([$userId]);
        $totalViews = (int)$views->fetchColumn();

        return [
            'total' => $totalCount,
            'published' => $publishedCount,
            'drafts' => $draftCount,
            'views' => $totalViews
        ];
    }

    /**
     * List ONLY curated platform demonstration gifts (No customer data ever leaked)
     */
    public static function listShowcaseGifts(): array {
        $db = Database::getConnection();
        $sql = "SELECT g.*, 
                (SELECT COUNT(*) FROM memories WHERE gift_id = g.id) AS memories_count
                FROM gift_websites g 
                WHERE g.is_demo = 1 AND g.status = 'published'
                ORDER BY g.id ASC LIMIT 6";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * List all gifts (strictly for platform administration)
     */
    public static function listAllGiftsAdmin(int $limit = 100, ?string $search = null): array {
        $db = Database::getConnection();
        $sql = "SELECT g.*, u.username as owner_name, u.email as owner_email,
                (SELECT COUNT(*) FROM memories WHERE gift_id = g.id) AS memories_count
                FROM gift_websites g
                LEFT JOIN users u ON g.user_id = u.id ";
        
        $params = [];
        if (!empty($search)) {
            $sql .= "WHERE g.title LIKE ? OR g.sender_name LIKE ? OR g.recipient_name LIKE ? OR g.slug LIKE ? OR u.email LIKE ? ";
            $q = '%' . $search . '%';
            $params = [$q, $q, $q, $q, $q];
        }

        $sql .= "ORDER BY g.created_at DESC LIMIT " . (int)$limit;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete a gift with strict ownership check
     */
    public static function deleteGift(int $id, ?int $userId = null, ?string $editToken = null): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM gift_websites WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $gift = $stmt->fetch();

        if (!$gift) {
            return false;
        }

        if (!self::canAccessGift($gift, $userId, $editToken)) {
            return false;
        }

        $db->prepare("DELETE FROM memories WHERE gift_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM timeline_entries WHERE gift_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM counters WHERE gift_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM orders WHERE gift_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM payments WHERE gift_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM gift_websites WHERE id = ?")->execute([$id]);
        return true;
    }

    /**
     * Save/Replace Memories
     */
    public static function saveMemories(int $giftId, array $memories): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM memories WHERE gift_id = ?");
        $stmt->execute([$giftId]);

        $ins = $db->prepare("INSERT INTO memories (gift_id, image_url, title, caption, memory_date, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $order = 0;
        foreach ($memories as $m) {
            $imageUrl = trim($m['image_url'] ?? '');
            if (empty($imageUrl)) continue;
            $title = trim($m['title'] ?? '');
            $caption = trim($m['caption'] ?? '');
            $date = trim($m['memory_date'] ?? '');
            $ins->execute([$giftId, $imageUrl, $title, $caption, $date, $order++]);
        }
    }

    /**
     * Save/Replace Timeline Entries
     */
    public static function saveTimelineEntries(int $giftId, array $timeline): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM timeline_entries WHERE gift_id = ?");
        $stmt->execute([$giftId]);

        $ins = $db->prepare("INSERT INTO timeline_entries (gift_id, title, description, event_date, image_url, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $order = 0;
        foreach ($timeline as $t) {
            $title = trim($t['title'] ?? '');
            $desc = trim($t['description'] ?? '');
            $date = trim($t['event_date'] ?? '');
            $img = trim($t['image_url'] ?? '');
            if (empty($title) && empty($desc)) continue;
            $ins->execute([$giftId, $title, $desc, $date, $img, $order++]);
        }
    }

    /**
     * Save/Replace Counters
     */
    public static function saveCounters(int $giftId, array $counters): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM counters WHERE gift_id = ?");
        $stmt->execute([$giftId]);

        $ins = $db->prepare("INSERT INTO counters (gift_id, label, target_value, icon, sort_order) VALUES (?, ?, ?, ?, ?)");
        $order = 0;
        foreach ($counters as $c) {
            $label = trim($c['label'] ?? '');
            $val = (int)($c['target_value'] ?? 0);
            $icon = trim($c['icon'] ?? 'fa-heart');
            if (empty($label)) continue;
            $ins->execute([$giftId, $label, $val, $icon, $order++]);
        }
    }

    private static function attachRelations(array $gift): array {
        $db = Database::getConnection();
        $id = $gift['id'];

        $stmt = $db->prepare("SELECT * FROM memories WHERE gift_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$id]);
        $gift['memories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT * FROM timeline_entries WHERE gift_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$id]);
        $gift['timeline'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT * FROM counters WHERE gift_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$id]);
        $gift['counters'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $gift;
    }
}
