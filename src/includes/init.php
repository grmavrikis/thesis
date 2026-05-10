<?php
/* 
Initialization File
This file sets up the environment, loads necessary classes,
and initializes core objects for the application.
The file is included at the beginning of each main script.
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set('Europe/Athens');
define('BASE_PATH', dirname(__DIR__));
define('DB_HOST', 'db'); // Usually localhost but set to 'db' for Docker setups
define('DB_USER', 'my_thesis_user');
define('DB_PASS', '9s!9oU1x1');
define('DB_NAME', 'my_thesis_db');
define('DB_CHARSET', 'utf8mb4');

require_once BASE_PATH . '/classes/auth.php';
require_once BASE_PATH . '/classes/database.php';
require_once BASE_PATH . '/classes/languages.php';
require_once BASE_PATH . '/classes/seo_url.php';
require_once BASE_PATH . '/classes/breadcrumb.php';
require_once BASE_PATH . '/classes/page_controller.php';
require_once BASE_PATH . '/classes/file_manager.php';
require_once BASE_PATH . '/classes/invoice.php';
require_once BASE_PATH . '/includes/general_functions.php';

// Initialize Core Objects
try
{
    $auth = new Auth();
    $db = new Database();

    $languages = new Languages();
    $language = $languages->getLanguage();

    require_once BASE_PATH . '/languages/' . $language['directory'] . '.php';
    $seoUrl = new SeoUrl($languages);
    $pageController = new PageController(
        $language['directory'],
        BASE_PATH
    );
}
catch (Exception $e)
{
    die("System Error: " . $e->getMessage());
}
