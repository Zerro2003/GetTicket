<?php
// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the main configuration file
require_once('config.php');

// Include Composer's autoloader if it exists
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
} else {
    // Fallback to manual loading if Composer is not used
    require_once(__DIR__ . '/libs/TCPDF/tcpdf.php');
    require_once(__DIR__ . '/libs/qr-code/src/QrCode.php');
    require_once(__DIR__ . '/libs/qr-code/src/Writer/PngWriter.php');
    // Add any other manual includes here
}
?>