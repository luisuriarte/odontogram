<?php
require_once(__DIR__ . "/../../globals.php");
require_once(dirname(__FILE__) . "/../../../library/api.inc.php");
require_once(dirname(__FILE__) . "/../../../library/lists.inc.php");
require_once(dirname(__FILE__) . "/../../../library/forms.inc.php");
require_once(dirname(__FILE__) . "/../../../library/patient.inc.php");
require_once(dirname(__FILE__) . "/../../../library/translation.inc.php");
require_once(dirname(__FILE__) . "/../../../library/date_functions.php");

use OpenEMR\Common\Csrf\CsrfUtils;

if (!CsrfUtils::verifyCsrfToken($_GET["csrf_token_form"])) {
    CsrfUtils::csrfNotVerified();
}

$pid = $_SESSION['pid'] ?? 0;
$encounter = $_SESSION['encounter'] ?? 0;
$userId = $_SESSION['authUserID'];
$formid = (int) (isset($_GET['id']) ? $_GET['id'] : 0);

if (empty($pid) || empty($encounter) || empty($formid)) {
    die(xl("Error: Invalid patient, encounter, or form ID."));
}

// Verificar sesión activa
if (empty($_SESSION['authUserID'])) {
    header("Location: $web_root/interface/login/login.php?site=default");
    exit;
}

// Obtener preferencia de numeración
$sql = "SELECT odontogram_preference FROM users WHERE id = ?";
$result = sqlQuery($sql, [$userId]);
$defaultSystem = $result['odontogram_preference'] ?? 'FDI';

// Cargar historial del formulario
$sql = "SELECT h.tooth_id, h.svg_style, h.date, h.intervention_type, h.option_id, h.notes, h.code, o.name, o.part
        FROM form_odontogram_history h
        JOIN form_odontogram o ON h.odontogram_id = o.id
        WHERE h.pid = ? AND h.encounter = ? AND h.id = ? AND h.activity = 1
        ORDER BY h.date DESC";
$history = [];
$result = sqlStatement($sql, [$pid, $encounter, $formid]);
while ($row = sqlFetchArray($result)) {
    $history[] = [
        'tooth_id' => $row['tooth_id'],
        'svg_style' => $row['svg_style'],
        'date' => oeTimestampFormatDateTime(strtotime($row['date']), 'global', 'db'),
        'intervention_type' => $row['intervention_type'],
        'option_id' => $row['option_id'],
        'notes' => $row['notes'],
        'code' => $row['code'],
        'name' => $row['name'],
        'part' => $row['part']
    ];
}
?>

<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo xlt('Odontogram Report'); ?></title>
    <link rel="stylesheet" href="<?php echo $web_root; ?>/public/assets/bootstrap/dist/css/bootstrap.min.css">
    <script src="<?php echo $web_root; ?>/public/assets/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/svg.js/3.2.0/svg.min.js"></script>
    <style>
        #odontogram-container { max-width: 100%; overflow: auto; }
        #odontogram-svg { width: 1048px; height: 704px; border: 1px solid #ccc; }
        .table { margin-top: 20px; }
        .palmer-symbol { fill: red; }
        .tooth-part { pointer-events: all; } /* Asegurar interactividad */
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo xlt("Odontogram"); ?></h2>
        <div id="odontogram-container">
            <div id="odontogram-svg"></div>
        </div>
        <h3><?php echo xlt("Intervention History"); ?></h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th><?php echo xlt("Date"); ?></th>
                    <th><?php echo xlt("Tooth"); ?></th>
                    <th><?php echo xlt("Part"); ?></th>
                    <th><?php echo xlt("Type"); ?></th>
                    <th><?php echo xlt("Option"); ?></th>
                    <th><?php echo xlt("Notes"); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $item) : ?>
                    <tr>
                        <td><?php echo text($item['date']); ?></td>
                        <td><?php echo text($item['name']); ?></td>
                        <td><?php echo text($item['part']); ?></td>
                        <td><?php echo text($item['intervention_type']); ?></td>
                        <td><?php echo text($item['option_id']); ?></td>
                        <td><?php echo text($item['notes']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        $(document).ready(function() {
            var draw = SVG().addTo('#odontogram-svg').size(1048, 704);
            var historyLayer = draw.group().attr('id', 'historyLayer');

            function applyStyles(toothId, svgStyle, date) {
                var element = draw.findOne('#' + toothId);
                if (element && svgStyle) {
                    console.log('Applying styles to tooth_id:', toothId, 'svg_style:', svgStyle, 'element type:', element.node.tagName);
                    if (!svgStyle.includes('fill:')) {
                        svgStyle = 'fill: ' + svgStyle;
                    }
                    if (element.node.tagName === 'g') {
                        element.each(function() {
                            if (this.node.tagName === 'path' || this.node.tagName === 'rect') {
                                this.addClass('tooth-part').attr('style', svgStyle).data('date', date || '');
                            }
                        });
                    } else {
                        element.addClass('tooth-part').attr('style', svgStyle).data('date', date || '');
                    }
                } else {
                    console.log('Element not found or invalid style for tooth_id:', toothId, 'svgStyle:', svgStyle);
                }
            }

            $.get('<?php echo $web_root; ?>/interface/forms/odontogram/assets/odontogram.svg', function(svgData) {
                draw.svg(svgData);
                var numbersLayer = draw.findOne('#Numbers');
                if (numbersLayer) {
                    var fdi = numbersLayer.findOne('#FDI');
                    var universal = numbersLayer.findOne('#Universal');
                    var palmer = numbersLayer.findOne('#Palmer');

                    function showNumberingSystem(system) {
                        if (fdi) fdi.hide();
                        if (universal) universal.hide();
                        if (palmer) palmer.hide();
                        var selectedGroup = numbersLayer.findOne('#' + system);
                        if (selectedGroup) selectedGroup.show();
                    }

                    showNumberingSystem('<?php echo attr($defaultSystem); ?>');
                }

                // Aplicar historial
                historyLayer.clear();
                var history = <?php echo json_encode($history); ?>;
                var latestStyles = {};
                history.forEach(function(item) {
                    if (item.tooth_id) {
                        latestStyles[item.tooth_id] = {
                            svg_style: item.svg_style,
                            date: item.date
                        };
                    }
                });

                Object.keys(latestStyles).forEach(function(toothId) {
                    var styleData = latestStyles[toothId];
                    applyStyles(toothId, styleData.svg_style, styleData.date);
                });
            }, 'text').fail(function(jqXHR, textStatus) {
                alert('<?php echo xlj("Error loading SVG:"); ?> ' + textStatus);
            });
        });
    </script>
</body>
</html>