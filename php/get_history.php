<?php
require_once("../../../globals.php");
require_once("$srcdir/sql.inc.php");

$start = $_POST['start'] ?? '';
$end = $_POST['end'] ?? '';
$encounter = $_POST['encounter'] ?? 0;
$filters = $_POST['filters'] ?? [];

$sql = "SELECT h.tooth_id, h.intervention_type, h.option_id, h.svg_style, h.date, h.code, h.notes, o.style AS original_style 
        FROM form_odontogram_history h
        LEFT JOIN form_odontogram o ON h.tooth_id = o.tooth_id
        WHERE h.pid = ? AND h.encounter = ?";
$params = [$_SESSION['pid'], $encounter];

if ($start) {
    $sql .= " AND h.date >= ?";
    $params[] = $start . " 00:00:00";
}
if ($end) {
    $sql .= " AND h.date <= ?";
    $params[] = $end . " 23:59:59";
}
if (!empty($filters)) {
    $sql .= " AND h.intervention_type IN (" . implode(',', array_fill(0, count($filters), '?')) . ")";
    $params = array_merge($params, $filters);
}

$sql .= " ORDER BY h.date ASC";

$result = sqlStatement($sql, $params);
$history = [];
while ($row = sqlFetchArray($result)) {
    $history[] = $row;
}

header('Content-Type: application/json');
echo json_encode($history);
exit;