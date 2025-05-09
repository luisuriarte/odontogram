<?php
require_once(__DIR__ . "/../../../globals.php");
require_once("$srcdir/translation.inc.php");

use OpenEMR\Common\Csrf\CsrfUtils;

if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"])) {
    CsrfUtils::csrfNotVerified();
}

$user = $_SESSION['authUser'];
$format = $_POST['format'] ?? '';

if (!in_array($format, ['Universal', 'FDI', 'Palmer'])) {
    echo json_encode(['success' => false, 'message' => xlt('Invalid numbering format')]);
    exit;
}

sqlStatement("UPDATE users SET odontogram_preference = ? WHERE username = ?", [$format, $user]);
echo json_encode(['success' => true, 'message' => xlt('Preference updated')]);
?>