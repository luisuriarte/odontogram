<?php
$mysqli = new mysqli("localhost", "openemr", "S4nC4rl0sC3ntr0", "openemr");

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Configurar codificación UTF-8
$mysqli->set_charset("utf8mb4") or die("Error al configurar UTF-8: " . $mysqli->error);

// Mapeo de códigos Universal, FDI y Palmer
$tooth_codes = [
	// === Adult Teeth == ⏋⎿ ⏌┌
	
    // Maxillary Right (Adult)
    ["universal" => "1",  "fdi" => "18", "palmer" => "8⏌", "name" => "Third Molar", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Right"],
    ["universal" => "2",  "fdi" => "17", "palmer" => "7⏌", "name" => "Second Molar", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Right"],
    ["universal" => "3",  "fdi" => "16", "palmer" => "6⏌", "name" => "First Molar", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Right"],
    ["universal" => "4",  "fdi" => "15", "palmer" => "5⏌", "name" => "Second Premolar", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Right"],
    ["universal" => "5",  "fdi" => "14", "palmer" => "4⏌", "name" => "First Premolar", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Right"],
    ["universal" => "6",  "fdi" => "13", "palmer" => "3⏌", "name" => "Canine", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Right"],
    ["universal" => "7",  "fdi" => "12", "palmer" => "2⏌", "name" => "Lateral Incisor", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Right"],
    ["universal" => "8",  "fdi" => "11", "palmer" => "1⏌", "name" => "Central Incisor", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Right"],

    // Maxillary Left (Adult)
    ["universal" => "9",  "fdi" => "21", "palmer" => "⎿1", "name" => "Central Incisor", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Left"],
    ["universal" => "10", "fdi" => "22", "palmer" => "⎿2", "name" => "Lateral Incisor", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Left"],
    ["universal" => "11", "fdi" => "23", "palmer" => "⎿3", "name" => "Canine", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Left"],
    ["universal" => "12", "fdi" => "24", "palmer" => "⎿4", "name" => "First Premolar", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Left"],
    ["universal" => "13", "fdi" => "25", "palmer" => "⎿5", "name" => "Second Premolar", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Left"],
    ["universal" => "14", "fdi" => "26", "palmer" => "⎿6", "name" => "First Molar", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Left"],
    ["universal" => "15", "fdi" => "27", "palmer" => "⎿7", "name" => "Second Molar", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Left"],
    ["universal" => "16", "fdi" => "28", "palmer" => "⎿8", "name" => "Third Molar", "dentition_type" => "Adult", "arc" => "Maxillary", "side" => "Left"],

    // Mandibular Left (Adult)
    ["universal" => "17", "fdi" => "38", "palmer" => "┌8", "name" => "Third Molar", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Left"],
    ["universal" => "18", "fdi" => "37", "palmer" => "┌7", "name" => "Second Molar", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Left"],
    ["universal" => "19", "fdi" => "36", "palmer" => "┌6", "name" => "First Molar", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Left"],
    ["universal" => "20", "fdi" => "35", "palmer" => "┌5", "name" => "Second Premolar", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Left"],
    ["universal" => "21", "fdi" => "34", "palmer" => "┌4", "name" => "First Premolar", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Left"],
    ["universal" => "22", "fdi" => "33", "palmer" => "┌3", "name" => "Canine", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Left"],
    ["universal" => "23", "fdi" => "32", "palmer" => "┌2", "name" => "Lateral Incisor", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Left"],
    ["universal" => "24", "fdi" => "31", "palmer" => "┌1", "name" => "Central Incisor", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Left"],

    // Mandibular Right (Adult)
    ["universal" => "25", "fdi" => "41", "palmer" => "1⏋", "name" => "Central Incisor", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Right"],
    ["universal" => "26", "fdi" => "42", "palmer" => "2⏋", "name" => "Lateral Incisor", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Right"],
    ["universal" => "27", "fdi" => "43", "palmer" => "3⏋", "name" => "Canine", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Right"],
    ["universal" => "28", "fdi" => "44", "palmer" => "4⏋", "name" => "First Premolar", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Right"],
    ["universal" => "29", "fdi" => "45", "palmer" => "5⏋", "name" => "Second Premolar", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Right"],
    ["universal" => "30", "fdi" => "46", "palmer" => "6⏋", "name" => "First Molar", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Right"],
    ["universal" => "31", "fdi" => "47", "palmer" => "7⏋", "name" => "Second Molar", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Right"],
    ["universal" => "32", "fdi" => "48", "palmer" => "8⏋", "name" => "Third Molar", "dentition_type" => "Adult", "arc" => "Mandibular", "side" => "Right"],
	
	// === Infant Teeth

    // Maxillary Right (Infant)
    ["universal" => "A", "fdi" => "55", "palmer" => "E⏌", "name" => "Second Molar", "dentition_type" => "Infant", "arc" => "Maxillary", "side" => "Right"],
    ["universal" => "B", "fdi" => "54", "palmer" => "D⏌", "name" => "First Molar", "dentition_type" => "Infant", "arc" => "Maxillary", "side" => "Right"],
    ["universal" => "C", "fdi" => "53", "palmer" => "C⏌", "name" => "Canine", "dentition_type" => "Infant", "arc" => "Maxillary", "side" => "Right"],
    ["universal" => "D", "fdi" => "52", "palmer" => "B⏌", "name" => "Lateral Incisor", "dentition_type" => "Infant", "arc" => "Maxillary", "side" => "Right"],
    ["universal" => "E", "fdi" => "51", "palmer" => "A⏌", "name" => "Central Incisor", "dentition_type" => "Infant", "arc" => "Maxillary", "side" => "Right"],

    // Maxillary Left (Infant)
    ["universal" => "F", "fdi" => "61", "palmer" => "⎿A", "name" => "Central Incisor", "dentition_type" => "Infant", "arc" => "Maxillary", "side" => "Left"],
    ["universal" => "G", "fdi" => "62", "palmer" => "⎿B", "name" => "Lateral Incisor", "dentition_type" => "Infant", "arc" => "Maxillary", "side" => "Left"],
    ["universal" => "H", "fdi" => "63", "palmer" => "⎿C", "name" => "Canine", "dentition_type" => "Infant", "arc" => "Maxillary", "side" => "Left"],
    ["universal" => "I", "fdi" => "64", "palmer" => "⎿D", "name" => "First Molar", "dentition_type" => "Infant", "arc" => "Maxillary", "side" => "Left"],
    ["universal" => "J", "fdi" => "65", "palmer" => "⎿E", "name" => "Second Molar", "dentition_type" => "Infant", "arc" => "Maxillary", "side" => "Left"],

    // Mandibular Left (Infant)
    ["universal" => "K", "fdi" => "75", "palmer" => "┌E", "name" => "Second Molar", "dentition_type" => "Infant", "arc" => "Mandibular", "side" => "Left"],
    ["universal" => "L", "fdi" => "74", "palmer" => "┌D", "name" => "First Molar", "dentition_type" => "Infant", "arc" => "Mandibular", "side" => "Left"],
    ["universal" => "M", "fdi" => "73", "palmer" => "┌C", "name" => "Canine", "dentition_type" => "Infant", "arc" => "Mandibular", "side" => "Left"],
    ["universal" => "N", "fdi" => "72", "palmer" => "┌B", "name" => "Lateral Incisor", "dentition_type" => "Infant", "arc" => "Mandibular", "side" => "Left"],
    ["universal" => "O", "fdi" => "71", "palmer" => "┌A", "name" => "Central Incisor", "dentition_type" => "Infant", "arc" => "Mandibular", "side" => "Left"],

    // Mandibular Right (Infant)
    ["universal" => "P", "fdi" => "81", "palmer" => "A⏋", "name" => "Central Incisor", "dentition_type" => "Infant", "arc" => "Mandibular", "side" => "Right"],
    ["universal" => "Q", "fdi" => "82", "palmer" => "B⏋", "name" => "Lateral Incisor", "dentition_type" => "Infant", "arc" => "Mandibular", "side" => "Right"],
    ["universal" => "R", "fdi" => "83", "palmer" => "C⏋", "name" => "Canine", "dentition_type" => "Infant", "arc" => "Mandibular", "side" => "Right"],
    ["universal" => "S", "fdi" => "84", "palmer" => "D⏋", "name" => "First Molar", "dentition_type" => "Infant", "arc" => "Mandibular", "side" => "Right"],
    ["universal" => "T", "fdi" => "85", "palmer" => "E⏋", "name" => "Second Molar", "dentition_type" => "Infant", "arc" => "Mandibular", "side" => "Right"]

];

// Actualizar la tabla con los códigos
foreach ($tooth_codes as $tooth) {
    $sql = "UPDATE form_odontogram 
            SET universal = '{$tooth['universal']}', 
                fdi = '{$tooth['fdi']}', 
                palmer = '{$tooth['palmer']}' 
            WHERE dentition_type = '{$tooth['dentition_type']}'
              AND arc = '{$tooth['arc']}'
              AND side = '{$tooth['side']}'
              AND name = '{$tooth['name']}'";
              
    $mysqli->query($sql);
}

$mysqli->close();
echo "Numeraciones asignadas correctamente.";
?>
