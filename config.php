<?php
/**
 * London Labels E-Commerce Application
 * Configuration File
 */
if (defined('CONFIG_LOADED')) return;
define('CONFIG_LOADED', true);

// ===== DATABASE CONFIGURATION =====
define('DB_HOST', getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: '127.0.0.1'));
define('DB_PORT', getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: '3306'));
define('DB_NAME', getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'londonlabels'));
define('DB_USER', getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root'));
define('DB_PASS', getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '')); 

// Base URL for the application
// Priority:
// 1) BASE_URL env var if provided
// 2) Infer from DOCUMENT_ROOT + project path (works for /LondonLabels on XAMPP)
// 3) Fallback to '' (works for php -S in project root)
if (!defined('BASE_URL')) {
	$envBaseUrl = getenv('BASE_URL');

	if (is_string($envBaseUrl) && $envBaseUrl !== '') {
		$baseUrl = rtrim($envBaseUrl, '/');
	} else {
		$projectRoot = str_replace('\\', '/', realpath(__DIR__) ?: __DIR__);
		$documentRootRaw = $_SERVER['DOCUMENT_ROOT'] ?? '';
		$documentRoot = str_replace('\\', '/', realpath($documentRootRaw) ?: $documentRootRaw);

		$baseUrl = '';
		if ($documentRoot !== '' && stripos($projectRoot, $documentRoot) === 0) {
			$relative = substr($projectRoot, strlen($documentRoot));
			$relative = str_replace('\\', '/', $relative);
			$relative = '/' . ltrim($relative, '/');
			$baseUrl = rtrim($relative, '/');
			if ($baseUrl === '/') {
				$baseUrl = '';
			}
		}
	}

	define('BASE_URL', $baseUrl);
}

// ===== MAILER CONFIGURATION (Mailtrap SMTP) =====
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'sandbox.smtp.mailtrap.io');
define('MAIL_PORT', (int)(getenv('MAIL_PORT') ?: 465));
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');

// Auto-switch encryption: 465 usually means 'ssl', 587 usually means 'tls'
$defaultEnc = (MAIL_PORT === 465) ? 'ssl' : 'tls';
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: $defaultEnc);
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@londonlabels.com');
define('MAIL_FROM_NAME', 'London Labels');

// ===== SITE CONFIGURATION =====
define('SITE_NAME', 'London Labels');
define('SITE_TAGLINE', 'Style Without Borders');
define('SITE_LOGO_URL', BASE_URL . '/assets/images/londonlabels logo.jpeg');

// ===== STORE LOCATION =====
define('STORE_ADDRESS', 'London Labels, Treasure Mall, Abijo GRA, by Rain Oil, Ajah, Lagos, Nigeria.');
define('STORE_MAP_QUERY', 'London Labels, Treasure Mall, Abijo GRA, by Rain Oil, Ajah, Lagos, Nigeria');
define('STORE_MAP_URL', 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(STORE_MAP_QUERY));
define('STORE_MAP_EMBED_URL', 'https://www.google.com/maps?q=' . rawurlencode(STORE_MAP_QUERY) . '&output=embed');

// ===== COMMUNITY CHANNELS =====
// Set these to your real public URLs.
define('WHATSAPP_GROUP_URL', 'https://chat.whatsapp.com/EWC5GGpwipQK0aJDDpx4ZN');
define('YOUTUBE_CHANNEL_URL', 'https://www.youtube.com/@LondonLabels');

// ===== CONTACT DETAILS =====
define('CONTACT_EMAIL', 'support@londonlabels.com');
define('CONTACT_PHONE', '+234 800 000 0000'); // Replace with your real number
define('CONTACT_PHONE_HREF', 'tel:+2348000000000'); // Replace with your real number
define('CUSTOMER_CARE_HOURS', '9AM – 6PM WAT');

// ===== CURRENCY =====
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '₦');
if (!defined('CURRENCY_CODE')) define('CURRENCY_CODE', 'NGN');
define('UPLOAD_DIR', __DIR__ . '/Uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('MEDIA_CDN_BASE_URL', getenv('MEDIA_CDN_BASE_URL') ?: '');
define('MEDIA_SIGNING_SECRET', getenv('MEDIA_SIGNING_SECRET') ?: '');
define('CLAMSCAN_BIN', getenv('CLAMSCAN_BIN') ?: '');
define('FFMPEG_BIN', getenv('FFMPEG_BIN') ?: '');
define('UPLOAD_MAX_PER_HOUR', (int)(getenv('UPLOAD_MAX_PER_HOUR') ?: 30));
define('UPLOAD_MAX_PER_DAY', (int)(getenv('UPLOAD_MAX_PER_DAY') ?: 120));
define('PRODUCT_IMAGES_MIN_RECOMMENDED', (int)(getenv('PRODUCT_IMAGES_MIN_RECOMMENDED') ?: 6));
define('PRODUCT_IMAGES_MAX_RECOMMENDED', (int)(getenv('PRODUCT_IMAGES_MAX_RECOMMENDED') ?: 8));
define('PRODUCT_IMAGES_MAX_ALLOWED', (int)(getenv('PRODUCT_IMAGES_MAX_ALLOWED') ?: 8));

// ===== PAGINATION =====
define('ITEMS_PER_PAGE', 12);

// ===== PAYSTACK =====
// Get your keys from https://dashboard.paystack.com/#/settings/developer
define('PAYSTACK_SECRET_KEY', getenv('PAYSTACK_SECRET_KEY') ?: '');
define('PAYSTACK_PUBLIC_KEY', getenv('PAYSTACK_PUBLIC_KEY') ?: '');

// ===== OAUTH CREDENTIALS =====
// Add your credentials below. Keep these secure!
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
