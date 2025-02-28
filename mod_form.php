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
 * The main mod_minute configuration form.
 *
 * @package     mod_minute
 * @copyright   2025 Ibai Mutiloa <ibaimuga03@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_minute
 * @copyright   2025 Ibai Mutiloa <ibaimuga03@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_minute_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        global $COURSE;
        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('minutename', 'mod_minute'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'minutename', 'mod_minute');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Campo de duración (horas y minutos)
        $mform->addElement('header', 'durationheader', get_string('duration', 'mod_minute'));

        // Campo para horas
        $mform->addElement('select', 'duration_hours', get_string('hours', 'mod_minute'), range(0, 23));
        $mform->setType('duration_hours', PARAM_INT);

        // Campo para minutos
        $mform->addElement('select', 'duration_minutes', get_string('minutes', 'mod_minute'), range(0, 59));
        $mform->setType('duration_minutes', PARAM_INT);

        // Adding the meeting date/time field (Calendar)
        $mform->addElement('date_time_selector', 'meeting_date', get_string('meetingdate', 'mod_minute'));

        $mform->addElement('header', 'memberssection', get_string('members', 'mod_minute'));

        // Get course users.
        $context = context_course::instance($COURSE->id);
        $users = get_enrolled_users($context);

        $options = [];
        foreach ($users as $user) {
            $options[$user->id] = fullname($user);
        }

        $mform->addElement('autocomplete', 'members', get_string('selectmembers', 'mod_minute'), $options, ['multiple' => true]);
        $mform->setType('members', PARAM_INT);
        if (!isset($COURSE->id)) {
            throw new moodle_exception('invalidcourseid', 'error');
        }
        $mform->addElement('hidden', 'course', $COURSE->id);
        $mform->setType('course', PARAM_INT);

        $mform->addElement('header', 'tasksheader', get_string('tasks', 'mod_minute'));

        // Usar el editor de texto Atto para permitir formato enriquecido
        $mform->addElement('editor', 'tasks', get_string('tasklist', 'mod_minute'), null, array('subdirs' => 0, 'maxfiles' => 0, 'trusttext' => 1));
        $mform->setType('tasks', PARAM_RAW);

        $mform->addElement('header', 'tasksheader2', get_string('tasks2', 'mod_minute'));

        // Usar el editor de texto Atto para permitir formato enriquecido
        $mform->addElement('editor', 'tasks2', get_string('tasklist2', 'mod_minute'), null, array('subdirs' => 0, 'maxfiles' => 0, 'trusttext' => 1));
        $mform->setType('tasks2', PARAM_RAW);


        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
