<?php
require_once("../../globals.php");
require_once("$srcdir/forms.inc");
require_once("$srcdir/patient.inc");

if (!isset($_SESSION['site_id'])) {
    $_SESSION['site_id'] = 'default';
}

$pid = $_SESSION['pid'] ?? 0;
$encounter = $_SESSION['encounter'] ?? 0;
$userId = $_SESSION['authUserID'];
$professionalName = $_SESSION['authUser'] ?? xl('Unknown'); // Logged-in professional's name

$sql = "SELECT odontogram_preference FROM users WHERE id = ?";
$result = sqlQuery($sql, array($userId));
$defaultSystem = $result['odontogram_preference'] ?? 'FDI';

if (isset($_POST['system'])) {
    $newSystem = $_POST['system'];
    if (in_array($newSystem, ['FDI', 'Universal', 'Palmer'])) {
        $sql = "UPDATE users SET odontogram_preference = ? WHERE id = ?";
        sqlStatement($sql, array($newSystem, $userId));
        echo json_encode(['success' => true]);
        exit;
    }
}

$start = $_POST['start'] ?? date('Y-m-d', strtotime('-10 years'));
$endDate = $_POST['end_date'] ?? date('Y-m-d');
?>

<html>
<head>
	<meta charset="UTF-8"> <!-- Forzar UTF-8 en la página -->
    <title><?php echo xl('Odontogram'); ?></title>
    <link rel="stylesheet" href="/public/assets/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/assets/jquery-ui/jquery-ui.min.css">
    <script src="/public/assets/jquery/dist/jquery.min.js"></script>
    <script src="/public/assets/jquery-ui/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/svg.js/3.2.0/svg.min.js"></script>
	<script src="/interface/forms/odontogram/js/bundle.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/js-draw@1.29.0/dist/js-draw.umd.js"></script> -->
    <style>
        #odontogram-container { max-width: 100%; overflow: auto; }
        #odontogram-svg { width: 1048px; height: 704px; border: 1px solid #ccc; }
        #odontogram-svg * { pointer-events: all !important; }
        .compact-select { width: 150px; }
        .filter-container { display: flex; justify-content: flex-end; align-items: center; gap: 10px; }
        .filter-container label { margin-bottom: 0; }
        .filter-container input[type="date"] { width: 140px; }
        .toggle-switch { position: relative; display: inline-block; width: 50px; height: 24px; margin-right: 10px; }
        .toggle-switch input[type="checkbox"] { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
        input[type="checkbox"]:checked + .slider { background-color: #28a745; }
        input[type="checkbox"]:checked + .slider:before { transform: translateX(26px); }
        .filter-label { vertical-align: middle; margin-right: 15px; }
        #symbol-preview { width: 50px; height: 50px; margin-top: 10px; }

        .modal-dialog {
            max-height: 80vh;
            margin: 1.75rem auto;
        }
        .modal-content {
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            max-height: 50vh;
            padding: 15px;
        }
        .modal-footer {
            flex-shrink: 0;
            position: sticky;
            bottom: 0;
            background: #fff;
            border-top: 1px solid #dee2e6;
            padding: 10px;
        }
        #symbol-preview {
            width: 100px;
            height: 100px;
            overflow: hidden;
        }
		.palmer-symbol {
            color: red; /* Símbolo en rojo */
        }
        .drawing-container {
            position: relative;
            width: 1048px;
            height: 704px;
        }
        #jsdraw-toolbar {
            position: absolute;
            top: 10px;
            left: 1060px;
            background-color: #f5f5f5 !important; /* Fondo sólido */
            padding: 10px 5px;
            border: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            z-index: 10;
        }
        .tool-button {
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 20px;
        }
        .tool-button:hover {
            background-color: #e0e0e0;
        }
        .tool-button.active {
            background-color: #d0d0ff;
        }
        #drawing-layer {
            width: 1048px !important;
            height: 704px !important;
            background: transparent !important;
        }
        #drawing-layer canvas {
            width: 1048px !important;
            height: 704px !important;
            background: transparent !important; /* Forzar transparencia del canvas */
        }
        .js-draw-toolbar {
            position: absolute !important;
            top: 10px !important;
            left: 1060px !important;
            background-color: #f5f5f5 !important; /* Fondo sólido */
            border: 1px solid #ddd !important;
            z-index: 10 !important;
        }
    </style>
    <script>
        var defaultSystem = '<?php echo $defaultSystem; ?>';
        var userId = '<?php echo $userId; ?>';
        var patientId = '<?php echo $pid; ?>';
    </script>
</head>
<body>
	<div id="jsdraw-toolbar"></div>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="m-0"><?php echo xl('Odontogram'); ?></h2>
            <div class="filter-container">
                <label><?php echo xl('System:'); ?></label>
                <select id="numbering_system" class="form-control compact-select">
                    <option value="FDI">FDI</option>
                    <option value="Universal"><?php echo xl('Universal'); ?></option>
                    <option value="Palmer"><?php echo xl('Palmer'); ?></option>
                </select>
                <label><?php echo xl('Start:'); ?></label>
                <input type="date" id="start_date" class="form-control" value="<?php echo $startDate; ?>">
                <label><?php echo xl('End:'); ?></label>
                <input type="date" id="end_date" class="form-control" value="<?php echo $endDate; ?>">
                <button id="update_history" class="btn btn-primary btn-sm"><?php echo xl('Update'); ?></button>
            </div>
        </div>

        <div class="form-group">
            <label><?php echo xl('Filter by type:'); ?></label><br>
            <label class="toggle-switch">
                <input type="checkbox" id="filter_diagnosis" name="filters[]" value="odonto_diagnosis" checked>
                <span class="slider"></span>
            </label>
            <span class="filter-label"><?php echo xl('Diagnoses'); ?></span>
            <label class="toggle-switch">
                <input type="checkbox" id="filter_issue" name="filters[]" value="odonto_issue" checked>
                <span class="slider"></span>
            </label>
            <span class="filter-label"><?php echo xl('Issues'); ?></span>
            <label class="toggle-switch">
                <input type="checkbox" id="filter_procedures" name="filters[]" value="odonto_procedures" checked>
                <span class="slider"></span>
            </label>
            <span class="filter-label"><?php echo xl('Procedures'); ?></span>
        </div>

		<!-- Modal de detalles del diente -->
		<div class="modal fade" id="toothModal" tabindex="-1" role="dialog" aria-labelledby="toothModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="toothModalLabel"><?php echo xl('Tooth Details'); ?></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<p><strong><?php echo xl('Name:'); ?></strong> <span id="toothName"></span></p>
						<p><strong><?php echo xl('Details:'); ?></strong> <span id="toothDetails"></span></p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" id="editTooth"><?php echo xl('Edit'); ?></button>
						<button type="button" class="btn btn-info" id="historyTooth"><?php echo xl('History'); ?></button>
						<button type="button" class="btn btn-secondary" id="drawTooth"><?php echo xl('Draw'); ?></button>
						<button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo xl('Close'); ?></button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="editModalLabel"><?php echo xl('Edit Tooth'); ?></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">×</span>
						</button>
					</div>
					<div class="modal-body">
						<p><strong><?php echo xl('Name:'); ?></strong> <span id="editToothName"></span></p>
						<p><strong><?php echo xl('Details:'); ?></strong> <span id="editToothDetails"></span></p>
						<input type="hidden" id="editSvgId" name="svgId">
						<div class="form-group">
							<label for="editInterventionType"><?php echo xl('Intervention Type'); ?></label>
							<select class="form-control" id="editInterventionType">
								<option value="Diagnosis"><?php echo xl('Diagnosis'); ?></option>
								<option value="Issue"><?php echo xl('Issue'); ?></option>
								<option value="Procedure"><?php echo xl('Procedure'); ?></option>
							</select>
						</div>
						<div class="form-group">
							<label for="editOption"><?php echo xl('Option'); ?></label>
							<select class="form-control" id="editOption"></select>
						</div>
						<div class="form-group">
							<label for="editCode"><?php echo xl('Code'); ?></label>
							<input type="text" class="form-control" id="editCode" readonly>
						</div>
						<div class="form-group">
							<label for="editNotes"><?php echo xl('Notes'); ?></label>
							<textarea class="form-control" id="editNotes" rows="3"></textarea>
						</div>
						<div id="symbol-preview"></div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" id="editModalOk"><?php echo xl('Ok'); ?></button>
						<button type="button" class="btn btn-secondary" id="editModalCancel" data-dismiss="modal"><?php echo xl('Cancel'); ?></button>
					</div>
				</div>
			</div>
		</div>
    </div>
    <!-- Contenedores para odontograma y lienzo -->
    <div class="drawing-container" style="position: relative; width: 1048px; height: 704px;">
        <div id="odontogram-svg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;"></div>
        <div id="drawing-layer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; pointer-events: none;"></div>
        <div id="jsdraw-toolbar" style="display: none;"></div>
    </div>
</div>

<script>
$(document).ready(function() {
    var draw = SVG().addTo('#odontogram-svg').size(1048, 704);
    var historyLayer = draw.group().id('historyLayer');
    var pendingChanges = {};
    var editor = null;

    var interventionTypeToListId = {
        'Diagnosis': 'odonto_diagnosis',
        'Issue': 'odonto_issue',
        'Procedure': 'odonto_procedures'
    };

    // Chequeo inicial para confirmar que jsdraw está disponible
    if (typeof jsdraw === 'undefined') {
        console.error("jsdraw is not defined. Check if /interface/forms/odontogram/assets/js-draw-bundle.js loaded correctly.");
    } else {
        console.log("jsdraw loaded successfully:", jsdraw);
    }

    loadHistory();

    $.get('/interface/forms/odontogram/assets/odontogram.svg', function(svgData) {
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

            showNumberingSystem(defaultSystem);

            $('#numbering_system').change(function() {
                var system = $(this).val();
                showNumberingSystem(system);
                $.ajax({
                    url: '/interface/forms/odontogram/new.php',
                    type: 'POST',
                    data: { system: system },
                    success: function() {},
                    error: function(xhr, status, error) {
                        console.error("<?php echo xl('Error saving system:'); ?> " + error);
                    }
                });
            }).val(defaultSystem);
        }
    }, 'text').fail(function(jqXHR, textStatus) {
        console.error("<?php echo xl('Error loading SVG:'); ?> " + textStatus);
    });

    $('#odontogram-svg').on('click', 'rect, path:not([data-tooth-id]), polygon', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var toothId = this.id;
        window.lastClickedToothId = toothId;

        if (!toothId) return;

        $.ajax({
            url: '/interface/forms/odontogram/php/get_tooth_details.php',
            type: 'POST',
            data: { tooth_id: toothId, user_id: userId },
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    console.error("<?php echo xl('Error in response:'); ?> " + data.error);
                } else {
                    var numberDisplay = data.number;
                    if (data.system === 'PALMER' && data.palmer_symbol) {
                        if (data.palmer.indexOf(data.palmer_symbol) === 0) {
                            numberDisplay = '<span class="palmer-symbol">' + data.palmer_symbol + '</span>' + data.number;
                        } else {
                            numberDisplay = data.number + '<span class="palmer-symbol">' + data.palmer_symbol + '</span>';
                        }
                    }
                    $('#toothName').html(data.name + ' - ' + data.system + ' ' + numberDisplay);
                    $('#toothDetails').text(data.part + ', ' + data.arc + ', ' + data.side);
                    $('#toothModal').modal('show');
                }
            },
            error: function(xhr, status, error) {
                console.error("<?php echo xl('AJAX Error:'); ?> " + status + " - " + error);
            }
        });
    });

    function loadHistory() {
        var historyStartDate = $('#start_date').val() || '<?php echo $start; ?>';
        var historyEndDate = $('#end_date').val() || '<?php echo $endDate; ?>';
        var encounter = '<?php echo $_SESSION['encounter'] ?? 0; ?>';
        var filters = [];
        if ($('#filter_diagnosis').is(':checked')) filters.push('Diagnosis');
        if ($('#filter_issue').is(':checked')) filters.push('Issue');
        if ($('#filter_procedures').is(':checked')) filters.push('Procedure');

        $.ajax({
            url: '/interface/forms/odontogram/php/get_history.php',
            type: 'POST',
            data: { 
                start: historyStartDate, 
                end: historyEndDate, 
                encounter: encounter,
                filters: filters 
            },
            dataType: 'json',
            success: function(history) {
                historyLayer.clear();
                history.forEach(function(item) {
                    if (item.tooth_id) {
                        applyStyles(item.tooth_id, item.svg_style, item.draw_d, item.draw_style);
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error("<?php echo xl('Error loading dental history:'); ?> " + status + " - " + error);
            }
        });
    }

    function applyStyles(toothId, svgStyle, drawD, drawStyle) {
        var element = SVG('#' + toothId);
        if (element && svgStyle) {
            if (!svgStyle.includes('fill:')) {
                svgStyle = 'fill: ' + svgStyle;
            }
            element.attr('style', svgStyle);
        }
        if (drawD && drawStyle) {
            historyLayer.path(drawD).attr('style', drawStyle).attr('data-tooth-id', toothId);
        }
    }

    function loadOptions(type) {
        $.ajax({
            url: '/interface/forms/odontogram/php/get_options.php',
            type: 'POST',
            data: { type: type },
            dataType: 'json',
            success: function(options) {
                var $select = $('#editOption');
                $select.empty();
                if (options.error) {
                    $select.append(`<option value="">${options.error}</option>`);
                    return;
                }
                options.forEach(function(option) {
                    $select.append(`<option value="${option.option_id}" data-symbol="${option.symbol}" data-style="${option.symbol}" data-code="${option.codes}">${option.title}</option>`);
                });
                updateSymbolPreview();
            },
            error: function(xhr, status, error) {
                console.error("<?php echo xl('AJAX Error in loading options:'); ?> " + status + " - " + error);
            }
        });
    }

    function updateSymbolPreview() {
        var selectedOption = $('#editOption option:selected');
        var style = selectedOption.data('style');
        var code = selectedOption.data('code');
        $('#editCode').val(code || '');
        var previewStyle = style.includes('fill:') ? style : 'fill: ' + style;
        $('#symbol-preview').html(style ? `<div style="${previewStyle}; width: 30px; height: 30px;"></div>` : `<p><?php echo xl('No style available'); ?></p>`);
    }

    $('#editOption').change(updateSymbolPreview);

    $('#editTooth').click(function(e) {
        e.preventDefault();
        var toothName = $('#toothName').text();
        var toothDetails = $('#toothDetails').text();
        var svgId = window.lastClickedToothId;

        $('#editToothName').text(toothName);
        $('#editToothDetails').text(toothDetails);
        $('#editSvgId').val(svgId);
        loadOptions('diagnosis');

        $('#toothModal').modal('hide');
        $('#editModal').modal('show');
    });

    $('#editModalOk').click(function() {
        var toothId = $('#editSvgId').val();
        var interventionType = $('#editInterventionType').val();
        var optionId = $('#editOption').val();
        var svgStyle = $('#editOption option:selected').data('style');
        var code = $('#editOption option:selected').data('code');
        var notes = $('#editNotes').val();

        if (toothId && interventionType && optionId) {
            if (svgStyle && !svgStyle.includes('fill:')) {
                svgStyle = 'fill: ' + svgStyle;
            }
            pendingChanges[toothId] = pendingChanges[toothId] || {};
            pendingChanges[toothId].intervention_type = interventionType;
            pendingChanges[toothId].list_id = interventionTypeToListId[interventionType];
            pendingChanges[toothId].option_id = optionId;
            pendingChanges[toothId].code = code || null;
            pendingChanges[toothId].svg_style = svgStyle;
            pendingChanges[toothId].notes = notes || null;
            applyStyles(toothId, svgStyle, null, null);
            $('#editModal').modal('hide');
        }
    });

    $('#drawTooth').click(function() {
        var toothId = window.lastClickedToothId;
        if (toothId) {
            $('#toothModal').modal('hide');
            $('#drawing-layer').css('pointer-events', 'auto');
            $('#jsdraw-toolbar').show().empty();

            if (!editor) {
                try {
                    editor = new jsdraw.Editor(document.getElementById('drawing-layer'), {
                        backgroundColor: 'transparent' // Intento inicial de transparencia
                    });

                    // Agregar la barra de herramientas
                    editor.addToolbar();

                    // Depuración: Verificar el canvas y el toolbar
                    console.log("Editor initialized:", editor);
                    console.log("Canvas created:", $('#drawing-layer canvas')[0]);
                    console.log("Toolbar element:", $('.js-draw-toolbar')[0]);
                } catch (e) {
                    console.error("Error initializing jsdraw:", e);
                    return;
                }
            }

            $('#drawing-layer path').remove();

            if (!$('#finishDrawing').length) {
                $('<button id="finishDrawing" class="btn btn-primary">Finish Drawing</button>')
                    .appendTo('#jsdraw-toolbar')
                    .click(function() {
                        var svgData = editor.toSVG();
                        var paths = $(svgData).find('path');
                        if (paths.length > 0) {
                            var path = paths.last()[0];
                            var drawD = path.getAttribute('d');
                            var stroke = path.getAttribute('stroke') || '#000000';
                            var strokeWidth = path.getAttribute('stroke-width') || '2';
                            var drawStyle = `stroke: ${stroke}; stroke-width: ${strokeWidth}; fill: none;`;

                            pendingChanges[toothId] = pendingChanges[toothId] || {};
                            pendingChanges[toothId].draw_d = drawD;
                            pendingChanges[toothId].draw_style = drawStyle;
                            historyLayer.clear();
                            Object.keys(pendingChanges).forEach(function(id) {
                                applyStyles(id, pendingChanges[id].svg_style, pendingChanges[id].draw_d, pendingChanges[id].draw_style);
                            });
                        }
                        $('#jsdraw-toolbar').hide();
                        $('#drawing-layer').css('pointer-events', 'none').empty();
                        editor = null;
                        $(this).remove();
                    });
            }
        } else {
            console.error("Cannot activate drawing: toothId missing.");
        }
    });

    $('#saveForm').on('click', function() {
        var changes = [];
        Object.keys(pendingChanges).forEach(function(toothId) {
            var change = {
                tooth_id: toothId,
                intervention_type: pendingChanges[toothId].intervention_type || 'Diagnosis',
                list_id: pendingChanges[toothId].list_id,
                option_id: pendingChanges[toothId].option_id,
                code: pendingChanges[toothId].code,
                svg_style: pendingChanges[toothId].svg_style,
                draw_d: pendingChanges[toothId].draw_d,
                draw_style: pendingChanges[toothId].draw_style,
                notes: pendingChanges[toothId].notes,
                pid: '<?php echo $pid; ?>',
                encounter: '<?php echo $encounter; ?>',
                user: '<?php echo $userId; ?>',
                date: new Date().toISOString().slice(0, 19).replace('T', ' ')
            };
            changes.push(change);
        });

        $.ajax({
            url: '/interface/forms/odontogram/php/save_odontogram.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(changes),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    top.restoreSession();
                    window.location.href = '<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/encounter/encounter_top.php';
                } else {
                    alert(xl('Failed to save odontogram') + ': ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                alert(xl('Error saving odontogram') + ': ' + xhr.responseText);
            }
        });
    });

    $('#cancelForm').on('click', function() {
        top.restoreSession();
        window.location.href = '<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/encounter/encounter_top.php';
    });

    $('#start_date, #end_date, #update_history, #filter_diagnosis, #filter_issue, #filter_procedures').on('change click', loadHistory);
});
</script>
</body>
</html>