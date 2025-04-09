<?php
require_once("../../../globals.php");
require_once("$srcdir/sql.inc.php");

$tooth_id = $_POST['tooth_id'] ?? '';
$user_id = $_POST['user_id'] ?? '';

if (!$tooth_id) {
    echo json_encode(['error' => 'No tooth_id provided']);
    exit;
}

$sql = "SELECT style, name, universal AS number, fdi, palmer AS palmer_symbol, part, arc, side 
        FROM form_odontogram 
        WHERE tooth_id = ?";
$result = sqlQuery($sql, [$tooth_id]);

if ($result) {
    // Mapear campos para mantener compatibilidad con el frontend
    $result['system'] = $result['fdi'] ? 'FDI' : ($result['palmer'] ? 'PALMER' : 'UNIVERSAL');
    echo json_encode($result);
} else {
    echo json_encode(['error' => 'Tooth not found']);
}
exit;