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

$sql = "SELECT odontogram_preferences FROM users WHERE id = ?";
$result = sqlQuery($sql, array($userId));
$defaultSystem = $result['odontogram_preferences'] ?? 'FDI';

if (isset($_POST['system'])) {
    $newSystem = $_POST['system'];
    if (in_array($newSystem, ['FDI', 'Universal', 'Palmer'])) {
        $sql = "UPDATE users SET odontogram_preferences = ? WHERE id = ?";
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
    <title><?php echo xl('Odontogram'); ?></title>
    <link rel="stylesheet" href="/public/assets/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/assets/jquery-ui/jquery-ui.min.css">
    <script src="/public/assets/jquery/dist/jquery.min.js"></script>
    <script src="/public/assets/jquery-ui/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/svg.js/3.2.0/svg.min.js"></script>
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
    </style>
    <script>
        var defaultSystem = '<?php echo $defaultSystem; ?>';
        var userId = '<?php echo $userId; ?>';
        var patientId = '<?php echo $pid; ?>';
    </script>
</head>
<body>
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

        <div id="odontogram-container" class="panel panel-default">
            <div class="panel-body">
                <div id="odontogram-svg"></div>
            </div>
        </div>

        <div id="tooth_info" class="panel panel-default">
            <div class="panel-heading"><?php echo xl('Tooth Details'); ?></div>
            <div class="panel-body">
                <p><?php echo xl('Select a numbering system or click on a tooth.'); ?></p>
            </div>
        </div>

        <!-- Initial Modal -->
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
                        <button type="button" class="btn btn-info" id="viewHistory"><?php echo xl('History'); ?></button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo xl('Close'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel"><?php echo xl('Edit Intervention'); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p><strong><?php echo xl('Name:'); ?></strong> <span id="editToothName"></span></p>
                        <p><strong><?php echo xl('Details:'); ?></strong> <span id="editToothDetails"></span></p>
                        <input type="hidden" id="editSvgId">
                        <div class="form-group">
                            <label><?php echo xl('Date and Time:'); ?></label>
                            <input type="datetime-local" id="editDateTime" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="form-group">
                            <label><?php echo xl('Intervention Type:'); ?></label>
                            <select id="editInterventionType" class="form-control">
                                <option value="diagnosis"><?php echo xl('Diagnosis'); ?></option>
                                <option value="issue"><?php echo xl('Issue'); ?></option>
                                <option value="procedure"><?php echo xl('Procedure'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?php echo xl('Option:'); ?></label>
                            <select id="editOption" class="form-control"></select>
                        </div>
                        <div id="symbol-preview"></div>
                        <div class="form-group">
                            <label><?php echo xl('Code:'); ?></label>
                            <input type="text" id="editCode" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label><?php echo xl('Notes:'); ?></label>
                            <textarea id="editNotes" class="form-control" rows="2"></textarea>
                        </div>
                        <p><strong><?php echo xl('Professional:'); ?></strong> <?php echo htmlspecialchars($professionalName); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="saveEdit"><?php echo xl('Save'); ?></button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo xl('Cancel'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<div class="form-footer mt-3">
		<button type="button" class="btn btn-primary" id="saveForm"><?php echo xl('Save'); ?></button>
		<button type="button" class="btn btn-secondary" id="cancelForm"><?php echo xl('Cancel'); ?></button>
	</div>
	<script>
	$(document).ready(function() {
		console.log("<?php echo xl('Document ready'); ?>");

		var draw = SVG().addTo('#odontogram-svg').size(1048, 704);
		var historyLayer = draw.group().id('historyLayer');
		console.log("<?php echo xl('SVG container and history layer created'); ?>");

		loadHistory();

		// Click event on SVG elements
		$('#odontogram-svg').on('click', 'rect, path, polygon', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var svgId = this.id;
			window.lastClickedToothId = svgId;
			console.log("<?php echo xl('Click event triggered on element with ID:'); ?> " + svgId + " (" + this.tagName + ")");

			if (!svgId) {
				console.log("<?php echo xl('Element without ID clicked'); ?>");
				return;
			}

			$.ajax({
				url: '/interface/forms/odontogram/php/get_tooth_details.php',
				type: 'POST',
				data: { svg_id: svgId, user_id: userId },
				dataType: 'json',
				success: function(data) {
					if (data.error) {
						console.error("<?php echo xl('Error in response:'); ?> " + data.error);
						return;
					}
					$('#toothName').text(data.name + ' - ' + data.system + ' ' + data.number);
					$('#toothDetails').text(data.part + ', ' + data.arc + ', ' + data.side);
					$('#toothModal').modal('show');
				},
				error: function(xhr, status, error) {
					console.error("<?php echo xl('AJAX Error:'); ?> " + status + " - " + error);
				}
			});
		});

		// Load the base SVG
		$.get('/interface/forms/odontogram/assets/odontogram.svg', function(svgData) {
			console.log("<?php echo xl('SVG loaded from server'); ?>");
			draw.svg(svgData);

			var numbersLayer = draw.findOne('#Numbers');
			if (numbersLayer) {
				console.log("<?php echo xl('Layer #Numbers found'); ?>");
				var fdi = numbersLayer.findOne('#FDI');
				var universal = numbersLayer.findOne('#Universal');
				var palmer = numbersLayer.findOne('#Palmer');

				function showNumberingSystem(system) {
					if (fdi) fdi.hide();
					if (universal) universal.hide();
					if (palmer) palmer.hide();

					var selectedGroup = numbersLayer.findOne('#' + system);
					if (selectedGroup) {
						selectedGroup.show();
						console.log("<?php echo xl('Showing:'); ?> #" + system);
					}
				}

				showNumberingSystem(defaultSystem);

				$('#numbering_system').change(function() {
					var system = $(this).val();
					console.log("<?php echo xl('System selected:'); ?> " + system);
					showNumberingSystem(system);

					$.ajax({
						url: '/interface/forms/odontogram/new.php',
						type: 'POST',
						data: { system: system },
						success: function() { console.log("<?php echo xl('System saved:'); ?> " + system); },
						error: function(xhr, status, error) { console.error("<?php echo xl('Error saving system:'); ?> " + error); }
					});
				}).val(defaultSystem);
			}
		}, 'text').fail(function(jqXHR, textStatus) {
			console.error("<?php echo xl('Error loading SVG:'); ?> " + textStatus);
		});

		function loadHistory() {
			var historyStartDate = $('#start_date').val() || '<?php echo $start; ?>'; // Usa input o valor predeterminado (2015-03-13)
			var historyEndDate = $('#end_date').val() || '<?php echo $end; ?>';       // Usa input o valor predeterminado (2025-03-13)
			console.log("<?php echo xl('Loading dental history from'); ?> " + historyStartDate + " <?php echo xl('to'); ?> " + historyEndDate + " <?php echo xl('with filters:'); ?>", ['odonto_diagnosis', 'odonto_issue', 'odonto_procedures']);
			$.ajax({
				url: '/interface/forms/odontogram/php/get_history.php',
				type: 'POST',
				data: { 
					start: historyStartDate, 
					end: historyEndDate, 
					filters: ['odonto_diagnosis', 'odonto_issue', 'odonto_procedures'] 
				},
				dataType: 'json',
				success: function(history) {
					console.log("<?php echo xl('Dental history loaded:'); ?>", history);
					history.forEach(function(item) {
						overlaySymbol(item.tooth_id, item.symbol);
					});
				},
				error: function(xhr, status, error) {
					console.error("<?php echo xl('Error loading dental history:'); ?> " + status + " - " + error);
					console.log("<?php echo xl('Server response:'); ?> ", xhr.responseText);
				}
			});
		}

		$('#start_date').change(function() {
			var startDate = $(this).val();
			$('#end_date').val(startDate);
			loadHistory();
		});

		$('#end_date').change(function() {
			loadHistory();
		});

		$('#update_history').click(function() {
			loadHistory();
		});

		// Load options for intervention type
		function loadOptions(type) {
			console.log("<?php echo xl('Loading options for intervention type:'); ?> " + type);
			$.ajax({
				url: '/interface/forms/odontogram/php/get_options.php',
				type: 'POST',
				data: { type: type },
				dataType: 'json',
				success: function(options) {
					var $select = $('#editOption');
					$select.empty();
					if (options.error) {
						console.warn("<?php echo xl('Error in options:'); ?> " + options.error);
						$select.append(`<option value="">${options.error}</option>`);
						return;
					}
					options.forEach(function(option) {
						$select.append(`<option value="${option.option_id}" data-symbol="${option.symbol}" data-code="${option.codes}">${option.title}</option>`);
					});
					console.log("<?php echo xl('Options loaded, updating symbol preview'); ?>");
					updateSymbolPreview();
				},
				error: function(xhr, status, error) {
					console.error("<?php echo xl('AJAX Error in loading options:'); ?> " + status + " - " + error);
					$('#editOption').empty().append(`<option value=""><?php echo xl('Error loading options'); ?></option>`);
				}
			});
		}

		// Update symbol preview in modal
		function updateSymbolPreview() {
			var selectedOption = $('#editOption option:selected');
			var symbolFile = selectedOption.data('symbol');
			var code = selectedOption.data('code');

			console.log("<?php echo xl('Updating preview - symbol:'); ?> " + symbolFile + ", <?php echo xl('code:'); ?> " + code);

			$('#editCode').val(code || '');

			if (symbolFile) {
				var svgPath = '/interface/forms/odontogram/php/get_symbol.php?symbol=' + encodeURIComponent(symbolFile);
				console.log("<?php echo xl('Attempting to load symbol SVG from:'); ?> " + svgPath);
				$('#symbol-preview').html(`<img src="${svgPath}" alt="${selectedOption.text()} Icon" class="me-2" style="width: 30px; height: 20px;">`);
			} else {
				$('#symbol-preview').html(`<p><?php echo xl('No symbol available'); ?></p>`);
			}
		}

		$('#editOption').change(updateSymbolPreview);

		// Transition from tooth modal to edit modal
		$('#editTooth').click(function(e) {
			e.preventDefault();
			console.log("<?php echo xl('Click on Edit - Starting modal transition'); ?>");

			try {
				var toothName = $('#toothName').text();
				var toothDetails = $('#toothDetails').text();
				var svgId = $('#odontogram-svg .selected').attr('id') || window.lastClickedToothId;

				console.log("<?php echo xl('Data retrieved - tooth name:'); ?> " + toothName + ", <?php echo xl('svgId:'); ?> " + svgId);

				$('#editToothName').text(toothName);
				$('#editToothDetails').text(toothDetails);
				$('#editSvgId').val(svgId);

				console.log("<?php echo xl('Calling loadOptions for diagnosis'); ?>");
				loadOptions('diagnosis');

				$('#toothModal').modal('hide');
				$('#toothModal').on('hidden.bs.modal', function() {
					$('#editModal').modal('show');
					$('#editModal').on('shown.bs.modal', function() {
						console.log("<?php echo xl('Edit modal shown - Focusing intervention type'); ?>");
						$('#editInterventionType').focus();
					});
				});
			} catch (error) {
				console.error("<?php echo xl('Error in editTooth click event:'); ?> " + error.message);
			}
		});

		// Save intervention
		$('#saveEdit').click(function() {
			console.log("<?php echo xl('Saving odontogram data'); ?>");

			var toothId = $('#editSvgId').val();
			var interventionType = $('#editInterventionType').val();
			var selectedOption = $('#editOption option:selected');
			var optionId = selectedOption.val();
			var symbol = selectedOption.data('symbol');
			var code = selectedOption.data('code');

			if (!toothId || !interventionType || !optionId) {
				alert(xl('Please fill all required fields')); // Dental USA English
				return;
			}

			var data = {
				tooth_id: toothId,
				intervention_type: interventionType,
				option_id: optionId,
				symbol: symbol,
				code: code
			};
			console.log("<?php echo xl('Data to be saved:'); ?> ", data);

			$.ajax({
				url: '/interface/forms/odontogram/php/save_odontogram.php',
				type: 'POST',
				contentType: 'application/json',
				data: JSON.stringify(data),
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						console.log("<?php echo xl('Data saved with ID:'); ?> " + response.id);
						overlaySymbol(toothId, symbol);
						$('#editModal').modal('hide');
						loadHistory();
					} else {
						console.error("<?php echo xl('Error in response:'); ?> ", response);
						alert(xl('Failed to save dental intervention') + ': ' + response.error); // Dental USA English
					}
				},
				error: function(xhr, status, error) {
					console.error("<?php echo xl('AJAX Error:'); ?> " + status + " - " + error);
					console.log("<?php echo xl('Server response:'); ?> ", xhr.responseText);
					alert(xl('Error saving dental intervention') + ': ' + xhr.responseText); // Dental USA English
				}
			});
		});

		// Overlay symbol with central symmetry
		function overlaySymbol(toothId, symbolFile) {
			var element = SVG('#' + toothId);
			if (!element) {
				console.error("<?php echo xl('Tooth element not found:'); ?> " + toothId);
				return;
			}

			var bbox = element.bbox();
			var centerX = bbox.cx;
			var centerY = bbox.cy;
			var symbolWidth = 30;
			var symbolHeight = 30;

			var svgPath = '/interface/forms/odontogram/assets/svg/' + symbolFile; // Ruta directa
			var image = historyLayer.image(svgPath)
				.size(symbolWidth, symbolHeight)
				.move(centerX - symbolWidth / 2, centerY - symbolHeight / 2)
				.addClass('odontogram-overlay');

			console.log("<?php echo xl('Symbol overlaid on'); ?> " + toothId + " <?php echo xl('at coordinates:'); ?> x=" + (centerX - symbolWidth / 2) + ", y=" + (centerY - symbolHeight / 2));
		}

		$('#viewHistory').click(function() {
			$('#toothModal').modal('hide');
			alert("<?php echo xl('Open dental history (next step)'); ?>"); // Dental USA English
		});

		// Save the entire form
		$('#saveForm').on('click', function() {
			console.log("<?php echo xl('Saving the odontogram form'); ?>");

			$.ajax({
				url: '/interface/forms/odontogram/save.php',
				type: 'POST',
				data: {
					pid: '<?php echo $pid; ?>',
					encounter: '<?php echo $encounter; ?>'
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						console.log("<?php echo xl('Odontogram saved successfully, ID:'); ?> " + response.form_id);
						top.restoreSession();
						window.location.href = '<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/encounter/encounter_top.php';
					} else {
						console.error("<?php echo xl('Error saving odontogram:'); ?> ", response);
						alert(xl('Failed to save odontogram') + ': ' + response.error); // Dental USA English
					}
				},
				error: function(xhr, status, error) {
					console.error("<?php echo xl('AJAX Error saving odontogram:'); ?> " + status + " - " + error);
					console.log("<?php echo xl('Server response:'); ?> ", xhr.responseText);
					alert(xl('Error saving odontogram') + ': ' + xhr.responseText); // Dental USA English
				}
			});
		});

		// Cancel the form
		$('#cancelForm').on('click', function() {
			console.log("<?php echo xl('Canceling the odontogram form'); ?>");
			top.restoreSession();
			window.location.href = '<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/encounter/encounter_top.php';
		});
	});
	</script>
</body>
</html>