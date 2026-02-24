<?php
// Database connection for the cinema portal

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cinema_portal');

function get_db(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_errno) {
        http_response_code(500);
        echo '<h1>Database Connection Error</h1>';
        echo '<p>Please import database/schema.sql and database/seed.sql into MySQL and check inc/db.php settings.</p>';
        exit;
    }
    $conn->set_charset('utf8mb4');
    $tzName = date_default_timezone_get();
    if ($tzName) {
        try {
            $tz = new DateTimeZone($tzName);
            $offset = (new DateTime('now', $tz))->format('P');
            $conn->query("SET time_zone = '{$offset}'");
        } catch (Exception $e) {
            // Ignore timezone sync errors and continue
        }
    }
    return $conn;
}

?>
