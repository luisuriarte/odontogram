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

$system_preference = sqlQuery("SELECT odontogram_preference FROM users WHERE id = ?", [$user_id])['odontogram_preference'] ?? 'FDI';
$result = sqlQuery("SELECT name, universal, fdi, palmer, part, arc, side, svg_type, x, y, width, height, d 
                    FROM form_odontogram WHERE tooth_id = ?", [$tooth_id]);

if ($result) {
    $number = '';
    $system = $system_preference;
    $palmer_symbol = '';
    $palmer_number = '';

    switch (strtolower($system_preference)) {
        case 'universal':
            $number = $result['universal'];
            break;
        case 'fdi':
            $number = $result['fdi'];
            break;
        case 'palmer':
            $palmer_value = $result['palmer'];
            $symbols = ['⏋', '⎿', '⏌', '┌'];
            foreach ($symbols as $symbol) {
                if (strpos($palmer_value, $symbol) === 0) {
                    $palmer_symbol = $symbol;
                    $palmer_number = substr($palmer_value, strlen($symbol));
                    break;
                } elseif (strrpos($palmer_value, $symbol) === strlen($palmer_value) - strlen($symbol)) {
                    $palmer_symbol = $symbol;
                    $palmer_number = substr($palmer_value, 0, -strlen($symbol));
                    break;
                }
            }
            if (empty($palmer_symbol)) {
                $palmer_number = $palmer_value;
            }
            $number = $palmer_number;
            break;
        default:
            $system = 'FDI';
            $number = $result['fdi'];
    }

    // Validar coordenadas
    $svg_type = $result['svg_type'] ?? '';
    $x = floatval($result['x'] ?? 0);
    $y = floatval($result['y'] ?? 0);
    $width = floatval($result['width'] ?? 0);
    $height = floatval($result['height'] ?? 0);
    $d = $result['d'] ?? '';

    if ($svg_type === 'rect' && ($width <= 0 || $height <= 0)) {
        error_log("Invalid rect dimensions for $tooth_id: width=$width, height=$height");
    }
    if ($svg_type === 'path' && empty($d)) {
        error_log("Missing d for path $tooth_id");
    }

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
        'palmer_symbol' => $palmer_symbol,
        'svg_type' => $svg_type,
        'x' => $x,
        'y' => $y,
        'width' => $width,
        'height' => $height,
        'd' => $d
    ];
    error_log("Response sent: " . json_encode($response, JSON_UNESCAPED_UNICODE));
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'Tooth not found', 'tooth_id' => $tooth_id], JSON_UNESCAPED_UNICODE);
}

exit;
?>