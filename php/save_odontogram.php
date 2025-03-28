<?php
ob_start();
require_once("../../../globals.php");
require_once("$srcdir/forms.inc.php");

if (!isset($_SESSION['authUser'])) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['error' => xl('Unauthorized')]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('Invalid data received')]);
    exit;
}

$tooth_id = $data['tooth_id'] ?? '';
$intervention_type = $data['intervention_type'] ?? '';
$option_id = $data['option_id'] ?? '';
$symbol = $data['symbol'] ?? '';
$code = $data['code'] ?? '';

if (empty($tooth_id) || empty($intervention_type) || empty($option_id)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('Missing required fields'), 'data_received' => $data]);
    exit;
}

$pid = $_SESSION['pid'] ?? 0;
$encounter = $_SESSION['encounter'] ?? 0;
$user = $_SESSION['authUser'] ?? '';
$groupname = $_SESSION['authGroup'] ?? '';
$authorized = $_SESSION['userauthorized'] ?? 0;
$activity = 1;
$date = date('Y-m-d');

if (!$pid || !$encounter) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('Missing patient or encounter context')]);
    exit;
}

// Obtener odontogram_id desde form_odontogram usando svg_id
$odontogram_id = sqlQuery("SELECT id FROM form_odontogram WHERE svg_id = ?", [$tooth_id])['id'] ?? null;

if (!$odontogram_id) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('Invalid tooth ID'), 'tooth_id' => $tooth_id]);
    exit;
}

// Insertar en form_odontogram_history
$sql = "INSERT INTO form_odontogram_history (pid, encounter, odontogram_id, intervention_type, option_id, date, symbol, code, description, user, groupname, authorized, activity) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$params = [
    $pid,
    $encounter,
    $odontogram_id,
    $intervention_type,
    $option_id,
    $date,
    $symbol,
    $code,
    $tooth_id,
    $user,
    $groupname,
    $authorized,
    $activity
];

try {
    $history_id = sqlInsert($sql, $params);
    if ($history_id) {
        // Registrar el formulario en forms (usamos un ID de formulario general si existe)
        $form_id = sqlInsert("INSERT INTO form_odontogram (svg_id) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)", [$tooth_id]);
        addForm($encounter, "Odontogram", $form_id, "odontogram", $pid, $user, $groupname, $authorized);
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $history_id]);
    } else {
        throw new Exception('Insert failed');
    }
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => xl('Failed to save data'), 'details' => $e->getMessage()]);
}
exit;
?>