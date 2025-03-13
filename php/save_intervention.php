<?php
require_once("../../../globals.php");
require_once("$srcdir/sql.inc");

header('Content-Type: application/json');

$patientId = $_POST['patient_id'] ?? 0;
$encounter = $_POST['encounter'] ?? 0;
$svgId = $_POST['svg_id'] ?? '';
$interventionType = $_POST['intervention_type'] ?? '';
$optionId = $_POST['option_id'] ?? '';
$listId = $_POST['list_id'] ?? '';
$date = $_POST['date'] ?? '';
$title = $_POST['title'] ?? '';
$codeType = $_POST['code_type'] ?? '';
$code = $_POST['code'] ?? '';
$notes = $_POST['notes'] ?? '';

// Obtener odontogram_id desde form_odontogram
$sql = "SELECT id FROM form_odontogram WHERE svg_id = ?";
$result = sqlQuery($sql, array($svgId));
$odontogramId = $result['id'] ?? null;

if (!$odontogramId) {
    echo json_encode(['success' => false, 'error' => 'Diente no encontrado']);
    exit;
}

$sql = "INSERT INTO form_odontogram_history (
    patient_id, encounter, odontogram_id, list_id, option_id, intervention_type, date, description, title, code_type, code, notes
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
sqlStatement($sql, array(
    $patientId, $encounter, $odontogramId, $listId, $optionId, $interventionType, $date, $notes, $title, $codeType, $code, $notes
));

echo json_encode(['success' => true]);
exit;