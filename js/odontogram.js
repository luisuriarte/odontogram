let interventionsQueue = [];

$(document).ready(function() {
    var draw = SVG().addTo('#odontogram-svg').size(1048, 704);
    var historyLayer = draw.group().attr('id', 'historyLayer');
    var baseSvgLayer = draw.group().attr('id', 'baseSvgLayer');

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
            console.warn('Element not found for tooth_id:', toothId, 'svgStyle:', svgStyle);
        }
    }

    console.log('Attempting to load SVG from:', webRoot + '/interface/forms/odontogram/assets/odontogram.svg');
    $.get(webRoot + '/interface/forms/odontogram/assets/odontogram.svg', function(svgData) {
        console.log('SVG loaded successfully, length:', svgData.length);
        try {
            baseSvgLayer.svg(svgData);
            console.log('SVG rendered successfully in baseSvgLayer');
            var svgElement = baseSvgLayer.findOne('svg');
            if (svgElement) {
                console.log('SVG element found, child count:', svgElement.node.childElementCount);
            } else {
                console.warn('No SVG element found after rendering');
            }
        } catch (e) {
            console.error('Error rendering SVG with SVG.js:', e.message);
            alert('<?php echo xlj("Error rendering odontogram"); ?>');
            return;
        }

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

            $('#system').change(function() {
                var system = $(this).val();
                showNumberingSystem(system);
                $.ajax({
                    url: webRoot + '/interface/forms/odontogram/new.php',
                    type: 'POST',
                    data: {
                        system: system,
                        csrf_token_form: csrfToken
                    },
                    success: function(response) {
                        let res = JSON.parse(response);
                        if (!res.success) {
                            alert('<?php echo xlj("Error updating preference"); ?>');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('<?php echo xlj("Error updating preference:"); ?> ' + error);
                    }
                });
            }).val(defaultSystem);
        } else {
            console.warn('Numbers layer not found in SVG');
        }

        draw.find('[id^="Complete_"],[id^="Vertical_"],[id^="Distal_"],[id^="Mesial_"],[id^="Lingual_"],[id^="Buccal_"],[id^="Incisal_"],[id^="Occlusal_"]').each(function() {
            this.click(function() {
                var toothId = this.id();
                console.log('Clicked tooth_id:', toothId);
                $.get(webRoot + '/interface/forms/odontogram/php/get_tooth_details.php', {
                    tooth_id: toothId,
                    csrf_token_form: csrfToken
                }, function(data) {
                    console.log('Tooth details response:', data);
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    let numberFormat = $('#system').val();
                    let number = numberFormat === 'FDI' ? data.fdi : numberFormat === 'Palmer' ? data.palmer : data.universal;
                    $('#tooth_info').html(`<?php echo xlj("Tooth"); ?>: ${data.name || 'Unknown'}, <?php echo xlj("Part"); ?>: ${data.part || 'Unknown'}, ${numberFormat}: ${number || 'N/A'}`);
                    $('#tooth_id').val(toothId);
                    $('#toothActionModal').modal('show');
                }).fail(function(xhr, status, error) {
                    console.error('Error fetching tooth details for', toothId, ':', status, error, xhr.responseText);
                    alert('<?php echo xlj("Error fetching tooth details"); ?>');
                });
            });
        });

        console.log('Initial history load with default dates:', $('#start').val(), $('#end_date').val());
        loadHistory();
    }, 'text').fail(function(jqXHR, textStatus) {
        console.error('Error loading SVG:', textStatus, jqXHR.status, jqXHR.responseText);
        alert('<?php echo xlj("Error loading SVG:"); ?> ' + textStatus + ' (Status: ' + jqXHR.status + ')');
    });

    $('#editTooth').click(function() {
        $('#toothActionModal').modal('hide');
        $('#intervention_type').change(function() {
            let type = $(this).val();
            $.get(webRoot + '/interface/forms/odontogram/php/get_options.php', { csrf_token_form: csrfToken }, function(data) {
                let options = [];
                if (type === 'Diagnosis') options = data.odonto_diagnosis || [];
                else if (type === 'Issue') options = data.odonto_issue || [];
                else if (type === 'Procedure') options = data.odonto_procedures || [];
                
                $('#option_id').empty();
                options.forEach(opt => {
                    $('#option_id').append(`<option value="${opt.option_id}" data-style="${opt.notes}" data-code="${opt.codes}">${opt.title}</option>`);
                });
            });
        }).trigger('change');
        $('#interventionModal').modal('show');
    });

    $('#viewHistory').click(function() {
        let toothId = $('#tooth_id').val();
        $.get(webRoot + '/interface/forms/odontogram/php/get_history.php', { tooth_id: toothId, pid: patientId, csrf_token_form: csrfToken }, function(data) {
            if (data.error) {
                alert(data.error);
                return;
            }
            let historyHtml = '<table class="table"><tr><th><?php echo xlt("Date"); ?></th><th><?php echo xlt("Type"); ?></th><th><?php echo xlt("Option"); ?></th><th><?php echo xlt("Notes"); ?></th></tr>';
            data.forEach(item => {
                historyHtml += `<tr><td>${item.date}</td><td>${item.intervention_type}</td><td>${item.option_id}</td><td>${item.notes}</td></tr>`;
            });
            historyHtml += '</table>';
            $('#toothActionModal .modal-body').html(historyHtml);
        });
    });

    $('#addIntervention').click(function() {
        let intervention = {
            tooth_id: $('#tooth_id').val(),
            intervention_type: $('#intervention_type').val(),
            option_id: $('#option_id').val(),
            code: $('#option_id option:selected').data('code'),
            notes: $('#notes').val(),
            svg_style: $('#option_id option:selected').data('style')
        };

        if (!intervention.tooth_id || !intervention.intervention_type || !intervention.option_id) {
            alert('<?php echo xlj("Please complete all fields"); ?>');
            return;
        }

        interventionsQueue = interventionsQueue.filter(i => i.tooth_id !== intervention.tooth_id);
        interventionsQueue.push(intervention);

        applyStyles(intervention.tooth_id, intervention.svg_style);

        $('#queue_number').text(interventionsQueue.length);

        $('#interventionModal').modal('hide');
        alert('<?php echo xlj("Intervention added to queue"); ?>');
    });

    $('#saveAllInterventions').click(function() {
        if (interventionsQueue.length === 0) {
            alert('<?php echo xlj("No interventions to save"); ?>');
            return;
        }

        $.ajax({
            url: webRoot + '/interface/forms/odontogram/save.php',
            method: 'POST',
            data: {
                interventions: JSON.stringify(interventionsQueue),
                csrf_token_form: csrfToken,
                formid: formId
            },
            success: function(response) {
                let res = JSON.parse(response);
                alert(res.message);
                if (res.success) {
                    interventionsQueue = [];
                    $('#queue_number').text(0);
                    loadHistory();
                }
            },
            error: function() {
                alert('<?php echo xlj("Error saving interventions"); ?>');
            }
        });
    });

    $('#filterHistory').click(function() {
        let startDate = new Date($('#start').val());
        let endDate = new Date($('#end_date').val());
        if (startDate > endDate) {
            alert('<?php echo xlj("From date cannot be greater than To date"); ?>');
            $('#start').val('');
            return;
        }
        console.log('Filtering with start:', $('#start').val(), 'end:', $('#end_date').val(), 'types:', $('.intervention-type:checked').map(function() { return $(this).val(); }).get());
        loadHistory();
    });

    $('#cancelForm').click(function(e) {
        e.preventDefault();
        top.restoreSession();
        parent.closeTab(window.name, false);
    });

    function loadHistory() {
        let startDate = $('#start').val();
        let endDate = $('#end_date').val();
        let interventionTypes = $('.intervention-type:checked').map(function() {
            return $(this).val();
        }).get();

        let typesParam = interventionTypes.length > 0 ? interventionTypes.join(',') : '';

        console.log('Sending history request:', {
            pid: patientId,
            start: startDate,
            end_date: endDate,
            intervention_types: typesParam,
            csrf_token_form: csrfToken
        });

        if (!startDate || !endDate) {
            console.warn('No dates selected, skipping history load');
            return;
        }

        $.ajax({
            url: webRoot + '/interface/forms/odontogram/php/get_history.php',
            method: 'GET',
            data: {
                pid: patientId,
                start: startDate,
                end_date: endDate,
                intervention_types: typesParam,
                csrf_token_form: csrfToken
            },
            success: function(history) {
                console.log('History response:', history);
                if (history.error) {
                    console.error('History error:', history.error);
                    alert(history.error);
                    return;
                }
                if (!Array.isArray(history)) {
                    console.warn('Expected array, got:', history);
                    alert('<?php echo xlj("Invalid history data"); ?>');
                    return;
                }

                console.log('Clearing history layer, preserving baseSvgLayer');
                historyLayer.clear();

                var latestStyles = {};
                history.forEach(function(item) {
                    if (item.tooth_id) {
                        latestStyles[item.tooth_id] = {
                            svg_style: item.svg_style,
                            date: item.date
                        };
                    }
                });

                console.log('Applying history styles:', Object.keys(latestStyles));
                Object.keys(latestStyles).forEach(function(toothId) {
                    var styleData = latestStyles[toothId];
                    applyStyles(toothId, styleData.svg_style, styleData.date);
                });

                var allTeeth = draw.find('[id^="Complete_"],[id^="Vertical_"],[id^="Distal_"],[id^="Mesial_"],[id^="Lingual_"],[id^="Buccal_"],[id^="Incisal_"],[id^="Occlusal_"]');
                console.log('Found teeth for default styling:', allTeeth.length);
                allTeeth.forEach(function(tooth) {
                    var toothId = tooth.id();
                    if (!latestStyles[toothId] && toothId) {
                        applyStyles(toothId, 'fill: none');
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error('Error loading history:', status, error, xhr.responseText);
                alert('<?php echo xlj("Error loading history:"); ?> ' + error);
            }
        });
    }
});