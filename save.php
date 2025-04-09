<?php
ob_start();
require_once("../../globals.php");
require_once("$srcdir/forms.inc");
require_once("$srcdir/patient.inc");

use OpenEMR\Common\Csrf\CsrfUtils;

error_log("save.php - Iniciando procesamiento");

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !is_array($data)) {
    error_log("save.php - Error: Datos inválidos o no recibidos");
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('Invalid or no data received')]);
    exit;
}

error_log("save.php - Datos recibidos: " . json_encode($data));
error_log("save.php - CSRF Token recibido: " . ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? 'No recibido'));
if (!CsrfUtils::verifyCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    error_log("save.php - Error: Fallo en la verificación CSRF");
    CsrfUtils::csrfNotVerified();
}

$changes = $data['changes'] ?? [];
if (empty($changes)) {
    error_log("save.php - Error: No hay cambios para guardar");
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

error_log("save.php - Contexto: pid=$pid, encounter=$encounter, user=$user");

if (!$pid || !$encounter) {
    error_log("save.php - Error: Falta contexto de paciente o encuentro");
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('Missing patient or encounter context')]);
    exit;
}

$success = true;
$last_history_id = null;
$id = (int) (isset($_GET['id']) ? $_GET['id'] : 0);

error_log("save.php - ID recibido: $id");

if ($id && $id != 0) {
    // Actualizar registro existente
    foreach ($changes as $change) {
        $tooth_id = $change['tooth_id'] ?? '';
        $intervention_type = $change['intervention_type'] ?? '';
        $option_id = $change['option_id'] ?? '';
        $svg_style = $change['svg_style'] ?? '';
        $code = $change['code'] ?? '';
        $notes = $change['notes'] ?? '';
        $date = $change['date'] ?? date('Y-m-d H:i:s');

        if (empty($tooth_id) || empty($intervention_type) || empty($option_id)) {
            $success = false;
            error_log("save.php - Error: Campos requeridos vacíos para tooth_id: $tooth_id");
            continue;
        }

        $sql = "UPDATE form_odontogram_history SET intervention_type=?, option_id=?, svg_style=?, date=?, code=?, notes=?, user=?, groupname=?, authorized=?, activity=? WHERE id=?";
        $params = [$intervention_type, $option_id, $svg_style, $date, $code, $notes, $user, $groupname, $authorized, $activity, $id];
        try {
            sqlStatement($sql, $params);
            error_log("save.php - Actualización exitosa para id: $id");
        } catch (Exception $e) {
            $success = false;
            error_log("save.php - Error actualizando: " . $e->getMessage());
        }
    }
    $last_history_id = $id;
} else {
    // Insertar nuevo registro
    foreach ($changes as $change) {
        $tooth_id = $change['tooth_id'] ?? '';
        $intervention_type = $change['intervention_type'] ?? '';
        $svg_style = $change['svg_style'] ?? '';
        $code = $change['code'] ?? '';
        $notes = $change['notes'] ?? '';
        $date = $change['date'] ?? date('Y-m-d H:i:s');

        if (empty($tooth_id) || empty($intervention_type) || empty($option_id)) {
            $success = false;
            error_log("save.php - Error: Campos requeridos vacíos para tooth_id: $tooth_id");
            continue;
        }

        $odontogram_id = sqlQuery("SELECT id FROM form_odontogram WHERE tooth_id = ?", [$tooth_id])['id'] ?? null;
        error_log("save.php - odontogram_id para tooth_id $tooth_id: " . ($odontogram_id ?? 'No encontrado'));

        if (!$odontogram_id) {
            $success = false;
            error_log("save.php - Error: No se encontró odontogram_id para tooth_id: $tooth_id");
            continue;
        }

        $sql = "INSERT INTO form_odontogram_history (pid, encounter, odontogram_id, intervention_type, tooth_id, svg_style, date, code, notes, user, groupname, authorized, activity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$pid, $encounter, $odontogram_id, $intervention_type, $tooth_id, $svg_style, $date, $code, $notes, $user, $groupname, $authorized, $activity];

        try {
            $history_id = sqlInsert($sql, $params);
            if ($history_id) {
                $last_history_id = $history_id;
                error_log("save.php - Inserción exitosa, history_id: $history_id");
            } else {
                $success = false;
                error_log("save.php - Error: No se generó history_id para tooth_id: $tooth_id");
            }
        } catch (Exception $e) {
            $success = false;
            error_log("save.php - Error insertando: " . $e->getMessage());
        }
    }

    if ($success && $last_history_id) {
        error_log("save.php - Registrando formulario con ID: $last_history_id");
        addForm($encounter, "Odontogram", $last_history_id, "odontogram", $pid, $authorized);
    }
}

ob_end_clean();
header('Content-Type: application/json');
if ($success) {
    error_log("save.php - Respuesta exitosa enviada");
    echo json_encode(['success' => true, 'id' => $last_history_id]);
} else {
    error_log("save.php - Respuesta de error enviada");
    http_response_code(500);
    echo json_encode(['error' => xl('Failed to save some changes')]);
}
exit;