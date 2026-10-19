<?php
/**
 * Theme Registry - Centralized Romantic Template Definitions
 */

class ThemeRegistry {
    
    private static array $themes = [
        'luxury-cream' => [
            'id' => 'luxury-cream',
            'name_ar' => 'كريمي فاخر (Luxury Cream)',
            'name_en' => 'Luxury Warm Cream',
            'desc_ar' => 'أناقة كلاسيكية مذهبة مع درجات الكريمي الدافئ والبورجوندي الملكي.',
            'desc_en' => 'Warm cream, soft gold, and deep royal burgundy editorial styling.',
            'primary_color' => '#D98A9B',
            'accent_color' => '#D9B77E',
            'bg_gradient' => 'linear-gradient(135deg, #FFF8F0, #D98A9B, #542838)',
            'preview_class' => 'theme-preview-luxury-cream'
        ],
        'midnight' => [
            'id' => 'midnight',
            'name_ar' => 'سماء منتصف الليل (Midnight Romance)',
            'name_en' => 'Midnight Romance',
            'desc_ar' => 'سحر النجوم والبنفسجي الداكن مع توهج الوردي الفاتن.',
            'desc_en' => 'Cosmic indigo, deep violet, and radiant glowing pink star details.',
            'primary_color' => '#FF69B4',
            'accent_color' => '#E0AAFF',
            'bg_gradient' => 'linear-gradient(135deg, #0F0C20, #302B63, #FF69B4)',
            'preview_class' => 'theme-preview-midnight'
        ],
        'rose-blush' => [
            'id' => 'rose-blush',
            'name_ar' => 'وردي ناعم (Rose Blush)',
            'name_en' => 'Rose Blush',
            'desc_ar' => 'نعومة بتلات الورد المتفتحة مع درجات البينك المخملي.',
            'desc_en' => 'Blush petals, velvet rose pink, and glowing rose gold accents.',
            'primary_color' => '#D94B68',
            'accent_color' => '#E5A4B2',
            'bg_gradient' => 'linear-gradient(135deg, #FCF4F6, #F7DDE3, #D94B68)',
            'preview_class' => 'theme-preview-rose-blush'
        ],
        'vintage-scrapbook' => [
            'id' => 'vintage-scrapbook',
            'name_ar' => 'دفتر الذكريات القديم (Vintage Scrapbook)',
            'name_en' => 'Vintage Scrapbook',
            'desc_ar' => 'حنين الورق المعتق وتأثيرات السيبيا مع كروت البولارويد.',
            'desc_en' => 'Warm parchment, sepia nostalgic tones, and classic polaroid photos.',
            'primary_color' => '#A65B32',
            'accent_color' => '#C49A45',
            'bg_gradient' => 'linear-gradient(135deg, #F4EAD4, #E8D8BA, #A65B32)',
            'preview_class' => 'theme-preview-vintage-scrapbook'
        ],
        'cinematic-love' => [
            'id' => 'cinematic-love',
            'name_ar' => 'سينمائي درامي (Cinematic Love)',
            'name_en' => 'Cinematic Love',
            'desc_ar' => 'إطار سينمائي كامل، خلفية ليلية داكنة مع إضاءة سبوت لايت ساحرة وصور ضخمة.',
            'desc_en' => 'Dramatic letterbox framing, obsidian black, spotlight glow, and cinematic photography.',
            'primary_color' => '#E5C07B',
            'accent_color' => '#98C379',
            'bg_gradient' => 'linear-gradient(135deg, #0A0A0E, #1E1E28, #E5C07B)',
            'preview_class' => 'theme-preview-cinematic'
        ],
        'minimal-love' => [
            'id' => 'minimal-love',
            'name_ar' => 'مينيمال رقيق (Minimal Love)',
            'name_en' => 'Minimal Love',
            'desc_ar' => 'مساحات بيضاء راقية، خطوط نحيفة رقيقة وبساطة فائقة تركز على جمال صوركم.',
            'desc_en' => 'Pristine alabaster whitespace, delicate typography, and timeless unhurried romance.',
            'primary_color' => '#2B2D42',
            'accent_color' => '#8D99AE',
            'bg_gradient' => 'linear-gradient(135deg, #FFFFFF, #F8F9FA, #2B2D42)',
            'preview_class' => 'theme-preview-minimal'
        ],
        'dreamy-pastel' => [
            'id' => 'dreamy-pastel',
            'name_ar' => 'باستيل حالم (Dreamy Pastel)',
            'name_en' => 'Dreamy Pastel',
            'desc_ar' => 'درجات اللافندر الحالمة والخوخ الناعم مع كروت ذكريات دائرية ناعمة.',
            'desc_en' => 'Soft lavender, peach mist, pillowy rounded cards, and floating stardust details.',
            'primary_color' => '#B388EB',
            'accent_color' => '#FFCAD4',
            'bg_gradient' => 'linear-gradient(135deg, #F7EEFF, #FFCAD4, #B388EB)',
            'preview_class' => 'theme-preview-pastel'
        ],
        'love-journal' => [
            'id' => 'love-journal',
            'name_ar' => 'مذكرات حبنا (Love Journal)',
            'name_en' => 'Love Journal',
            'desc_ar' => 'ملمس أوراق اليوميات، شرائط لاصقة كرتونية، وعناوين مكتوبة بخط اليد.',
            'desc_en' => 'Lined diary pages, washi tape details, handcrafted notes, and handwritten nostalgia.',
            'primary_color' => '#8C5A3E',
            'accent_color' => '#D4A373',
            'bg_gradient' => 'linear-gradient(135deg, #FAEDCD, #E9D8A6, #8C5A3E)',
            'preview_class' => 'theme-preview-journal'
        ],
        'eternal-romance' => [
            'id' => 'eternal-romance',
            'name_ar' => 'عشق أبدي مذهب (Eternal Romance)',
            'name_en' => 'Eternal Romance',
            'desc_ar' => 'درجات العنابي الملكي المذهب، إطارات راقية وزخارف فخمة للزفاف والمناسبات الكبرى.',
            'desc_en' => 'Regal imperial burgundy, royal black, gilded gold filigree, and eternal wedding majesty.',
            'primary_color' => '#FFD700',
            'accent_color' => '#800020',
            'bg_gradient' => 'linear-gradient(135deg, #2B000B, #520015, #FFD700)',
            'preview_class' => 'theme-preview-eternal'
        ]
    ];

    public static function getAll(): array {
        return self::$themes;
    }

    public static function getAllThemes(): array {
        return self::getAll();
    }

    public static function get(string $themeId): ?array {
        return self::$themes[$themeId] ?? self::$themes['luxury-cream'];
    }

    public static function getTheme(string $themeId): ?array {
        return self::get($themeId);
    }

    public static function isValid(string $themeId): bool {
        return isset(self::$themes[$themeId]);
    }
}
