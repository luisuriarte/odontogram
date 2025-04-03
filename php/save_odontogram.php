<?php
require_once '../../../globals.php';
require_once "$srcdir/sql.inc.php";

header('Content-Type: application/json; charset=UTF-8');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !is_array($data)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$responses = [];
foreach ($data as $change) {
    $tooth_id = $change['tooth_id'] ?? ''; // Este es el tooth_id del SVG (ej. U11)
    $intervention_type = $change['intervention_type'] ?? '';
    $list_id = $change['list_id'] ?? '';
    $option_id = $change['option_id'] ?? '';
    $code = $change['code'] ?? null;
    $svg_style = $change['svg_style'] ?? null;
    $draw_d = $change['draw_d'] ?? null;
    $draw_style = $change['draw_style'] ?? null;
    $notes = $change['notes'] ?? null;
    $pid = $change['pid'] ?? 0;
    $encounter = $change['encounter'] ?? 0;
    $user = $change['user'] ?? '';
    $date = $change['date'] ?? date('Y-m-d H:i:s');

    if (empty($tooth_id) || empty($intervention_type) || empty($list_id) || empty($option_id)) {
        $responses[] = ['tooth_id' => $tooth_id, 'success' => false, 'error' => 'Missing required fields'];
        continue;
    }

    // Obtener el odontogram_id desde form_odontogram usando tooth_id
    $toothQuery = "SELECT id FROM form_odontogram WHERE tooth_id = ?";
    $toothResult = sqlQuery($toothQuery, [$tooth_id]);
    $odontogram_id = $toothResult['id'] ?? null;

    if (!$odontogram_id) {
        $responses[] = ['tooth_id' => $tooth_id, 'success' => false, 'error' => 'Tooth not found in form_odontogram'];
        continue;
    }

    $query = "INSERT INTO form_odontogram_history (
        date, pid, encounter, user, odontogram_id, intervention_type, list_id, option_id, code,
        svg_style, draw_d, draw_style, notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $result = sqlStatement($query, [
        $date, $pid, $encounter, $user, $odontogram_id, $intervention_type, $list_id, $option_id, $code,
        $svg_style, $draw_d, $draw_style, $notes
    ]);

    $id = sqlInsertId();
    $responses[] = ['tooth_id' => $tooth_id, 'success' => true, 'id' => $id];
}

echo json_encode(['success' => true, 'responses' => $responses]);
exit;