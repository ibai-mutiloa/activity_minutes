<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/minute/lib.php');

$id = required_param('id', PARAM_INT);

// Obtener el curso y el contexto del módulo
$cm = get_coursemodule_from_id('minute', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$context = context_module::instance($cm->id);

// Verificar si el módulo 'minute' existe en la base de datos
$moduleinstance = $DB->get_record('minute', array('id' => $cm->instance), '*', MUST_EXIST);

// Verificar permisos de acceso
require_login($course, true, $cm);
require_capability('mod_minute:view', $context);

// Generar el PDF y guardarlo en un buffer
ob_start();
generate_minutes_pdf($moduleinstance);
$pdf_output = ob_get_clean();

// Definir las cabeceras para forzar la descarga del archivo PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="minuto_' . $moduleinstance->id . '.pdf"');
header('Content-Length: ' . strlen($pdf_output));

// Enviar el contenido del archivo PDF
echo $pdf_output;
exit;
