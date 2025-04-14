<?php
require_once("../../../globals.php");
require_once("$srcdir/sql.inc.php");

$tooth_id = $_POST['tooth_id'] ?? '';

if (!$tooth_id) {
    echo json_encode(['error' => 'No tooth_id provided']);
    exit;
}

$sql = "SELECT id, universal, fdi, palmer, dentition_type, name, part, arc, style, side, tooth_id, d, width, height, x, y, sodipodi, svg_type 
        FROM form_odontogram 
        WHERE tooth_id = ?";
$result = sqlQuery($sql, [$tooth_id]);

if ($result) {
    echo json_encode($result);
} else {
    echo json_encode(['error' => 'Tooth not found']);
}
exit;