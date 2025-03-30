<?php
require_once '../../../globals.php';
require_once "$srcdir/sql.inc.php";

$pid = $_SESSION['pid'] ?? 0;
$encounter = $_POST['encounter'] ?? $_SESSION['encounter'] ?? 0;
$start = $_POST['start'] ?? date('Y-m-d', strtotime('-10 years'));
$end = $_POST['end'] ?? date('Y-m-d');
$filters = $_POST['filters'] ?? array('Diagnosis', 'Issue', 'Procedure');

if (!$pid || !$encounter) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing patient ID or encounter']);
    exit;
}

error_log("get_history.php - PID: $pid, Encounter: $encounter, Start: $start, End: $end, Filters: " . json_encode($filters));

$history = array();
$query = "SELECT h.id, h.pid, h.encounter, h.odontogram_id, h.intervention_type, h.option_id, h.date, h.symbol, h.code, h.description, h.notes, o.tooth_id AS tooth_id 
          FROM form_odontogram_history h
          LEFT JOIN form_odontogram o ON h.odontogram_id = o.id
          WHERE h.pid = ? AND h.encounter = ? AND h.date BETWEEN ? AND ? 
          AND h.intervention_type IN (" . implode(',', array_fill(0, count($filters), '?')) . ")";
$params = array_merge([$pid, $encounter, $start, $end], $filters);

error_log("Query: $query, Params: " . json_encode($params));

try {
    $result = sqlStatement($query, $params);
    while ($row = sqlFetchArray($result)) {
        $history[] = $row;
    }
    error_log("History found: " . json_encode($history));
} catch (Exception $e) {
    error_log("get_history.php - SQL Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database query failed', 'details' => $e->getMessage()]);
    exit;
}

header('Content-Type: application/json');
echo json_encode($history);
?>