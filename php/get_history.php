<?php
require_once '../../../globals.php';
require_once "$srcdir/sql.inc.php";

$pid = $_SESSION['pid'] ?? 0;
$encounter = $_SESSION['encounter'] ?? 0;
$start = $_POST['start'] ?? date('Y-m-d', strtotime('-10 years'));
$end = $_POST['end'] ?? date('Y-m-d');
$filters = $_POST['filters'] ?? array('odonto_diagnosis', 'odonto_issue', 'odonto_procedures');

if (!$pid || !$encounter) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing patient ID or encounter']);
    exit;
}

error_log("get_history.php - PID: $pid, Encounter: $encounter, Start: $start, End: $end, Filters: " . json_encode($filters));

$history = array();
$query = "SELECT id, pid, encounter, odontogram_id, intervention_type, option_id, date, symbol, code, description, notes 
          FROM form_odontogram_history 
          WHERE pid = ? AND encounter = ? AND date BETWEEN ? AND ? 
          AND intervention_type IN (" . implode(',', array_fill(0, count($filters), '?')) . ")";
$params = array_merge([$pid, $encounter, $start, $end], $filters);

$result = sqlStatement($query, $params);
while ($row = sqlFetchArray($result)) {
    $history[] = $row;
}

error_log("History found: " . json_encode($history));
header('Content-Type: application/json');
echo json_encode($history);
?>