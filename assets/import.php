<?php
$mysqli = new mysqli("localhost", "openemr", "S4nC4rl0sC3ntr0", "openemr");

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Vaciar la tabla teeth antes de la inserción
$mysqli->query("TRUNCATE TABLE form_odontogram");
	
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

            $d = $dMatch[1] ?? null;
            $x = $xMatch[1] ?? null;
            $y = $yMatch[1] ?? null;
            $width = $widthMatch[1] ?? null;
            $height = $heightMatch[1] ?? null;
            $sodipodi = isset($sodipodiMatch[1]) && isset($sodipodiMatch[2]) ? $sodipodiMatch[1] . '="' . $sodipodiMatch[2] . '"' : null;

            // Determinar si el tipo de SVG es "path" o "rect"
            $svg_type = strpos($elemento, "<rect") !== false ? "rect" : "path";

            // Si el tipo es rect, poner d como NULL
            if ($svg_type === "rect") {
                $d = NULL;
            }

            // Preparar la consulta SQL para insertar los datos en la tabla `teeth`
            if ($tooth_id) {
                $sql = "INSERT INTO form_odontogram (dentition_type, name, part, arc, side, tooth_id, d, width, height, x, y, sodipodi, svg_type)
                        VALUES ('$dentition_type', '$name', '$part', '$arc', '$side', '$tooth_id', " . ($d ? "'$d'" : "NULL") . ",
                                " . ($width ? "'$width'" : "NULL") . ", " . ($height ? "'$height'" : "NULL") . ",
                                " . ($x ? "'$x'" : "NULL") . ", " . ($y ? "'$y'" : "NULL") . ", 
                                " . ($sodipodi ? "'$sodipodi'" : "NULL") . ", '$svg_type')
                        ON DUPLICATE KEY UPDATE 
                            d=" . ($d ? "'$d'" : "NULL") . ",
                            width=" . ($width ? "'$width'" : "NULL") . ", height=" . ($height ? "'$height'" : "NULL") . ",
                            x=" . ($x ? "'$x'" : "NULL") . ", y=" . ($y ? "'$y'" : "NULL") . ",
                            sodipodi=" . ($sodipodi ? "'$sodipodi'" : "NULL") . ", 
                            svg_type='$svg_type'";

                // Ejecutar la consulta SQL
                $mysqli->query($sql);
            }
        } else {
            echo "No se pudo extraer correctamente los datos del id: $tooth_id\n";
        }
    }
}

$mysqli->close();
echo "Importación completada.";
?>
