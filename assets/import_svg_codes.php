<?php
$mysqli = new mysqli("localhost", "database", "password", "username");

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Configurar codificación UTF-8
$mysqli->set_charset("utf8mb4") or die("Error al configurar UTF-8: " . $mysqli->error);

// SQL script:
// CREATE TABLE `form_odontogram` (
//     `id` int(11) NOT NULL AUTO_INCREMENT,
//     `universal` varchar(10) DEFAULT NULL,
//     `fdi` varchar(10) DEFAULT NULL,
//     `palmer` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
//     `dentition_type` enum('Infant','Adult') DEFAULT NULL,
//     `name` varchar(50) DEFAULT NULL,
//     `part` enum('Complete','Vertical','Distal','Mesial','Lingual','Buccal','Incisal','Occlusal') DEFAULT NULL,
//     `arc` enum('Maxillary','Mandibular') DEFAULT NULL,
//     `style` text DEFAULT NULL,
//     `side` enum('Left','Right') DEFAULT NULL,
//     `tooth_id` varchar(60) DEFAULT NULL,
//     `d` text DEFAULT NULL,
//     `width` float DEFAULT NULL,
//     `height` float DEFAULT NULL,
//     `x` float DEFAULT NULL,
//     `y` float DEFAULT NULL,
//     `sodipodi` varchar(100) DEFAULT NULL,
//     `svg_type` varchar(20) DEFAULT NULL,
//     PRIMARY KEY (`id`),
//     UNIQUE KEY `svg_id_idx` (`tooth_id`),
//     KEY `odontogram_id_IDX` (`id`) USING BTREE
//   ) ENGINE=InnoDB AUTO_INCREMENT=356 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

// Vaciar la tabla teeth antes de la inserción
$mysqli->query("TRUNCATE TABLE form_odontogram") or die("Error al vaciar la tabla: " . $mysqli->error);
	
$archivoSVG = "odontogram.svg";  // Ruta del archivo SVG
$contenido = file_get_contents($archivoSVG);

// Buscar elementos <rect> y <path>
preg_match_all('/<(rect|path)[^>]+\/>/', $contenido, $coincidencias);

foreach ($coincidencias[0] as $elemento) {
    // Obtener el id (por ejemplo: Vertical_AdultThirdMolarMaxillaryRight)
    preg_match('/id="([^"]+)"/', $elemento, $idMatch);

    // Extraer el id completo
    $tooth_id = $idMatch[1] ?? null;

    // Filtrar solo los id que comienzan con los prefijos especificados
    if ($tooth_id && preg_match('/^(Complete_|Vertical_|Distal_|Mesial_|Lingual_|Buccal_|Incisal_|Occlusal_)/', $tooth_id)) {
        // Intentar extraer dentition_type, name, part, arc y side del id con una expresión regular mejorada
        preg_match('/^(Complete|Vertical|Distal|Mesial|Lingual|Buccal|Incisal|Occlusal)_(Adult|Infant)([A-Za-z\s]+)(Maxillary|Mandibular)(Left|Right)$/', $tooth_id, $matches);

        // Verificar si la expresión regular encontró coincidencias
        if (count($matches) === 6) {
            $part = $matches[1] ?? null;  // "Complete", "Vertical", "Distal", etc.
            $dentition_type = $matches[2] ?? null;  // "Adult" o "Infant"
            $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $matches[3]);  // "First Molar", "Lateral Incisor", etc.
            $arc = $matches[4] ?? null;  // "Maxillary" o "Mandibular"
            $side = $matches[5] ?? null;  // "Left" o "Right"

            // Filtrar el valor de part para que solo sea uno de los valores permitidos
            $valid_parts = ['Complete', 'Vertical', 'Distal', 'Mesial', 'Lingual', 'Buccal', 'Incisal', 'Occlusal'];
            if (!in_array($part, $valid_parts)) {
                continue; // Si el valor no es válido, saltamos a la siguiente iteración
            }

            // Buscar los atributos de <path /> o <rect />
            preg_match('/d="([^"]+)"/', $elemento, $dMatch);
            preg_match('/x="([^"]+)"/', $elemento, $xMatch);
            preg_match('/y="([^"]+)"/', $elemento, $yMatch);
            preg_match('/width="([^"]+)"/', $elemento, $widthMatch);
            preg_match('/height="([^"]+)"/', $elemento, $heightMatch);
            preg_match('/sodipodi:([a-zA-Z]+)="([^"]+)"/', $elemento, $sodipodiMatch);
            preg_match('/style="([^"]+)"/', $elemento, $styleMatch);

            $d = $dMatch[1] ?? null;
            $x = $xMatch[1] ?? null;
            $y = $yMatch[1] ?? null;
            $width = $widthMatch[1] ?? null;
            $height = $heightMatch[1] ?? null;
            $sodipodi = isset($sodipodiMatch[1]) && isset($sodipodiMatch[2]) ? $sodipodiMatch[1] . '="' . $sodipodiMatch[2] . '"' : null;
            $style = $styleMatch[1] ?? null;

            // Determinar si el tipo de SVG es "path" o "rect"
            $svg_type = strpos($elemento, "<rect") !== false ? "rect" : "path";

            // Si el tipo es rect, poner d como NULL
            if ($svg_type === "rect") {
                $d = NULL;
            }

            // Preparar la consulta SQL para insertar los datos en la tabla `teeth`
            if ($tooth_id) {
                $sql = "INSERT INTO form_odontogram (dentition_type, name, part, arc, side, tooth_id, d, width, height, x, y, sodipodi, svg_type, style)
                VALUES ('$dentition_type', '$name', '$part', '$arc', '$side', '$tooth_id', " . ($d ? "'$d'" : "NULL") . ",
                        " . ($width ? "'$width'" : "NULL") . ", " . ($height ? "'$height'" : "NULL") . ",
                        " . ($x ? "'$x'" : "NULL") . ", " . ($y ? "'$y'" : "NULL") . ", 
                        " . ($sodipodi ? "'$sodipodi'" : "NULL") . ", '$svg_type',
                        " . ($style ? "'$style'" : "NULL") . ")
                ON DUPLICATE KEY UPDATE 
                    d=" . ($d ? "'$d'" : "NULL") . ",
                    width=" . ($width ? "'$width'" : "NULL") . ", height=" . ($height ? "'$height'" : "NULL") . ",
                    x=" . ($x ? "'$x'" : "NULL") . ", y=" . ($y ? "'$y'" : "NULL") . ",
                    sodipodi=" . ($sodipodi ? "'$sodipodi'" : "NULL") . ", 
                    svg_type='$svg_type',
                    style=" . ($style ? "'$style'" : "NULL");

                // Ejecutar la consulta SQL
                $mysqli->query($sql);
            }
        } else {
            echo "No se pudo extraer correctamente los datos del id: $tooth_id\n";
        }
    }
}

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
echo "Importación completada.";
?>
