<?php
require_once(__DIR__ . "/../../globals.php");
require_once(dirname(__FILE__) . "/../../../library/forms.inc.php");
require_once(dirname(__FILE__) . "/../../../library/patient.inc.php");
require_once(dirname(__FILE__) . "/../../../library/options.inc.php");
require_once(dirname(__FILE__) . "/../../../library/translation.inc.php");
require_once(dirname(__FILE__) . "/../../../library/date_functions.php");

use OpenEMR\Common\Csrf\CsrfUtils;

$formid = (int) (isset($_GET['id']) ? $_GET['id'] : 0);
$csrf_token = CsrfUtils::collectCsrfToken();

if (!isset($_SESSION['site_id'])) {
    $_SESSION['site_id'] = 'default';
}

$pid = $_SESSION['pid'] ?? 0;
$encounter = $_SESSION['encounter'] ?? 0;
$userId = $_SESSION['authUserID'];
$professionalName = $_SESSION['authUser'] ?? xl('Unknown');

// Verificar sesión activa
if (empty($_SESSION['authUserID'])) {
    header("Location: $web_root/interface/login/login.php?site=default");
    exit;
}

// Cargar preferencia del sistema de numeración
$sql = "SELECT odontogram_preference FROM users WHERE id = ?";
$result = sqlQuery($sql, array($userId));
$defaultSystem = $result['odontogram_preference'] ?? 'FDI';

// Cargar datos existentes si formid está presente
$history = [];
if ($formid) {
    $sql = "SELECT * FROM form_odontogram_history WHERE id = ?";
    $history = sqlQuery($sql, array($formid));
}

// Actualizar preferencia si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['system'])) {
    $newSystem = $_POST['system'];
    if (in_array($newSystem, ['FDI', 'Universal', 'Palmer'])) {
        $sql = "UPDATE users SET odontogram_preference = ? WHERE id = ?";
        sqlStatement($sql, array($newSystem, $userId));
        echo json_encode(['success' => true]);
        exit;
    }
}

// Configurar fechas iniciales
$start_formatted = date('Y-m-d', strtotime('-10 years'));
$end_date_formatted = date('Y-m-d');
?>

<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo xlt('Odontogram'); ?></title>
    <link rel="stylesheet" href="<?php echo $web_root; ?>/public/assets/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $web_root; ?>/public/assets/jquery-ui/jquery-ui.min.css">
    <script src="<?php echo $web_root; ?>/public/assets/jquery/dist/jquery.min.js"></script>
    <script src="<?php echo $web_root; ?>/public/assets/jquery-ui/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/svg.js/3.2.0/svg.min.js"></script>
    <style>
        #odontogram-container { max-width: 100%; overflow: auto; }
        #odontogram-svg { width: 1048px; height: 704px; border: 1px solid #ccc; }
        .compact-select { width: 150px; }
        .filter-container { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .filter-container label { margin-bottom: 0; }
        .filter-container input[type="date"] { width: 140px; }
        .filter-label { vertical-align: middle; margin-right: 15px; }
        .modal-dialog { max-height: 80vh; margin: 1.75rem auto; }
        .modal-content { max-height: 80vh; display: flex; flex-direction: column; }
        .modal-body { flex: 1 1 auto; overflow-y: auto; max-height: 50vh; padding: 15px; }
        .modal-footer { flex-shrink: 0; position: sticky; bottom: 0; background: #fff; border-top: 1px solid #dee2e6; padding: 10px; }
        .palmer-symbol { color: red; }
    </style>
    <script>
        var defaultSystem = '<?php echo attr($defaultSystem); ?>';
        var userId = '<?php echo attr($userId); ?>';
        var patientId = '<?php echo attr($pid); ?>';
        var csrfToken = '<?php echo attr($csrf_token); ?>';
        var formId = '<?php echo attr($formid); ?>';
        var webRoot = '<?php echo $web_root; ?>';
    </script>
</head>
<body>
    <input type="hidden" id="csrf_token_form" value="<?php echo attr($csrf_token); ?>">
    <div class="container">
        <h2><?php echo xlt("Interactive Odontogram"); ?></h2>
        <form id="odontogram_form" method="post">
            <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrf_token); ?>">
            <input type="hidden" name="formid" value="<?php echo attr($formid); ?>">
            <div class="filter-container">
                <div>
                    <label><?php echo xlt("Numbering Format"); ?></label>
                    <select id="system" name="system" class="form-control compact-select">
                        <option value="FDI" <?php echo $defaultSystem === 'FDI' ? 'selected' : ''; ?>><?php echo xlt("FDI"); ?></option>
                        <option value="Universal" <?php echo $defaultSystem === 'Universal' ? 'selected' : ''; ?>><?php echo xlt("Universal"); ?></option>
                        <option value="Palmer" <?php echo $defaultSystem === 'Palmer' ? 'selected' : ''; ?>><?php echo xlt("Palmer"); ?></option>
                    </select>
                </div>
                <div>
                    <label><?php echo xlt("From Date"); ?></label>
                    <input type="date" name="start" id="start" value="<?php echo attr($start_formatted); ?>" class="form-control">
                </div>
                <div>
                    <label><?php echo xlt("To Date"); ?></label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo attr($end_date_formatted); ?>" class="form-control">
                </div>
                <div>
                    <label class="filter-label">
                        <input type="checkbox" class="intervention-type" value="Diagnosis" checked> <?php echo xlt("Diagnosis"); ?>
                    </label>
                    <label class="filter-label">
                        <input type="checkbox" class="intervention-type" value="Procedure" checked> <?php echo xlt("Procedure"); ?>
                    </label>
                    <label class="filter-label">
                        <input type="checkbox" class="intervention-type" value="Issue" checked> <?php echo xlt("Issue"); ?>
                    </label>
                </div>
                <button type="button" id="filterHistory" class="btn btn-primary"><?php echo xlt("Filter"); ?></button>
            </div>
            <div id="odontogram-container">
                <div id="odontogram-svg"></div>
            </div>
            <div class="mt-3">
                <span id="queue_count"><?php echo xlt("Interventions in queue"); ?>: <span id="queue_number">0</span></span>
                <button type="button" id="saveAllInterventions" class="btn btn-success"><?php echo xlt("Save All Interventions"); ?></button>
                <button type="button" id="cancelForm" class="btn btn-secondary"><?php echo xlt("Cancel"); ?></button>
            </div>
        </form>
    </div>

    <!-- Modal para elegir acción -->
    <div class="modal fade" id="toothActionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo xlt("Tooth Action"); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="tooth_info"></p>
                </div>
                <div class="modal-footer">
                    <button id="editTooth" class="btn btn-primary"><?php echo xlt("Register Intervention"); ?></button>
                    <button id="viewHistory" class="btn btn-secondary"><?php echo xlt("View History"); ?></button>
                    <button id="cancelToothAction" class="btn btn-secondary" data-dismiss="modal"><?php echo xlt("Cancel"); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para registrar intervención -->
    <div class="modal fade" id="interventionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo xlt("Register Intervention"); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="intervention_form">
                        <input type="hidden" id="tooth_id" name="tooth_id">
                        <div class="form-group">
                            <label><?php echo xlt("Intervention Type"); ?></label>
                            <select id="intervention_type" name="intervention_type" class="form-control">
                                <option value="Diagnosis"><?php echo xlt("Diagnosis"); ?></option>
                                <option value="Issue"><?php echo xlt("Issue"); ?></option>
                                <option value="Procedure"><?php echo xlt("Procedure"); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?php echo xlt("Option"); ?></label>
                            <select id="option_id" name="option_id" class="form-control"></select>
                        </div>
                        <div class="form-group">
                            <label><?php echo xlt("Notes"); ?></label>
                            <textarea id="notes" name="notes" class="form-control"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button id="addIntervention" class="btn btn-primary"><?php echo xlt("Add Intervention"); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo xlt("Cancel"); ?></button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo $web_root; ?>/interface/forms/odontogram/js/odontogram.js"></script>
</body>
</html>