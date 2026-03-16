<?php
define('DB_HOST', 'localhost'); // Database host
define('DB_USER', 'root'); // Database user
define('DB_PASS', ''); // Database password
define('DB_NAME', 'sopweb'); // Database name

define('MAIL_HOST', getenv('SOPWEB_MAIL_HOST') ?: '');
define('MAIL_PORT', (int) (getenv('SOPWEB_MAIL_PORT') ?: 587));
define('MAIL_USERNAME', getenv('SOPWEB_MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('SOPWEB_MAIL_PASSWORD') ?: '');
define('MAIL_FROM_ADDRESS', getenv('SOPWEB_MAIL_FROM_ADDRESS') ?: '');
define('MAIL_FROM_NAME', getenv('SOPWEB_MAIL_FROM_NAME') ?: 'SOPWeb');
define('MAIL_NOTIFY_ADDRESS', getenv('SOPWEB_MAIL_NOTIFY_ADDRESS') ?: '');

define('APPROOT', dirname(dirname(__FILE__))); // App root
$defaultPdfBrowserRoots = [APPROOT . DIRECTORY_SEPARATOR . 'uploads'];
$userProfile = getenv('USERPROFILE') ?: '';
$oneDriveRoot = getenv('OneDrive') ?: '';

if ($userProfile !== '') {
    $defaultPdfBrowserRoots[] = $userProfile . DIRECTORY_SEPARATOR . 'Documents';
    $defaultPdfBrowserRoots[] = $userProfile . DIRECTORY_SEPARATOR . 'Downloads';
    $defaultPdfBrowserRoots[] = $userProfile . DIRECTORY_SEPARATOR . 'Desktop';
}

if ($oneDriveRoot !== '') {
    $defaultPdfBrowserRoots[] = $oneDriveRoot . DIRECTORY_SEPARATOR . 'Documents';
    $defaultPdfBrowserRoots[] = $oneDriveRoot . DIRECTORY_SEPARATOR . 'Downloads';
}

$pdfBrowserRootsRaw = getenv('SOPWEB_PDF_BROWSER_ROOTS') ?: implode(';', $defaultPdfBrowserRoots);
$pdfBrowserRoots = array_values(array_unique(array_filter(array_map('trim', preg_split('/[;\r\n]+/', $pdfBrowserRootsRaw)))));
define('PDF_BROWSER_ROOTS', $pdfBrowserRoots);
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('URLROOT', 'http://' . $httpHost . '/SOPWeb/public/');
define('SITENAME', 'SOPWeb'); // Site name
?>
