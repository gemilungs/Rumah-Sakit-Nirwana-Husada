<?php
// Copy this file to config.php and update values. Do NOT commit real credentials.
// Example local config (use environment variables in production)
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'rumah_sakit');

// Application secret: set via environment variable APP_SECRET in production
define('APP_SECRET', getenv('APP_SECRET') ?: 'change_this_to_a_strong_secret');

// Timezone
date_default_timezone_set('Asia/Jakarta');

/**
 * Usage:
 * - Copy to php/config.php
 * - Make sure php/config.php is excluded from version control (e.g., in .gitignore)
 */
