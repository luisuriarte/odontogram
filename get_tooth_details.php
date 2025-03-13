<?php
require_once("../../globals.php");

if (isset($_POST['svg_id']) && isset($_POST['user_id'])) {
    $svgId = $_POST['svg_id'];
    $userId = $_POST['user_id'];

    // Obtener preferencia de numeración
    $sql = "SELECT odontogram_preferences FROM users WHERE id = ?";
    $userResult = sqlQuery($sql, array($userId));
    $system = $userResult['odontogram_preferences'] ?? 'FDI';

    // Obtener detalles del diente
    $sql = "SELECT name, universal, fdi, palmer, part, arc, side FROM form_odontogram WHERE svg_id = ?";
    $result = sqlQuery($sql, array($svgId));

    if ($result) {
        $number = ($system === 'Universal') ? $result['universal'] : (($system === 'FDI') ? $result['fdi'] : $result['palmer']);
        $response = [
            'name' => $result['name'],
            'system' => $system,
            'number' => $number,
            'part' => $result['part'],
            'arc' => $result['arc'],
            'side' => $result['side']
        ];
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Diente no encontrado']);
    }
}
?>