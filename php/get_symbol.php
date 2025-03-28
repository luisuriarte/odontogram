<?php
require_once '../../../globals.php';

$symbol = $_GET['symbol'] ?? '';
$symbolPath = dirname(__FILE__) . "/../assets/symbols/$symbol";

error_log("Attempting to load: $symbolPath"); // Añade esto para depurar
if (file_exists($symbolPath)) {
    header('Content-Type: image/svg+xml');
    readfile($symbolPath);
} else {
    header('HTTP/1.0 404 Not Found');
    error_log("File not found: $symbolPath"); // Log del error
    echo "Symbol not found: $symbol";
}
?>