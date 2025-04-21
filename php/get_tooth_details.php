<?php
require_once("../../../globals.php");

use OpenEMR\Common\Csrf\CsrfUtils;

// Forzar JSON como tipo de contenido
header('Content-Type: application/json');

try {
    if (!CsrfUtils::verifyCsrfToken($_REQUEST["csrf_token_form"])) {
        echo json_encode(['error' => xl('Authentication failed')]);
        exit;
    }

    $tooth_id = $_REQUEST['tooth_id'] ?? '';
    if (empty($tooth_id)) {
        echo json_encode(['error' => xl('Tooth ID is missing')]);
        exit;
    }

    $result = sqlQuery("SELECT name, part, universal, fdi, palmer, tooth_id, style FROM form_odontogram WHERE tooth_id = ?", [$tooth_id]);

    if ($result) {
        $response = [
            'tooth_id' => $tooth_id,
            'name' => $result['name'] ?? 'Unknown',
            'part' => $result['part'] ?? 'Unknown',
            'fdi' => $result['fdi'] ?? 'N/A',
            'palmer' => $result['palmer'] ?? 'N/A',
            'universal' => $result['universal'] ?? 'N/A',
            'style' => $result['style'] ?? 'fill: none'
        ];
    } else {
        $response = [
            'tooth_id' => $tooth_id,
            'name' => 'Unknown',
            'part' => 'Unknown',
            'fdi' => 'N/A',
            'palmer' => 'N/A',
            'universal' => 'N/A',
            'style' => 'fill: none'
        ];
    }

    echo json_encode($response);
} catch (Exception $e) {
    error_log("Error in get_tooth_details.php: " . $e->getMessage());
    echo json_encode(['error' => xl('Server error occurred')]);
}
?>