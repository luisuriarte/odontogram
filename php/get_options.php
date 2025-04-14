<?php
ob_start();
require_once("../../../globals.php");
header('Content-Type: application/json');

$interventionType = $_POST['type'] ?? '';

$listIds = [
    'diagnosis' => 'odonto_diagnosis',
    'issue' => 'odonto_issue',
    'procedure' => 'odonto_procedures'
];

$listId = $listIds[strtolower($interventionType)] ?? '';
if (empty($listId)) {
    ob_end_clean();
    echo json_encode(['error' => xl('Invalid or missing intervention type')]);
    exit;
}

$sql = "SELECT option_id, title, notes AS style, codes 
        FROM list_options 
        WHERE list_id = ? 
        ORDER BY title";
$result = sqlStatement($sql, [$listId]);
$options = [];

while ($row = sqlFetchArray($result)) {
    $row['title'] = xl($row['title']);
    $row['style'] = $row['style'] ?: ''; // Asegurar que el color no sea null
    $options[] = $row;
}

if (empty($options)) {
    ob_end_clean();
    echo json_encode(['error' => xl('No options found for') . ' ' . htmlspecialchars($listId)]);
    exit;
}

ob_end_clean();
echo json_encode($options);
exit;