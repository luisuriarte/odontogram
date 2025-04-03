<?php
require_once '../../../globals.php';
require_once "$srcdir/sql.inc.php";

header('Content-Type: application/json; charset=UTF-8');

$start = $_POST['start'] ?? date('Y-m-d', strtotime('-10 years'));
$end = $_POST['end'] ?? date('Y-m-d');
$encounter = $_POST['encounter'] ?? 0;
$filters = $_POST['filters'] ?? ['Diagnosis', 'Issue', 'Procedure'];

$query = "SELECT odontogram_id, intervention_type, list_id, option_id, code, svg_style, draw_d, draw_style, notes 
          FROM form_odontogram_history 
          WHERE date BETWEEN ? AND ? AND encounter = ? 
          AND intervention_type IN (" . implode(',', array_fill(0, count($filters), '?')) . ")";
$params = [$start . ' 00:00:00', $end . ' 23:59:59', $encounter];
$params = array_merge($params, $filters);

try {
    $result = sqlStatement($query, $params);

    $history = [];
    while ($row = sqlFetchArray($result)) {
        // Obtener el tooth_id correspondiente desde form_odontogram
        $toothQuery = "SELECT tooth_id FROM form_odontogram WHERE id = ?";
        $toothResult = sqlQuery($toothQuery, [$row['odontogram_id']]);
        $toothId = $toothResult['tooth_id'] ?? null;

        $history[] = [
            'tooth_id' => $toothId, // Devolvemos tooth_id para el frontend
            'odontogram_id' => $row['odontogram_id'],
            'intervention_type' => $row['intervention_type'],
            'list_id' => $row['list_id'],
            'option_id' => $row['option_id'],
            'code' => $row['code'],
            'svg_style' => $row['svg_style'],
            'draw_d' => $row['draw_d'],
            'draw_style' => $row['draw_style'],
            'notes' => $row['notes']
        ];
    }

    echo json_encode($history);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

exit;