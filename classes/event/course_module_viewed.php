<?php

namespace mod_minute\event;

defined('MOODLE_INTERNAL') || die();

class course_module_viewed extends \core\event\course_module_viewed {

    protected function init() {
        $this->data['objecttable'] = 'minute'; // nombre de tu tabla principal
        parent::init();
    }
}
