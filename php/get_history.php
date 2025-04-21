<?php
require_once("../../../globals.php");

use OpenEMR\Common\Csrf\CsrfUtils;

header('Content-Type: application/json');

try {
    if (!CsrfUtils::verifyCsrfToken($_REQUEST["csrf_token_form"])) {
        echo json_encode(['error' => xl('Authentication failed')]);
        exit;
    }

    $pid = $_REQUEST['pid'] ?? 0;
    $start = $_REQUEST['start'] ?? '';
    $end_date = $_REQUEST['end_date'] ?? '';
    $intervention_types = $_REQUEST['intervention_types'] ?? '';
    $tooth_id = $_REQUEST['tooth_id'] ?? '';

    if (empty($pid)) {
        echo json_encode(['error' => xl('Invalid patient ID')]);
        exit;
    }

    $where = [];
    $params = [$pid];

    if (!empty($tooth_id)) {
        $where[] = "tooth_id = ?";
        $params[] = $tooth_id;
    }

    if (!empty($start) && !empty($end_date)) {
        $where[] = "DATE(date) BETWEEN ? AND ?";
        $params[] = $start;
        $params[] = $end_date;
    }

    if ($intervention_types !== '') {
        $types = array_map('trim', explode(',', $intervention_types));
        if (!empty($types)) {
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            $where[] = "intervention_type IN ($placeholders)";
            $params = array_merge($params, $types);
        } else {
            $where[] = "1 = 0";
        }
    }

    $sql = "SELECT id, date, pid, encounter, intervention_type, tooth_id, option_id, code, notes, svg_style
            FROM form_odontogram_history
            WHERE pid = ? AND activity = 1";
    if (!empty($where)) {
        $sql .= " AND " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY date DESC";

    error_log("SQL Query: " . $sql . " | Params: " . json_encode($params));

    $result = sqlStatement($sql, $params);
    $history = [];
    while ($row = sqlFetchArray($result)) {
        // Validar datos para evitar problemas con JSON
        $row['notes'] = mb_convert_encoding($row['notes'] ?? '', 'UTF-8', 'UTF-8');
        $row['svg_style'] = mb_convert_encoding($row['svg_style'] ?? '', 'UTF-8', 'UTF-8');
        $history[] = [
            'id' => $row['id'],
            'date' => $row['date'],
            'pid' => $row['pid'],
            'encounter' => $row['encounter'],
            'intervention_type' => $row['intervention_type'],
            'tooth_id' => $row['tooth_id'],
            'option_id' => $row['option_id'],
            'code' => $row['code'],
            'notes' => $row['notes'],
            'svg_style' => $row['svg_style']
        ];
    }

    error_log("History result count: " . count($history));

    // Verificar codificación JSON
    $json = json_encode($history);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON encoding error: " . json_last_error_msg());
        echo json_encode(['error' => xl('Failed to encode history data')]);
        exit;
    }

    echo $json;
} catch (Exception $e) {
    error_log("Error in get_history.php: " . $e->getMessage());
    echo json_encode(['error' => xl('Server error occurred')]);
}
?>