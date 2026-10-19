<?php
/**
 * Internationalization (I18n) Engine - Arabic First
 */

require_once __DIR__ . '/../config.php';

class I18n {
    private static ?string $currentLang = null;
    private static array $strings = [];

    public static function init(): void {
        if (self::$currentLang !== null) {
            return;
        }

        // Handle language switch via query parameter ?lang=ar or ?lang=en
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
            $selected = $_GET['lang'];
            $_SESSION['lang'] = $selected;
            setcookie('habeba_lang', $selected, time() + (86400 * 365), '/');
            self::$currentLang = $selected;
        } elseif (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], ['ar', 'en'])) {
            self::$currentLang = $_SESSION['lang'];
        } elseif (!empty($_COOKIE['habeba_lang']) && in_array($_COOKIE['habeba_lang'], ['ar', 'en'])) {
            self::$currentLang = $_COOKIE['habeba_lang'];
        } else {
            // Default language is ARABIC as mandated by the prompt
            self::$currentLang = 'ar';
        }

        self::loadStrings(self::$currentLang);
    }

    private static function loadStrings(string $lang): void {
        $file = __DIR__ . '/../locales/' . $lang . '.php';
        if (file_exists($file)) {
            self::$strings = require $file;
        } else {
            // Fallback to Arabic
            self::$strings = require __DIR__ . '/../locales/ar.php';
        }
    }

    public static function get(string $key, ...$args): string {
        self::init();
        $text = self::$strings[$key] ?? $key;
        if (!empty($args)) {
            return sprintf($text, ...$args);
        }
        return $text;
    }

    public static function getLang(): string {
        self::init();
        return self::$currentLang;
    }

    public static function setLang(string $lang): void {
        if (in_array($lang, ['ar', 'en'])) {
            self::$currentLang = $lang;
            $_SESSION['lang'] = $lang;
            self::loadStrings($lang);
        }
    }

    public static function setLocale(string $lang): void {
        self::setLang($lang);
    }

    public static function getLocale(): string {
        return self::getLang();
    }

    public static function getDir(): string {
        return self::getLang() === 'ar' ? 'rtl' : 'ltr';
    }

    public static function isRtl(): bool {
        return self::getDir() === 'rtl';
    }

    public static function getSwitcherUrl(): string {
        self::init();
        $target = (self::$currentLang === 'ar') ? 'en' : 'ar';
        $params = $_GET;
        $params['lang'] = $target;
        return '?' . http_build_query($params);
    }
}

// Global translation shorthand
function __(string $key, ...$args): string {
    return I18n::get($key, ...$args);
}
