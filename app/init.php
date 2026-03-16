<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/core/App.php';
require_once dirname(__DIR__) . '/core/Controller.php';
require_once dirname(__DIR__) . '/core/Database.php';

require_once 'helpers/url_helper.php';
require_once 'helpers/session_helper.php';
require_once 'helpers/pdms_authoring_options.php';

// Only set session settings if a session hasn't started yet
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0); // Set session cookies to expire when the browser is closed
    ini_set('session.use_strict_mode', 1); // Enforce strict mode for sessions (optional)
    session_start(); // Start the session
}

// Autoload core libraries
spl_autoload_register(function($className) {
    require_once dirname(__DIR__) . '/core/' . $className . '.php';
});
?>
