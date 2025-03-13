<?php
ob_start();
require_once("../../../globals.php");

$start = $_POST['start'] ?? date('Y-m-d', strtotime('-10 years'));
$end = $_POST['end'] ?? date('Y-m-d');
$filters = $_POST['filters'] ?? ['Diagnosis', 'Issue', 'Procedure'];

$pid = $_SESSION['pid'] ?? 0;
$encounter = $_SESSION['encounter'] ?? 0;

if (!$pid || !$encounter) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => xl('Missing patient or encounter context')]);
    exit;
}

$sql = "SELECT h.id, h.patient_id, h.encounter, h.odontogram_id, h.intervention_type, h.option_id, h.date, h.symbol, h.code, h.description, h.notes, h.user, h.groupname, h.authorized, h.activity, o.svg_id 
        FROM form_odontogram_history h
        LEFT JOIN form_odontogram o ON h.odontogram_id = o.id
        WHERE h.patient_id = ? AND h.encounter = ? AND h.date BETWEEN ? AND ? 
        AND h.intervention_type IN (" . implode(',', array_fill(0, count($filters), '?')) . ")";
$params = array_merge([$pid, $encounter, $start, $end], $filters);

try {
    $result = sqlStatement($sql, $params);
    $history = [];

    while ($row = sqlFetchArray($result)) {
        $history[] = [
            'tooth_id' => $row['svg_id'],
            'intervention_type' => $row['intervention_type'],
            'option_id' => $row['option_id'],
            'symbol' => $row['symbol'],
            'code' => $row['code'],
            'description' => $row['description'],
            'notes' => $row['notes'],
            'user' => $row['user'],
            'groupname' => $row['groupname'],
            'authorized' => $row['authorized'],
            'activity' => $row['activity']
        ];
    }

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($history);
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => xl('Database error'), 'details' => $e->getMessage()]);
}
exit;
