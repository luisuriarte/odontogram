<?php
// Cargar el entorno de OpenEMR
require_once("../../globals.php");
require_once("$srcdir/forms.inc.php");

// Obtener parámetros esenciales
$encounter = $_SESSION['encounter'] ?? 0;
$pid = $_SESSION['pid'] ?? 0;
$form_id = $_GET['id'] ?? 0; // ID del formulario en la tabla `forms`

// Validar parámetros
if (!$encounter || !$pid || !$form_id) {
    die(xl("Falta el encuentro, paciente o ID del formulario"));
}

// Consultar las intervenciones del odontograma
$interventions = [];
$result = sqlStatement(
    "SELECT h.*, o.svg_id 
     FROM form_odontogram_history h
     LEFT JOIN form_odontogram o ON h.odontogram_id = o.id
     WHERE h.pid = ? AND h.encounter = ?",
    [$pid, $encounter]
);
while ($row = sqlFetchArray($result)) {
    $interventions[] = $row;
}
?>

<html>
<head>
    <title><?php echo xlt('Odontograma'); ?></title>
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot']; ?>/public/assets/bootstrap-5.3.0-dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h2><?php echo xlt('Odontograma'); ?></h2>
        <!-- Contenedor para el SVG del odontograma -->
        <div id="odontogram-svg" style="width: 1048px; height: 704px;"></div>

        <!-- Tabla de intervenciones -->
        <h3><?php echo xlt('Intervenciones'); ?></h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo xlt('Diente'); ?></th>
                    <th><?php echo xlt('Tipo de intervención'); ?></th>
                    <th><?php echo xlt('Opción'); ?></th>
                    <th><?php echo xlt('Símbolo'); ?></th>
                    <th><?php echo xlt('Código'); ?></th>
                    <th><?php echo xlt('Fecha'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($interventions as $intervention) { ?>
                    <tr>
                        <td><?php echo text($intervention['svg_id']); ?></td>
                        <td><?php echo text($intervention['intervention_type']); ?></td>
                        <td><?php echo text($intervention['option_id']); ?></td>
                        <td><?php echo text($intervention['symbol']); ?></td>
                        <td><?php echo text($intervention['code']); ?></td>
                        <td><?php echo text($intervention['date']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Scripts para cargar y manipular el SVG -->
    <script src="<?php echo $GLOBALS['webroot']; ?>/public/assets/jquery-3.6.0/jquery.min.js"></script>
    <script src="<?php echo $GLOBALS['webroot']; ?>/public/assets/svg.js/svg.min.js"></script>
    <script>
        $(document).ready(function() {
            // Crear el lienzo SVG
            var draw = SVG().addTo('#odontogram-svg').size(1048, 704);

            // Cargar el SVG base del odontograma
            $.get('/interface/forms/odontogram/assets/odontogram.svg', function(svgData) {
                draw.svg(svgData);

                // Superponer los símbolos de las intervenciones
                <?php foreach ($interventions as $intervention) { ?>
                    draw.image('/interface/forms/odontogram/php/get_symbol.php?symbol=<?php echo urlencode($intervention['symbol']); ?>')
                        .size(30, 30)
                        .move($('#<?php echo js_escape($intervention['svg_id']); ?>').attr('x'), $('#<?php echo js_escape($intervention['svg_id']); ?>').attr('y'));
                <?php } ?>
            }, 'text');
        });
    </script>
</body>
</html>