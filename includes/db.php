<?php
// Oracle Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '1521');
define('DB_SERVICE', 'ORCLPDB'); // Change to your Oracle service name
define('DB_USER', 'kebox');
define('DB_PASS', 'kebox123');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $dsn = DB_HOST . ':' . DB_PORT . '/' . DB_SERVICE;
        $conn = oci_connect(DB_USER, DB_PASS, $dsn, 'UTF8');
        if (!$conn) {
            $e = oci_error();
            die(json_encode(['error' => 'Database connection failed: ' . $e['message']]));
        }
    }
    return $conn;
}

function executeQuery($sql, $params = []) {
    $conn = getDB();
    $stmt = oci_parse($conn, $sql);
    foreach ($params as $key => $value) {
        oci_bind_by_name($stmt, $key, $params[$key]);
    }
    oci_execute($stmt);
    return $stmt;
}

function fetchAll($stmt) {
    $results = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $results[] = array_change_key_case($row, CASE_LOWER);
    }
    return $results;
}

function fetchOne($stmt) {
    $row = oci_fetch_assoc($stmt);
    if ($row) return array_change_key_case($row, CASE_LOWER);
    return null;
}
?>
