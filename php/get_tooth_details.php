<?php
require_once '../../../globals.php';
require_once "$srcdir/sql.inc.php";

header('Content-Type: application/json; charset=UTF-8');

$tooth_id = $_POST['tooth_id'] ?? '';
$user_id = $_POST['user_id'] ?? '';
error_log("get_tooth_details.php - Received POST: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));

if (empty($tooth_id)) {
    echo json_encode(['error' => 'Missing tooth_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($user_id)) {
    echo json_encode(['error' => 'Missing user_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtener la preferencia del usuario desde la tabla users
$system_preference = sqlQuery("SELECT odontogram_preference FROM users WHERE id = ?", [$user_id])['odontogram_preference'] ?? 'FDI';

// Obtener detalles del diente
$result = sqlQuery("SELECT name, universal, fdi, palmer, part, arc, side FROM form_odontogram WHERE tooth_id = ?", [$tooth_id]);

if ($result) {
    // Determinar el número y sistema según la preferencia
    $number = '';
    $system = $system_preference;
    $palmer_symbol = ''; // Para el símbolo en Palmer
    $palmer_number = ''; // Para el número en Palmer

    switch (strtolower($system_preference)) {
        case 'universal':
            $number = $result['universal'];
            break;
        case 'fdi':
            $number = $result['fdi'];
            break;
        case 'palmer':
            // Separar número y símbolo en Palmer (ej. "8⏋" -> "8" y "⏋")
            $palmer_value = $result['palmer'];
            if (preg_match('/^([0-8])(.*)$/', $palmer_value, $matches)) {
                $palmer_number = $matches[1]; // Número (0-8)
                $palmer_symbol = $matches[2]; // Símbolo (⏋ o similar)
            } elseif (preg_match('/^([A-E])(.*)$/', $palmer_value, $matches)) {
                $palmer_number = $matches[1]; // Letra (A-E para infantiles)
                $palmer_symbol = $matches[2]; // Símbolo
            } else {
                $palmer_number = $palmer_value; // Si no hay símbolo, todo es número
            }
            $number = $palmer_number; // Para compatibilidad con el frontend
            break;
        default:
            $system = 'FDI';
            $number = $result['fdi'];
    }

    // Construir respuesta
    $response = [
        'name' => $result['name'],
        'universal' => $result['universal'],
        'fdi' => $result['fdi'],
        'palmer' => $result['palmer'],
        'part' => $result['part'],
        'arc' => $result['arc'],
        'side' => $result['side'],
        'system' => strtoupper($system),
        'number' => $number,
        'palmer_symbol' => $palmer_symbol // Nuevo campo para el símbolo
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'Tooth not found', 'tooth_id' => $tooth_id], JSON_UNESCAPED_UNICODE);
}

exit;
?>