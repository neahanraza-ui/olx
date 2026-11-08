<?php
// includes/lang.php
if (session_status() === PHP_SESSION_NONE) session_start();

$current_lang = $_SESSION['lang'] ?? 'en';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ur'])) {
    $current_lang = $_GET['lang'];
    $_SESSION['lang'] = $current_lang;
    
    // Update user preference if logged in
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/db.php';
        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
        $stmt->execute([$current_lang, $_SESSION['user_id']]);
    }
}

$translations = [
    'en' => [
        // ── Header / Nav ──
        'home'               => 'Home',
        'search_placeholder' => 'Search for anything...',
        'online'             => 'Online',
        'messages'           => 'Messages',
        'notifications'      => 'Notifications',
        'post_ad'            => 'Post Ad',
        'login'              => 'Login',
        'join_free'          => 'Join Free',
        'my_account'         => 'My Account',
        'logout'             => 'Logout',
        'categories'         => 'Categories',
        'featured_ads'       => 'Featured Ads',
        'recent_ads'         => 'Recent Ads',
        'block_user'         => 'Block User',
        'report_chat'        => 'Report Chat',
        'propose_deal'       => 'Propose Deal',
        'ai_assist'          => 'AI Assistant',
        'translate'          => 'Translate',

        // ── Login ──
        'welcome_back'       => 'Welcome back',
        'sign_in_sub'        => 'Sign in to your account',
        'email_address'      => 'Email address',
        'password'           => 'Password',
        'sign_in_btn'        => 'Sign In',
        'forgot_password'    => 'Forgot password?',
        'no_account'         => "Don't have an account?",
        'create_free'        => "Create one — it's free",
        'invalid_credentials'=> 'Invalid email or password.',

        // ── Register ──
        'create_account'     => 'Create account',
        'register_sub'       => 'Join thousands of trusted buyers and sellers',
        'full_name'          => 'Full Name',
        'confirm_password'   => 'Confirm Password',
        'password_min_ph'    => 'Min. 8 characters',
        'repeat_password_ph' => 'Repeat password',
        'by_creating'        => 'By creating an account you agree to our',
        'terms'              => 'Terms of Service',
        'and'                => 'and',
        'privacy'            => 'Privacy Policy',
        'create_account_btn' => 'Create Account',
        'already_account'    => 'Already have an account?',
        'sign_in_link'       => 'Sign in',
        'err_fields_required'=> 'All fields are required.',
        'err_passwords_match'=> 'Passwords do not match.',
        'err_password_min'   => 'Password must be at least 8 characters.',
        'err_email_exists'   => 'An account with this email already exists.',

        // ── Forgot Password ──
        'forgot_password_title' => 'Forgot Password',
        'forgot_password_sub'   => "Enter your email and we'll send a reset link.",
        'send_reset_link'       => 'Send Reset Link',
        'back_to_login'         => 'Back to Login',
        'reset_sent'            => 'If that email exists, a reset link has been sent.',
        'dev_mode_link'         => 'Dev Mode — Reset Link:',

        // ── Homepage Hero ──
        'hero_title'         => 'Buy & Sell with <span>Confidence</span>',
        'hero_sub'           => "Pakistan's most trust-focused marketplace. Meet, agree, and confirm with a mutual receipt.",
        'search_keyword_ph'  => 'What are you looking for?',
        'search_city_ph'     => 'City or Area',
        'hero_search_btn'    => 'Search',
        'online_delivery_q'  => 'Looking for online delivery?',
        'online_delivery_link'=> 'See Online listings (proceed with care)',
        'stat_active'        => 'Active Listings',
        'stat_verified'      => 'Verified Sellers',
        'stat_deals'         => 'Deals Confirmed',

        // ── Trust Banner ──
        'trust_meet'         => 'Meet Safely',
        'trust_meet_sub'     => 'Always meet in public places',
        'trust_receipt'      => 'Mutual Receipts',
        'trust_receipt_sub'  => 'Get a signed digital agreement',
        'trust_risk'         => 'Online Delivery Risk',
        'trust_risk_sub'     => 'We warn you before every online deal',
        'trust_verified'     => 'Verified Sellers',
        'trust_verified_sub' => 'Check trust scores before buying',

        // ── Categories / Sections ──
        'browse_categories'  => 'Browse Categories',
        'view_all'           => 'View all',
        'fresh_listings'     => 'Fresh Listings',
        'no_listings_yet'    => 'No listings yet. Be the first to post!',
        'post_ad_first'      => '+ Post Ad',
        'higher_risk'        => 'Higher Risk',

        // ── Search / Filters ──
        'filters'            => 'Filters',
        'category'           => 'Category',
        'clear_category'     => 'Clear category',
        'price_range'        => 'Price Range (Rs)',
        'price_min_ph'       => 'Min',
        'price_max_ph'       => 'Max',
        'location_label'     => 'Location',
        'location_ph'        => 'City or area',
        'seller_label'       => 'Seller',
        'verified_only'      => 'Verified Only',
        'delivery_type'      => 'Delivery Type',
        'online_delivery'    => 'Online Delivery',
        'apply_filters'      => 'Apply Filters',
        'result_count'       => 'result',
        'results_count'      => 'results',
        'results_for'        => 'for',
        'results_in'         => 'in',
        'newest_first'       => 'Newest First',
        'price_low_high'     => 'Price: Low to High',
        'price_high_low'     => 'Price: High to Low',
        'no_listings_found'  => 'No listings found. Try different keywords or clear the filters.',
        'clear_filters'      => 'Clear Filters',
        'online_scam_warn'   => '<strong>Warning:</strong> Online delivery listings carry a higher scam risk. Always verify the seller before sending any payment.',
        'any_country'        => 'Any Country',
        'any_city'           => 'Any City',
        'country_city'       => 'Country & City',

        // ── Footer ──
        'footer_about'       => 'The trust-focused marketplace for buying and selling locally and online. Every deal is backed by our mutual receipt system.',
        'footer_links'       => 'Quick Links',
        'footer_follow'      => 'Follow Us',
        'all_rights'         => 'All Rights Reserved.',
        'footer_marketplace' => 'Marketplace',
        'footer_browse_all'  => 'Browse All',
        'footer_online_del'  => 'Online Delivery',
        'footer_post_ad'     => 'Post an Ad',
        'footer_account'     => 'Account',
        'footer_dashboard'   => 'Dashboard',
        'footer_messages'    => 'Messages',
        'footer_receipts'    => 'Receipts',
        'footer_profile'     => 'Profile',
        'footer_safety'      => 'Safety',
        'footer_safety_tips' => 'Safety Tips',
        'footer_report_scam' => 'Report a Scam',
        'footer_how_receipts'=> 'How Receipts Work',
        'footer_terms'       => 'Terms of Service',
        'footer_trust'       => 'Trust is our Priority',
        'footer_rights'      => 'All rights reserved.',

        // ── Chatbot ──
        'chat_title'         => 'OLX Plus Assistant',
        'chat_subtitle'      => 'AI-powered help',
        'chat_placeholder'   => 'Ask me anything...',
        'chat_send'          => 'Send',
        'chat_welcome'       => 'Hi! I\'m your OLX Plus assistant. Ask me about buying, selling, safety tips, or how the site works.',
        'chat_error'         => 'Something went wrong. Please try again.',
        'chat_thinking'      => 'Thinking...',
    ],

    'ur' => [
        // ── Header / Nav ──
        'home'               => 'ہوم',
        'search_placeholder' => 'کچھ بھی تلاش کریں...',
        'online'             => 'آن لائن',
        'messages'           => 'پیغامات',
        'notifications'      => 'اطلاعات',
        'post_ad'            => 'اشتہار لگائیں',
        'login'              => 'لاگ ان',
        'join_free'          => 'مفت شامل ہوں',
        'my_account'         => 'میرا اکاؤنٹ',
        'logout'             => 'لاگ آؤٹ',
        'categories'         => 'اقسام',
        'featured_ads'       => 'نمایاں اشتہارات',
        'recent_ads'         => 'حالیہ اشتہارات',
        'block_user'         => 'صارف کو بلاک کریں',
        'report_chat'        => 'چیٹ کی رپورٹ کریں',
        'propose_deal'       => 'ڈیل کی تجویز دیں',
        'ai_assist'          => 'اے آئی اسسٹنٹ',
        'translate'          => 'ترجمہ کریں',

        // ── Login ──
        'welcome_back'       => 'واپس خوش آمدید',
        'sign_in_sub'        => 'اپنے اکاؤنٹ میں داخل ہوں',
        'email_address'      => 'ای میل ایڈریس',
        'password'           => 'پاس ورڈ',
        'sign_in_btn'        => 'سائن ان کریں',
        'forgot_password'    => 'پاس ورڈ بھول گئے؟',
        'no_account'         => 'اکاؤنٹ نہیں ہے؟',
        'create_free'        => 'مفت بنائیں',
        'invalid_credentials'=> 'غلط ای میل یا پاس ورڈ۔',

        // ── Register ──
        'create_account'     => 'اکاؤنٹ بنائیں',
        'register_sub'       => 'ہزاروں قابل اعتماد خریداروں اور فروخت کنندگان میں شامل ہوں',
        'full_name'          => 'پورا نام',
        'confirm_password'   => 'پاس ورڈ کی تصدیق کریں',
        'password_min_ph'    => 'کم از کم 8 حروف',
        'repeat_password_ph' => 'پاس ورڈ دہرائیں',
        'by_creating'        => 'اکاؤنٹ بنا کر آپ ہماری',
        'terms'              => 'سروس کی شرائط',
        'and'                => 'اور',
        'privacy'            => 'پرائیویسی پالیسی',
        'create_account_btn' => 'اکاؤنٹ بنائیں',
        'already_account'    => 'پہلے سے اکاؤنٹ ہے؟',
        'sign_in_link'       => 'سائن ان کریں',
        'err_fields_required'=> 'تمام فیلڈز لازمی ہیں۔',
        'err_passwords_match'=> 'پاس ورڈ میل نہیں کھاتے۔',
        'err_password_min'   => 'پاس ورڈ کم از کم 8 حروف کا ہونا چاہیے۔',
        'err_email_exists'   => 'اس ای میل سے پہلے سے اکاؤنٹ موجود ہے۔',

        // ── Forgot Password ──
        'forgot_password_title' => 'پاس ورڈ بھول گئے',
        'forgot_password_sub'   => 'اپنی ای میل درج کریں اور ہم آپ کو ری سیٹ لنک بھیجیں گے۔',
        'send_reset_link'       => 'ری سیٹ لنک بھیجیں',
        'back_to_login'         => 'لاگ ان پر واپس جائیں',
        'reset_sent'            => 'اگر وہ ای میل موجود ہے تو ری سیٹ لنک بھیج دیا گیا ہے۔',
        'dev_mode_link'         => 'ڈیو موڈ — ری سیٹ لنک:',

        // ── Homepage Hero ──
        'hero_title'         => 'اعتماد کے ساتھ <span>خرید و فروخت</span> کریں',
        'hero_sub'           => 'پاکستان کا سب سے اعتماد پر مبنی مارکیٹ پلیس۔ ملیں، اتفاق کریں اور باہمی رسید سے تصدیق کریں۔',
        'search_keyword_ph'  => 'آپ کیا ڈھونڈ رہے ہیں؟',
        'search_city_ph'     => 'شہر یا علاقہ',
        'hero_search_btn'    => 'تلاش کریں',
        'online_delivery_q'  => 'آن لائن ڈیلیوری ڈھونڈ رہے ہیں؟',
        'online_delivery_link'=> 'آن لائن اشتہارات دیکھیں (احتیاط سے آگے بڑھیں)',
        'stat_active'        => 'فعال اشتہارات',
        'stat_verified'      => 'تصدیق شدہ فروخت کنندگان',
        'stat_deals'         => 'تصدیق شدہ ڈیلز',

        // ── Trust Banner ──
        'trust_meet'         => 'محفوظ طریقے سے ملیں',
        'trust_meet_sub'     => 'ہمیشہ عوامی مقامات پر ملیں',
        'trust_receipt'      => 'باہمی رسیدیں',
        'trust_receipt_sub'  => 'ڈیجیٹل معاہدہ حاصل کریں',
        'trust_risk'         => 'آن لائن ڈیلیوری کا خطرہ',
        'trust_risk_sub'     => 'ہم ہر آن لائن ڈیل سے پہلے خبردار کرتے ہیں',
        'trust_verified'     => 'تصدیق شدہ فروخت کنندگان',
        'trust_verified_sub' => 'خریداری سے پہلے اعتماد کا اسکور چیک کریں',

        // ── Categories / Sections ──
        'browse_categories'  => 'اقسام دیکھیں',
        'view_all'           => 'سب دیکھیں',
        'fresh_listings'     => 'تازہ اشتہارات',
        'no_listings_yet'    => 'ابھی تک کوئی اشتہار نہیں۔ پہلے آپ لگائیں!',
        'post_ad_first'      => '+ اشتہار لگائیں',
        'higher_risk'        => 'زیادہ خطرہ',

        // ── Search / Filters ──
        'filters'            => 'فلٹرز',
        'category'           => 'قسم',
        'clear_category'     => 'قسم صاف کریں',
        'price_range'        => 'قیمت کی حد (Rs)',
        'price_min_ph'       => 'کم از کم',
        'price_max_ph'       => 'زیادہ سے زیادہ',
        'location_label'     => 'مقام',
        'location_ph'        => 'شہر یا علاقہ',
        'seller_label'       => 'فروخت کنندہ',
        'verified_only'      => 'صرف تصدیق شدہ',
        'delivery_type'      => 'ڈیلیوری کی قسم',
        'online_delivery'    => 'آن لائن ڈیلیوری',
        'apply_filters'      => 'فلٹر لگائیں',
        'result_count'       => 'نتیجہ',
        'results_count'      => 'نتائج',
        'results_for'        => 'کے لیے',
        'results_in'         => 'میں',
        'newest_first'       => 'سب سے نیا پہلے',
        'price_low_high'     => 'قیمت: کم سے زیادہ',
        'price_high_low'     => 'قیمت: زیادہ سے کم',
        'no_listings_found'  => 'کوئی اشتہار نہیں ملا۔ مختلف الفاظ آزمائیں یا فلٹر صاف کریں۔',
        'clear_filters'      => 'فلٹر صاف کریں',
        'online_scam_warn'   => '<strong>خبردار:</strong> آن لائن ڈیلیوری اشتہارات میں فراڈ کا زیادہ خطرہ ہے۔ کوئی بھی ادائیگی کرنے سے پہلے فروخت کنندہ کی تصدیق ضرور کریں۔',
        'any_country'        => 'کوئی بھی ملک',
        'any_city'           => 'کوئی بھی شہر',
        'country_city'       => 'ملک اور شہر',

        // ── Footer ──
        'footer_about'       => 'OLX Plus پاکستان کا اعتماد پر مبنی مارکیٹ پلیس ہے۔ ہر ڈیل ہماری باہمی رسید کے نظام سے محفوظ ہے۔',
        'footer_links'       => 'فوری لنکس',
        'footer_follow'      => 'ہمارے ساتھ چلو',
        'all_rights'         => 'تمام حقوق محفوظ ہیں۔',
        'footer_marketplace' => 'مارکیٹ پلیس',
        'footer_browse_all'  => 'سب دیکھیں',
        'footer_online_del'  => 'آن لائن ڈیلیوری',
        'footer_post_ad'     => 'اشتہار لگائیں',
        'footer_account'     => 'اکاؤنٹ',
        'footer_dashboard'   => 'ڈیش بورڈ',
        'footer_messages'    => 'پیغامات',
        'footer_receipts'    => 'رسیدیں',
        'footer_profile'     => 'پروفائل',
        'footer_safety'      => 'حفاظت',
        'footer_safety_tips' => 'حفاظتی مشورے',
        'footer_report_scam' => 'فراڈ کی رپورٹ کریں',
        'footer_how_receipts'=> 'رسیدیں کیسے کام کرتی ہیں',
        'footer_terms'       => 'سروس کی شرائط',
        'footer_trust'       => 'اعتماد ہماری ترجیح ہے',
        'footer_rights'      => 'تمام حقوق محفوظ ہیں۔',

        // ── Chatbot ──
        'chat_title'         => 'OLX Plus اسسٹنٹ',
        'chat_subtitle'      => 'اے آئی مدد',
        'chat_placeholder'   => 'کچھ بھی پوچھیں...',
        'chat_send'          => 'بھیجیں',
        'chat_welcome'       => 'ہیلو! میں آپ کا OLX Plus اسسٹنٹ ہوں۔ خریداری، فروخت، حفاظتی مشورے یا سائٹ کے بارے میں پوچھیں۔',
        'chat_error'         => 'کچھ غلط ہو گیا۔ دوبارہ کوشش کریں۔',
        'chat_thinking'      => 'سوچ رہا ہوں...',
    ]
];

function __($key) {
    global $translations, $current_lang;
    return $translations[$current_lang][$key] ?? $key;
}

function isRTL() {
    global $current_lang;
    return $current_lang === 'ur';
}
?>
