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

/**
 * Treetopics lib file.
 *
 * @package    format_treetopics
 * @copyright  RECITFAD
 * @author     RECITFAD
 * @license    {@link http://www.gnu.org/licenses/gpl-3.0.html} GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function treetopics_completion_callback(){
    global $USER, $COURSE, $DB;

    $course = course_get_format($COURSE)->get_course();
    if (!isset($course->ttcustompath) || $course->ttcustompath == 0 || $course->enablecompletion == 0) return;
    
    $modinfo = get_fast_modinfo($course);

    foreach ($modinfo->get_section_info_all() as $section){
        if ($section->available){
            foreach ($modinfo->cms as $cm){
                if ($cm->section == $section->id && $cm->completion == 1){
                    $result = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cm->id, 'userid' => $USER->id));
                    if (!$result || $result->completionstate == 0) return;
                }
            }
        }
    }

    $params = array(
        'userid'    => $USER->id,
        'course'  => $COURSE->id
    );

    $ccompletion = new completion_completion($params);
    return $ccompletion->mark_complete();
}