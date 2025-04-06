<?php
ob_start();
require_once("../../globals.php");
require_once("$srcdir/forms.inc");
require_once("$srcdir/patient.inc");

use OpenEMR\Common\Csrf\CsrfUtils;

if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"] ?? '')) {
    CsrfUtils::csrfNotVerified();
}

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

if (!is_array($data) || empty($data)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('No changes to save')]);
    exit;
}

$pid = $_SESSION['pid'] ?? 0;
$encounter = $_SESSION['encounter'] ?? 0;
$user = $_SESSION['authUser'] ?? '';
$groupname = $_SESSION['authGroup'] ?? '';
$authorized = $_SESSION['userauthorized'] ?? 0;
$activity = 1;

if (!$pid || !$encounter) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('Missing patient or encounter context')]);
    exit;
}

$success = true;
$last_history_id = null;
foreach ($data as $change) {
    $tooth_id = $change['tooth_id'] ?? '';
    $intervention_type = $change['intervention_type'] ?? '';
    $option_id = $change['option_id'] ?? '';
    $svg_style = $change['svg_style'] ?? '';
    $code = $change['code'] ?? '';
    $notes = $change['notes'] ?? '';
    $date = $change['date'] ?? date('Y-m-d H:i:s');

    if (empty($tooth_id) || empty($intervention_type) || empty($option_id)) {
        $success = false;
        continue;
    }

    $odontogram_id = sqlQuery("SELECT id FROM form_odontogram WHERE tooth_id = ?", [$tooth_id])['id'] ?? null;

    $sql = "INSERT INTO form_odontogram_history (pid, encounter, odontogram_id, intervention_type, option_id, svg_style, date, code, notes, user, groupname, authorized, activity) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $params = [$pid, $encounter, $odontogram_id, $intervention_type, $option_id, $svg_style, $date, $code, $notes, $user, $groupname, $authorized, $activity];

    try {
        $history_id = sqlInsert($sql, $params);
        if ($history_id) {
            $last_history_id = $history_id; // Guardamos el último ID
        } else {
            $success = false;
        }
    } catch (Exception $e) {
        $success = false;
        error_log("save.php - Error: " . $e->getMessage());
    }
}

ob_end_clean();
header('Content-Type: application/json');
if ($success) {
    echo json_encode(['success' => true, 'id' => $last_history_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => xl('Failed to save some changes')]);
}
exit;