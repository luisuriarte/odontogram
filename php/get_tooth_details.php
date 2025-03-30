<?php
require_once '../../../globals.php';
require_once "$srcdir/sql.inc.php";

header('Content-Type: application/json');

$tooth_id = $_POST['tooth_id'] ?? '';
error_log("get_tooth_details.php - Received POST: " . json_encode($_POST));
error_log("get_tooth_details.php - Tooth ID: " . $tooth_id);

if (empty($tooth_id)) {
    echo json_encode(['error' => 'Missing tooth_id']);
    exit;
}

try {
    $result = sqlQuery("SELECT name, universal, fdi, palmer, part, arc, side FROM form_odontogram WHERE tooth_id = ?", [$tooth_id]);
    if ($result) {
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Tooth not found', 'tooth_id' => $tooth_id]);
    }
} catch (Exception $e) {
    error_log("get_tooth_details.php - SQL Error: " . $e->getMessage());
    echo json_encode(['error' => 'Database query failed', 'details' => $e->getMessage()]);
}

exit;
?>