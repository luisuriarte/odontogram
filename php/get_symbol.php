<?php
require_once("../../../globals.php"); // Carga la autenticación y configuración de OpenEMR

// Obtener el nombre del archivo SVG desde la solicitud GET
$symbol = $_GET['symbol'] ?? '';

// Validar entrada para evitar accesos no deseados
if (empty($symbol) || !preg_match('/^[a-zA-Z0-9_-]+\.svg$/', $symbol)) {
    http_response_code(400);
    exit('Invalid symbol name');
}

// Ruta al archivo SVG
$svgPath = __DIR__ . '../assets/symbols/' . $symbol;

// Verificar que el archivo exista
if (file_exists($svgPath) && is_file($svgPath)) {
    // Establecer encabezados para servir SVG
    header('Content-Type: image/svg+xml');
    header('Content-Length: ' . filesize($svgPath));
    readfile($svgPath);
    exit;
} else {
    http_response_code(404);
    exit('Symbol not found');
}