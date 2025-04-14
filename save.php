<?php
ob_start();
require_once("../../globals.php");
require_once("$srcdir/forms.inc");
require_once("$srcdir/patient.inc");

use OpenEMR\Common\Csrf\CsrfUtils;

if (!CsrfUtils::verifyCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    CsrfUtils::csrfNotVerified();
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

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !is_array($data)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('Invalid or no data received')]);
    exit;
}

$changes = $data['changes'] ?? [];
if (empty($changes)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('No changes to save')]);
    exit;
}

$success = true;
$last_history_id = null;
$id = (int) (isset($_GET['id']) ? $_GET['id'] : 0);

if ($id && $id != 0) {
    // Actualizar registro existente
    foreach ($changes as $change) {
        $tooth_id = $change['tooth_id'] ?? '';
        $intervention_type = $change['intervention_type'] ?? '';
        $option_id = $change['option_id'] ?? '';
        $svg_style = $change['svg_style'] ?? '';
        $code = $change['code'] ?? '';
        $notes = $change['notes'] ?? '';
        $draw_d = $change['draw_d'] ?? '';
        $draw_style = $change['draw_style'] ?? '';
        $date = $change['date'] ?? date('Y-m-d H:i:s');

        if (empty($tooth_id) || empty($intervention_type) || empty($option_id)) {
            $success = false;
            continue;
        }

        $sql = "UPDATE form_odontogram_history SET intervention_type=?, option_id=?, svg_style=?, date=?, code=?, notes=?, draw_d=?, draw_style=?, user=?, groupname=?, authorized=?, activity=? WHERE id=?";
        $params = [$intervention_type, $option_id, $svg_style, $date, $code, $notes, $draw_d, $draw_style, $user, $groupname, $authorized, $activity, $id];
        try {
            sqlStatement($sql, $params);
        } catch (Exception $e) {
            $success = false;
            error_log("save.php - Error updating: " . $e->getMessage());
        }
    }
    $last_history_id = $id;
} else {
    // Insertar nuevo registro
    foreach ($changes as $change) {
        $tooth_id = $change['tooth_id'] ?? '';
        $intervention_type = $change['intervention_type'] ?? '';
        $option_id = $change['option_id'] ?? '';
        $svg_style = $change['svg_style'] ?? '';
        $code = $change['code'] ?? '';
        $notes = $change['notes'] ?? '';
        $draw_d = $change['draw_d'] ?? '';
        $draw_style = $change['draw_style'] ?? '';
        $date = $change['date'] ?? date('Y-m-d H:i:s');

        if (empty($tooth_id) || empty($intervention_type) || empty($option_id)) {
            $success = false;
            continue;
        }

        // Obtener odontogram_id basado en tooth_id
        $odontogram_id = sqlQuery("SELECT id FROM form_odontogram WHERE tooth_id = ?", [$tooth_id])['id'] ?? null;
        if (!$odontogram_id) {
            $success = false;
            error_log("save.php - Error: No odontogram_id found for tooth_id: $tooth_id");
            continue;
        }

        $sql = "INSERT INTO form_odontogram_history (pid, encounter, odontogram_id, tooth_id, intervention_type, option_id, svg_style, date, code, notes, draw_d, draw_style, user, groupname, authorized, activity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$pid, $encounter, $odontogram_id, $tooth_id, $intervention_type, $option_id, $svg_style, $date, $code, $notes, $draw_d, $draw_style, $user, $groupname, $authorized, $activity];

        try {
            $history_id = sqlInsert($sql, $params);
            if ($history_id) {
                $last_history_id = $history_id;
            } else {
                $success = false;
            }
        } catch (Exception $e) {
            $success = false;
            error_log("save.php - Error inserting: " . $e->getMessage());
        }
    }

    if ($success && $last_history_id) {
        addForm($encounter, "Odontogram", $last_history_id, "odontogram", $pid, $authorized);
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