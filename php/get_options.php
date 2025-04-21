<?php
require_once("../../../globals.php");
require_once("$srcdir/translation.inc.php");

$lists = ['odonto_diagnosis', 'odonto_issue', 'odonto_procedures'];
$options = [];

foreach ($lists as $list) {
    $result = sqlStatement("SELECT option_id, title, codes, notes FROM list_options WHERE list_id = ? AND activity = 1 ORDER BY seq", [$list]);
    while ($row = sqlFetchArray($result)) {
        $options[$list][] = [
            'option_id' => $row['option_id'],
            'title' => xl($row['title']),
            'codes' => $row['codes'],
            'notes' => $row['notes']
        ];
    }
}

echo json_encode($options);
?>