<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants.
 *
 * @package     mod_minute
 * @copyright   2025 Ibai Mutiloa <ibaimuga03@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function minute_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_minute into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_minute_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function minute_add_instance($moduleinstance, $mform = null) {
    global $DB;

    // Verificar si course está definido
    error_log('Course ID received: ' . print_r($moduleinstance->course, true));

    if (empty($moduleinstance->course) || !$DB->record_exists('course', ['id' => $moduleinstance->course])) {
        throw new moodle_exception('invalidcourseid', 'error');
    }

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('minute', $moduleinstance);

    if (!empty($moduleinstance->members) && is_array($moduleinstance->members)) {
        foreach ($moduleinstance->members as $userid) {
            $member = new stdClass();
            $member->minuteid = $id;
            $member->userid = $userid;
            $DB->insert_record('minute_members', $member);
        }
    }
    // Llamar a la función para generar el PDF después de insertar los datos
    generate_minutes_pdf($moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_minute in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_minute_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function minute_update_instance($moduleinstance, $mform = null) {
    global $DB;

    // Actualizar la marca de tiempo
    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    // Actualizar los datos en la base de datos
    $updated = $DB->update_record('minute', $moduleinstance);

    // Si la actualización fue exitosa, generar el PDF
    if ($updated) {
        generate_minutes_pdf($moduleinstance);
    }

    return $updated;
}

/**
 * Removes an instance of the mod_minute from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function minute_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('minute', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('minute', array('id' => $id));

    return true;
}

require_once($CFG->dirroot . '/lib/tcpdf/tcpdf.php');
/**
 * Función para generar el PDF con los datos del formulario
 *
 * @param object $moduleinstance Los datos del formulario
 */
function generate_minutes_pdf($moduleinstance) {
    global $DB;

    // Obtener los miembros asociados a la instancia
    $members = $DB->get_records('minute_members', array('minuteid' => $moduleinstance->id));

    $member_names = [];
    foreach ($members as $member) {
        // Obtener el nombre completo de cada usuario
        $user = $DB->get_record('user', array('id' => $member->userid));
        if ($user) {
            $member_names[] = fullname($user);
        }
    }

    // Crear el PDF usando TCPDF
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    // Título del PDF
    $pdf->Cell(0, 10, 'Meeting Minutes: ' . $moduleinstance->name, 0, 1, 'C');

    // Tareas para la reunión actual
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Tasks to do:', 0, 1);

    if (!empty($moduleinstance->tasks2)) {
        if (is_array($moduleinstance->tasks2)) {
            $moduleinstance->tasks2 = implode(', ', $moduleinstance->tasks2);
        }
        $tasks2_plain_text = strip_tags($moduleinstance->tasks2);
        $pdf->MultiCell(0, 10, $tasks2_plain_text);
    } else {
        $pdf->MultiCell(0, 10, 'No tasks to do defined.');
    }

    // Tareas
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Tasks for next meeting:', 0, 1);

    // Verificar si tasks está vacío y manejarlo
    if (!empty($moduleinstance->tasks)) {
        // Si tasks es un array, convertirlo a string.
        if (is_array($moduleinstance->tasks)) {
            $moduleinstance->tasks = implode(', ', $moduleinstance->tasks);
        }
        // Eliminar cualquier etiqueta HTML si es necesario
        $tasks_plain_text = strip_tags($moduleinstance->tasks);
        // Añadir las tareas al PDF
        $pdf->MultiCell(0, 10, $tasks_plain_text);
    } else {
        $pdf->MultiCell(0, 10, 'No tasks defined.');
    }

    // Miembros
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Members:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    if (count($member_names) > 0) {
        foreach ($member_names as $name) {
            $pdf->Cell(0, 10, $name, 0, 1);
        }
    } else {
        $pdf->Cell(0, 10, 'No members assigned.');
    }

    // Duración
    $duration = sprintf("%02d:%02d", $moduleinstance->duration_hours, $moduleinstance->duration_minutes);
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Duration: ' . $duration, 0, 1);

    // Fecha de la reunión
    $meeting_date = userdate($moduleinstance->meeting_date);
    $pdf->Cell(0, 10, 'Meeting Date: ' . $meeting_date, 0, 1);

    // Generar el archivo PDF
    $pdf->Output('meeting_minutes_' . $moduleinstance->id . '.pdf', 'D');
}

/**
 * Trigger the course module viewed event.
 *
 * @param object $moduleinstance The module instance object.
 */
function minute_view($moduleinstance) {
    global $DB, $PAGE;

    // Verificar si el módulo es visualizado
    $context = context_module::instance($moduleinstance->cmid);
    $event = \mod_minute\event\course_module_viewed::create(array(
        'objectid' => $moduleinstance->id,
        'context' => $context,
        'courseid' => $moduleinstance->course
    ));

    // Disparar el evento
    $event->trigger();
}
