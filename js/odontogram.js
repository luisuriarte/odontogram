$(document).ready(function() {
    console.log("odontogram.js cargado");
    console.log("jQuery versión: ", jQuery.fn.jquery);

    // Crear un contenedor SVG con SVG.js
    var draw = SVG().addTo('#odontogram-svg').size(1045, 690);

    // Cargar el SVG desde la URL
    $.get('/interface/forms/odontogram/assets/odontogram.svg', function(svgData) {
        // Convertir el SVG a un elemento DOM
        var svgDoc = new DOMParser().parseFromString(svgData, 'image/svg+xml');
        var svgElement = svgDoc.documentElement;

        // Añadir el SVG al contenedor
        draw.svg(svgData);

        // Acceder a los grupos
        var numbersLayer = draw.findOne('#Numbers');
        if (numbersLayer) {
            console.log("Capa #Numbers encontrada");
            var fdi = numbersLayer.findOne('#FDI');
            var universal = numbersLayer.findOne('#Universal');
            var palmer = numbersLayer.findOne('#Palmer');
            console.log("Grupos FDI: ", fdi ? 1 : 0);
            console.log("Grupos Universal: ", universal ? 1 : 0);
            console.log("Grupos Palmer: ", palmer ? 1 : 0);

            // Ocultar todos inicialmente
            if (fdi) fdi.hide();
            if (universal) universal.hide();
            if (palmer) palmer.hide();
        } else {
            console.log("Capa #Numbers NO encontrada");
        }

        // Cambiar sistema de numeración
        $('#numbering_system').change(function() {
            var system = $(this).val();
            console.log("Sistema seleccionado: " + system);

            if (fdi) fdi.hide();
            if (universal) universal.hide();
            if (palmer) palmer.hide();

            var selectedGroup = numbersLayer ? numbersLayer.findOne('#' + system) : null;
            if (selectedGroup) {
                selectedGroup.show();
                console.log("Mostrando: #" + system);
            } else {
                console.log("Grupo #" + system + " no encontrado");
            }

            // Guardar preferencia
            $.ajax({
                url: '/interface/forms/odontogram/new.php',
                type: 'POST',
                data: { system: system },
                success: function(response) {
                    console.log("Preferencia guardada: " + system);
                },
                error: function(xhr, status, error) {
                    console.error("Error al guardar preferencia: " + error);
                }
            });
        });

        console.log("Sistema restaurado: " + defaultSystem);
        $('#numbering_system').val(defaultSystem).trigger('change');
    }, 'text').fail(function(jqXHR, textStatus, errorThrown) {
        console.error("Error al cargar el SVG:", textStatus, errorThrown);
    });
});