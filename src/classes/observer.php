<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


defined('MOODLE_INTERNAL') || die();

class format_recit_observer {

    /**
     * Triggered via \core\event\course_updated event.
     *
     * @param \core\event\course_updated $event
     */
    public static function course_updated(\core\event\course_updated $event) {
        global $DB;
        if (class_exists('format_recit', false)) {
            $opts = $DB->get_records('course_format_options', array('format' => 'recit', 'courseid' => $event->courseid, 'name' => 'sectionlevel'));
            if (empty($opts)){
                $recitopts = $DB->get_records('format_recit_options', array('courseid' => $event->courseid, 'name' => 'sectionlevel'));
                if (!empty($recitopts)){
                    foreach($recitopts as $data){
                        $DB->execute("insert into {course_format_options} (courseid, format, sectionid, name, value)
                        values(?, 'recit', ?, 'sectionlevel', ?)
                        ON DUPLICATE KEY UPDATE value = ?", [$data->courseid, $data->sectionid, $data->value, $data->value]);
                    }
                }

            }
        }
    }
}