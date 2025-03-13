<?php
require_once("../../../globals.php");
require_once("$srcdir/sql.inc");

$pid = $_POST['pid'];
$tooth_id = $_POST['tooth_id'];
$date = $_POST['date'];

$sql = "SELECT * FROM odontogram_history WHERE patient_id = ? AND odontogram_id = (SELECT id FROM odontogram WHERE svg_id = ?) AND date = ?";
$result = sqlQuery($sql, array($pid, $tooth_id, $date));

echo json_encode($result ? $result : []);
?>