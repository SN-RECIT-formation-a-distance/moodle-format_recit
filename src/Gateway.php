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
 * Set the gateway.
 *
 * @copyright  RECITFAD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once('renderer.php');
require_once($CFG->dirroot.'/course/format/lib.php');
require_once($CFG->dirroot.'/course/lib.php');


require_login();

$webApi = new WebApi($DB);
$webApi->readRequest();
$webApi->processRequest();

/**
 * FormatRecit Web API.
 *
 * @author RECITFAD
 */
class WebApi{
    /** @var mysqli_native_moodle_database */
    protected $db = null;

    /** @var mysqli */
    protected $mysqli = null;

    /** @var stdClass */
    protected $request = null;

    /**
     * @param mysqli_native_moodle_database $db
     */
    public function __construct($db){
        $this->db = $db;
    }

    /**
     * Read the input data
     */
    public function readRequest(){
        $this->request = (object) json_decode(file_get_contents('php://input'), true);
        $this->request->data = (object) $this->request->data;
    }

    /**
     * Process the request
     */
    public function processRequest(){
        $serviceWanted = $this->request->service;

		$classInstance = null;
		if(method_exists($this, $serviceWanted)){
            $this->$serviceWanted();
        }
        else{
            $this->reply(false, null, "The service $serviceWanted was not found.");
        }
    }


    /**
     * Reply the client
     */
    protected function reply($success, $data = null, $msg = ""){
        $result = new stdClass();
        $result->success = $success;
        $result->data = $data;
        $result->msg = $msg;

        echo json_encode($result);
    }

    /**
     * Set the section level
     */
    protected function set_section_level() {
        global $CFG, $DB;
        try {

            $data = $this->request->data;
            $DB->execute("insert into {course_format_options} (courseid, format, sectionid, name, value)
            values(?, 'recit', ?, 'sectionlevel', ?)
            ON DUPLICATE KEY UPDATE value = ?", [$data->courseId, $data->sectionId, $data->level, $data->level]);
            $DB->execute("insert into {format_recit_options} (courseid, sectionid, name, value)
            values(?, ?, 'sectionlevel', ?)
            ON DUPLICATE KEY UPDATE value = ?", [$data->courseId, $data->sectionId, $data->level, $data->level]);

            $this->reply(true);
        } catch (Exception $ex) {
            $this->reply(false, null, $ex->GetMessage());
        }
    }

    /**
     * @return void
     */
    protected function get_section_content() {
        global $PAGE;

        try {
            $data = $this->request->data;

            $params = array('id' => $data->courseid);
            $course = $this->db->get_record('course', $params, '*', MUST_EXIST);

            $PAGE->set_context(context_course::instance($data->courseid, MUST_EXIST));
            $PAGE->set_course($course);

            $tt = new FormatRecit();
            $tt->load($PAGE->get_renderer('format_recit'), course_get_format($course)->get_course());

            $this->reply(true, $tt->render_section_content($data->sectionid));

        } catch (Exception $ex) {
            $this->reply(false, null, $ex->GetMessage());
        }
    }

    /**
     * Set the section level
     */
    protected function move_module_to_section() {
        global $CFG, $DB;
        try {
            $prefix = $CFG->prefix;

            $data = $this->request->data;

            $modulerecords = $DB->get_records_select('course_modules', 'ID IN (' . implode(',', array_fill(0, count($data->modules), '?')) . ')', $data->modules);
            foreach ($modulerecords as $modulerecord) {
                $cm = $this->validate_module($modulerecord->id);
        
                // Verify target section.
                $section = $this->validate_section($cm->course, $data->sectionId);

                $context = context_course::instance($section->course);
                require_capability('moodle/course:manageactivities', $context);
        
                moveto_module($modulerecord, $section);
            }
            $this->reply(true);
        } catch (Exception $ex) {
            $this->reply(false, null, $ex->GetMessage());
        }
    }

    protected function delete_modules() {
        global $CFG, $DB;
        try {
            $prefix = $CFG->prefix;

            $data = $this->request->data;

            $modulerecords = $DB->get_records_select('course_modules', 'ID IN (' . implode(',', array_fill(0, count($data->modules), '?')) . ')', $data->modules);
            foreach ($modulerecords as $modulerecord) {
                $cm = $this->validate_module($modulerecord->id);

                $context = context_course::instance($cm->course);
                require_capability('moodle/course:manageactivities', $context);
        
                course_delete_module($cm->id);
            }
            $this->reply(true);
        } catch (Exception $ex) {
            $this->reply(false, null, $ex->GetMessage());
        }
    }

    protected function set_modules_visible() {
        global $CFG, $DB;
        try {
            $prefix = $CFG->prefix;

            $data = $this->request->data;

            $modulerecords = $DB->get_records_select('course_modules', 'ID IN (' . implode(',', array_fill(0, count($data->modules), '?')) . ')', $data->modules);
            foreach ($modulerecords as $modulerecord) {
                $cm = $this->validate_module($modulerecord->id);

                $context = context_course::instance($data->courseId);
                require_capability('moodle/course:manageactivities', $context);
        
                set_coursemodule_visible($cm->id, $data->isVisible);
            }
            $this->reply(true);
        } catch (Exception $ex) {
            print_r($ex);
            $this->reply(false, null, $ex->GetMessage());
        }
    }

    protected function delete_section() {
        global $CFG, $DB;
        try {
            $prefix = $CFG->prefix;

            $data = $this->request->data;

            $course = $DB->get_record('course', array('id' => $data->courseId), '*', MUST_EXIST);
            $section = $this->validate_section((int)$data->courseId, $data->sectionId);
            $context = context_course::instance($section->course);
            require_capability('moodle/course:manageactivities', $context);

            course_delete_section($course, $data->sectionId, true);
            
            $this->reply(true);
        } catch (Exception $ex) {
            $this->reply(false, null, $ex->GetMessage());
        }
    }
    
    /**
     * Checks that the $target section exists in the course.
     *
     * @param int $course Course id
     * @param int $target Section number
     *
     * @return object $section Section database record
     */
    private function validate_section($course, $target) {
        global $DB;

        $section = $DB->get_record('course_sections',
            array('course' => $course, 'section' => $target));

        if (!$section) {
            throw new Exception('sectionnotexist'.$course.' '.$target);
        } else {
            return $section;
        }
    }

    /**
     * Checks that the module exists.
     *
     * @param int $moduleid Module id
     *
     * @return object $cm Course module database record
     */
    private function validate_module($moduleid) {
        if (!$cm = get_coursemodule_from_id('', $moduleid, 0, true)) {
            print_error('invalidcoursemodule');
        } else {
            return $cm;
        }
    }
}