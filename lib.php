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

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('minute', $moduleinstance);
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
