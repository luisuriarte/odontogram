<?php
require_once("../../../globals.php");
require_once("$srcdir/forms.inc.php");

$encounter = $_POST['encounter'] ?? '';
$pid = $_POST['pid'] ?? '';
$userauthorized = $_POST['userauthorized'] ?? 0;
$form_id = $_POST['form_id'] ?? 0;

if (!$encounter || !$pid || !$form_id) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

addForm($encounter, "Odontogram", $form_id, "odontogram", $pid, $userauthorized);
echo json_encode(['success' => true]);
exit;