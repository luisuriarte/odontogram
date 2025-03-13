<?php
// Iniciar buffer para evitar salida no deseada
ob_start();

// Cargar globals.php para definir $GLOBALS y funciones de OpenEMR
require_once("../../../globals.php");

// Establecer encabezado JSON
header('Content-Type: application/json');

// Obtener el tipo de intervención desde POST
$interventionType = $_POST['type'] ?? '';

// Mapear tipo de intervención a list_id
$listIds = [
    'diagnosis' => 'odonto_diagnosis',
    'issue' => 'odonto_issue',
    'procedure' => 'odonto_procedures'
];

// Validar entrada y asignar list_id
$listId = $listIds[$interventionType] ?? '';
if (empty($listId)) {
    ob_end_clean();
    echo json_encode(['error' => xl('Invalid or missing intervention type')]);
    exit;
}

// Consulta SQL ajustada a tu estructura
$sql = "SELECT option_id, title, notes AS symbol, codes 
        FROM list_options 
        WHERE list_id = ? 
        ORDER BY option_id"; // Ordenamos por option_id ya que seq no se usa
$result = sqlStatement($sql, [$listId]);
$options = [];

while ($row = sqlFetchArray($result)) {
    // Traducir el título con xl()
    $row['title'] = xl($row['title']);
    $options[] = $row;
}

// Verificar si hay resultados
if (empty($options)) {
    ob_end_clean();
    echo json_encode(['error' => xl('No options found for') . ' ' . htmlspecialchars($listId)]);
    exit;
}

// Enviar respuesta JSON
ob_end_clean();
echo json_encode($options);
exit;