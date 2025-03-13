<?php
ob_start();
require_once("../../globals.php");
require_once("$srcdir/forms.inc.php");

$pid = $_SESSION['pid'] ?? 0;
$encounter = $_SESSION['encounter'] ?? 0;
$user = $_SESSION['authUser'] ?? '';
$groupname = $_SESSION['authGroup'] ?? '';
$authorized = $_SESSION['userauthorized'] ?? 0;

if (!$pid || !$encounter) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('Missing patient or encounter context')]);
    exit;
}

// Verificar si ya existe un formulario en forms para este encuentro
$existing_form = sqlQuery("SELECT id, form_id FROM forms WHERE encounter = ? AND formdir = 'odontogram' AND deleted = 0", [$encounter]);
$existing_form_id = $existing_form['id'] ?? null;
$form_id = $existing_form['form_id'] ?? null;

if (!$existing_form_id) {
    // Generar un nuevo form_id con sequences
    $form_id = generate_id();

    // Registrar el formulario en la tabla forms
    sqlInsert(
        "INSERT INTO forms (date, encounter, form_name, form_id, pid, user, groupname, authorized, formdir) 
         VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, 'odontogram')",
        [$encounter, "Odontogram", $form_id, $pid, $user, $groupname, $authorized]
    );
}

ob_end_clean();
header('Content-Type: application/json');
echo json_encode(['success' => true, 'form_id' => $form_id]);
exit;