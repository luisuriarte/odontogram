<?php
require_once(__DIR__ . "/../../globals.php");
require_once("$srcdir/forms.inc.php");
require_once("$srcdir/translation.inc.php");

use OpenEMR\Common\Csrf\CsrfUtils;

if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"])) {
    CsrfUtils::csrfNotVerified();
}

$encounter = $_SESSION['encounter'];
$pid = $_SESSION['pid'];
$user = $_SESSION['authUser'];
$groupname = $_SESSION['authGroup'];
$authorized = 1;
$activity = 1;
$formid = (int) ($_POST['formid'] ?? 0);

if (!$encounter) {
    echo json_encode(['success' => false, 'message' => xlt('No encounter specified')]);
    exit;
}

$interventions = json_decode($_POST['interventions'], true);
if (empty($interventions)) {
    echo json_encode(['success' => false, 'message' => xlt('No interventions provided')]);
    exit;
}

sqlBeginTrans();
$insertedIds = [];

foreach ($interventions as $intervention) {
    $tooth_id = $intervention['tooth_id'];
    $intervention_type = $intervention['intervention_type'];
    $option_id = $intervention['option_id'];
    $code = $intervention['code'];
    $notes = $intervention['notes'];
    $svg_style = $intervention['svg_style'];

    // Validar tooth_id
    $toothCheck = sqlQuery("SELECT id FROM form_odontogram WHERE tooth_id = ?", [$tooth_id]);
    if (empty($toothCheck)) {
        sqlRollbackTrans();
        echo json_encode(['success' => false, 'message' => xlt('Invalid tooth ID') . ": $tooth_id"]);
        exit;
    }
    $odontogram_id = $toothCheck['id'];

    // Validar option_id
    $list_id = 'odonto_' . strtolower($intervention_type);
    $optionCheck = sqlQuery("SELECT option_id, notes FROM list_options WHERE list_id = ? AND option_id = ?", [$list_id, $option_id]);
    if (empty($optionCheck)) {
        sqlRollbackTrans();
        echo json_encode(['success' => false, 'message' => xlt('Invalid option') . ": $option_id"]);
        exit;
    }

    $query = "INSERT INTO form_odontogram_history (date, pid, encounter, user, groupname, authorized, activity, odontogram_id, intervention_type, tooth_id, option_id, code, notes, svg_style) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $bind = [$pid, $encounter, $user, $groupname, $authorized, $activity, $odontogram_id, $intervention_type, $tooth_id, $option_id, $code, $notes, $svg_style];
    $newid = sqlInsert($query, $bind);
    $insertedIds[] = $newid;

    // Registrar formulario en OpenEMR
    if ($formid == 0) {
        $formid = addForm($encounter, "Odontogram", $newid, "odontogram", $pid, $user, $groupname, $authorized);
    }
}

sqlCommitTrans();
echo json_encode(['success' => true, 'message' => xlt('Interventions saved successfully'), 'ids' => $insertedIds, 'formid' => $formid]);
?>