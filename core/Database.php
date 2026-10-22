<?php
/**
 * Database Connection & Migration Handler
 */

require_once __DIR__ . '/../config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::init();
        }
        return self::$instance;
    }

    private static function init(): void {
        try {
            if (DB_DRIVER === 'mysql') {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } else {
                // SQLite
                $dataDir = dirname(DB_SQLITE_PATH);
                if (!is_dir($dataDir)) {
                    mkdir($dataDir, 0755, true);
                }
                // Protect SQLite data folder from direct web access
                $htaccess = $dataDir . '/.htaccess';
                if (!file_exists($htaccess)) {
                    file_put_contents($htaccess, "Deny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>");
                }

                self::$instance = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$instance->exec('PRAGMA foreign_keys = ON;');
            }

            self::runMigrations();
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Database connection could not be established. Please check configuration.');
        }
    }

    private static function runMigrations(): void {
        $db = self::$instance;

        $isMysql = (DB_DRIVER === 'mysql');
        $pk = $isMysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $timestamp = $isMysql ? 'DATETIME DEFAULT CURRENT_TIMESTAMP' : 'DATETIME DEFAULT CURRENT_TIMESTAMP';

        // 1. Users Table
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id $pk,
            email VARCHAR(255) UNIQUE,
            username VARCHAR(100),
            password_hash VARCHAR(255),
            role VARCHAR(50) DEFAULT 'user',
            created_at $timestamp
        )");

        // 2. Gift Websites Table
        $db->exec("CREATE TABLE IF NOT EXISTS gift_websites (
            id $pk,
            user_id INT NULL,
            edit_token VARCHAR(64) NOT NULL,
            slug VARCHAR(120) UNIQUE NOT NULL,
            title VARCHAR(255) NOT NULL,
            sender_name VARCHAR(100) NOT NULL,
            recipient_name VARCHAR(100) NOT NULL,
            welcome_message TEXT,
            theme_id VARCHAR(50) DEFAULT 'midnight',
            theme_config TEXT,
            gallery_style VARCHAR(50) DEFAULT 'masonry',
            music_url VARCHAR(500),
            music_video_id VARCHAR(50),
            music_title VARCHAR(255),
            letter_title VARCHAR(255),
            letter_content TEXT,
            letter_signature VARCHAR(100),
            surprise_title VARCHAR(255),
            surprise_message TEXT,
            anniversary_date VARCHAR(50),
            show_timeline INT DEFAULT 1,
            show_gallery INT DEFAULT 1,
            show_letter INT DEFAULT 1,
            show_counters INT DEFAULT 1,
            show_surprise INT DEFAULT 1,
            status VARCHAR(30) DEFAULT 'published',
            views_count INT DEFAULT 0,
            created_at $timestamp,
            updated_at $timestamp
        )");

        // 3. Memories Table
        $db->exec("CREATE TABLE IF NOT EXISTS memories (
            id $pk,
            gift_id INT NOT NULL,
            image_url VARCHAR(500) NOT NULL,
            title VARCHAR(255),
            caption TEXT,
            memory_date VARCHAR(50),
            sort_order INT DEFAULT 0,
            created_at $timestamp
        )");

        // 4. Timeline Entries Table
        $db->exec("CREATE TABLE IF NOT EXISTS timeline_entries (
            id $pk,
            gift_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date VARCHAR(50),
            image_url VARCHAR(500),
            sort_order INT DEFAULT 0,
            created_at $timestamp
        )");

        // 5. Counters Table
        $db->exec("CREATE TABLE IF NOT EXISTS counters (
            id $pk,
            gift_id INT NOT NULL,
            label VARCHAR(100) NOT NULL,
            target_value INT NOT NULL DEFAULT 0,
            icon VARCHAR(50) DEFAULT 'fa-heart',
            sort_order INT DEFAULT 0
        )");

        // 6. Platform Settings Table
        $db->exec("CREATE TABLE IF NOT EXISTS platform_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT,
            updated_at $timestamp
        )");

        // 7. Orders Table
        $db->exec("CREATE TABLE IF NOT EXISTS orders (
            id $pk,
            user_id INT NOT NULL,
            gift_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'EGP',
            status VARCHAR(30) DEFAULT 'pending',
            created_at $timestamp,
            updated_at $timestamp
        )");

        // 8. Payments Table
        $db->exec("CREATE TABLE IF NOT EXISTS payments (
            id $pk,
            order_id INT NULL,
            gift_id INT NOT NULL,
            user_id INT NOT NULL,
            provider VARCHAR(50) NOT NULL,
            transaction_reference VARCHAR(255),
            provider_transaction_id VARCHAR(255),
            amount DECIMAL(10,2),
            currency VARCHAR(10) DEFAULT 'EGP',
            status VARCHAR(30) DEFAULT 'awaiting_review',
            receipt_path VARCHAR(500),
            reviewed_by INT NULL,
            reviewed_at DATETIME NULL,
            admin_notes TEXT,
            created_at $timestamp,
            updated_at $timestamp
        )");

        // Schema Alterations (backward-compatible column additions)
        self::ensureColumnExists('gift_websites', 'payment_status', "VARCHAR(30) DEFAULT 'free'");
        self::ensureColumnExists('gift_websites', 'is_demo', "INT DEFAULT 0");

        // Seed default platform settings
        self::seedDefaultSettings();

        // Seed default admin user
        self::seedDefaultAdmin();

        // Check if flagship seed exists
        self::seedDefaultGift();
    }

    private static function ensureColumnExists(string $table, string $column, string $definition): void {
        $db = self::$instance;
        try {
            if (DB_DRIVER === 'mysql') {
                $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
                $stmt->execute([$column]);
                if (!$stmt->fetch()) {
                    $db->exec("ALTER TABLE `$table` ADD `$column` $definition");
                }
            } else {
                // SQLite
                $cols = [];
                $res = $db->query("PRAGMA table_info($table)");
                while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                    $cols[] = $row['name'];
                }
                if (!in_array($column, $cols)) {
                    $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
                }
            }
        } catch (Exception $e) {
            error_log("Schema alteration note for $table.$column: " . $e->getMessage());
        }
    }

    private static function seedDefaultSettings(): void {
        $db = self::$instance;
        $defaults = [
            'payments_enabled' => '0', // Default testing mode: payments disabled, publication free
            'payment_price' => '149',
            'payment_currency' => 'EGP',
            'instapay_enabled' => '1',
            'instapay_address' => 'habeba@instapay',
            'instapay_instructions' => 'حول المبلغ عبر تطبيق انستاباي إلى العنوان أعلاه، ثم اكتب رقم العملية وارفع صورة الإيصال.',
            'vodafone_cash_enabled' => '1',
            'vodafone_cash_number' => '01000000000',
            'vodafone_cash_instructions' => 'حول المبلغ كاش إلى الرقم أعلاه، ثم اكتب رقم المحفظة المحول منها وارفع صورة الإيصال.',
            'card_enabled' => '0',
            'require_manual_review' => '1'
        ];

        $stmt = $db->prepare("INSERT OR IGNORE INTO platform_settings (setting_key, setting_value) VALUES (?, ?)");
        if (DB_DRIVER === 'mysql') {
            $stmt = $db->prepare("INSERT IGNORE INTO platform_settings (setting_key, setting_value) VALUES (?, ?)");
        }

        foreach ($defaults as $k => $v) {
            $stmt->execute([$k, $v]);
        }
    }

    private static function seedDefaultAdmin(): void {
        $db = self::$instance;
        $adminEmail = 'admin@habeba.com';
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$adminEmail]);
        if (!$stmt->fetch()) {
            $hash = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (email, username, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$adminEmail, 'Habeba Admin', $hash]);
        }
    }

    private static function seedDefaultGift(): void {
        $db = self::$instance;
        $stmt = $db->prepare("SELECT id FROM gift_websites WHERE slug = 'salma-and-mohamed' LIMIT 1");
        $stmt->execute();
        $exists = $stmt->fetch();

        if (!$exists) {
            // Seed the flagship Mohamed & Salma memory website
            $stmt = $db->prepare("INSERT INTO gift_websites (
                slug, edit_token, title, sender_name, recipient_name, welcome_message,
                theme_id, gallery_style, music_url, music_video_id, music_title,
                letter_title, letter_content, letter_signature,
                surprise_title, surprise_message, anniversary_date,
                show_timeline, show_gallery, show_letter, show_counters, show_surprise,
                status, is_demo, payment_status
            ) VALUES (
                'salma-and-mohamed', 'flagship_token_salma_mohamed', 'Mohamed & Salma - Our Love Story', 'Mohamed', 'Salma',
                'Every moment with you became my favorite memory. | كل لحظة معاكي بقت أجمل ذكرى في حياتي.',
                'midnight', 'masonry', 'https://www.youtube.com/watch?v=g7Zw5x6GKlE', 'g7Zw5x6GKlE', 'Our Favorite Song',
                'A Letter To You | رسالة إلى قلبي',
                'Salma,\n\nYou changed my life in the most beautiful way.\nEvery smile from you heals something inside me.\nI never knew love could feel this peaceful until I met you.\n\nسلمى،\nإنتِ أجمل حاجة حصلتلي في حياتي.\nضحكتك أمان ووجودك راحة لقلبي.\nبحبك بطريقة أكبر من أي كلام.',
                'Forever Yours, Mohamed',
                'Forever Starts With You ❤️', 'إلى الأبد يبدأ بوجودك ومعك كل يوم عيد حب ❤️', '2024-08-10',
                1, 1, 1, 1, 1,
                'published', 1, 'paid'
            )");
            $stmt->execute();
            $giftId = $db->lastInsertId();

            // Seed Timeline
            $timelineData = [
                ['You are the calm in my chaos.', 'assets/images/2.jpg', 'Chapter One: The First Spark', 'Day 1'],
                ['كل الطرق الجميلة انتهت بيكي.', 'assets/images/3.jpg', 'Our First Coffee & Long Conversation', 'Day 14'],
                ['Your smile feels like home.', 'assets/images/4.jpg', 'When I Knew You Were The One', 'Day 30'],
                ['وجودك في حياتي نعمة بحمد ربنا عليها كل يوم.', 'assets/images/5.jpg', 'Together Through Every Season', 'Day 60'],
            ];
            $tlStmt = $db->prepare("INSERT INTO timeline_entries (gift_id, title, description, image_url, event_date, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($timelineData as $idx => $t) {
                $tlStmt->execute([$giftId, $t[2], $t[0], $t[1], $t[3], $idx]);
            }

            // Seed Memories Gallery (5 primary initial + more)
            $memories = [
                ['assets/images/6.jpg', 'Pure Happiness', 'Our joyful laughter under the golden sun', 'Memory 1'],
                ['assets/images/7.jpg', 'Unspoken Love', 'The quiet moments that mean the most', 'Memory 2'],
                ['assets/images/8.jpg', 'By The Sea', 'Walking side by side feeling complete peace', 'Memory 3'],
                ['assets/images/11.jpg', 'Golden Hour Romance', 'When the sky matched the warmth in our hearts', 'Memory 4'],
                ['assets/images/12.jpg', 'Endless Smiles', 'Every single day with you is my blessing', 'Memory 5'],
                ['assets/images/13.jpg', 'Sweet Whispers', 'Moments etched into eternity', 'Memory 6'],
                ['assets/images/14.jpg', 'Together Forever', 'Holding hands against the entire world', 'Memory 7'],
                ['assets/images/15.jpg', 'Cherished Date', 'Our favorite unforgettable dinner together', 'Memory 8'],
                ['assets/images/16.jpg', 'Always Beside You', 'To many more beautiful tomorrows', 'Memory 9'],
            ];
            $memStmt = $db->prepare("INSERT INTO memories (gift_id, image_url, title, caption, memory_date, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($memories as $idx => $m) {
                $memStmt->execute([$giftId, $m[0], $m[1], $m[2], $m[3], $idx]);
            }

            // Seed Counters
            $counters = [
                ['Days Together', 70, 'fa-heart', 0],
                ['Photos', 150, 'fa-camera-retro', 1],
                ['Videos', 50, 'fa-video', 2],
                ['Memories', 1000, 'fa-infinity', 3],
            ];
            $cntStmt = $db->prepare("INSERT INTO counters (gift_id, label, target_value, icon, sort_order) VALUES (?, ?, ?, ?, ?)");
            foreach ($counters as $c) {
                $cntStmt->execute([$giftId, $c[0], $c[1], $c[2], $c[3]]);
            }
        }
    }
}
