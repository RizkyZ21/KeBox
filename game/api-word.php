<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$level = in_array($_GET['level'] ?? '', ['easy','medium','hard']) ? $_GET['level'] : 'easy';

// Oracle: random row
$stmt = executeQuery(
    "SELECT word FROM (SELECT word FROM words WHERE word_level=:lv ORDER BY DBMS_RANDOM.VALUE) WHERE ROWNUM=1",
    [':lv' => $level]
);
$row = fetchOne($stmt);

if ($row) {
    echo json_encode(['word' => strtoupper($row['word'])]);
} else {
    // Fallback words if DB empty
    $fallback = ['easy'=>['BUKU','MEJA','BOLA','KUDA','TAHU'],'medium'=>['BUNGA','CINTA','DAPUR','GELAP','MANIS'],'hard'=>['BANGSA','CANTIK','GAMBAR','HAMPIR','KARANG']];
    $words = $fallback[$level];
    echo json_encode(['word' => $words[array_rand($words)]]);
}
?>
