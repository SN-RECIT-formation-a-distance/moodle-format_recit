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

require_login();

$webApi = new WebApi($DB);
$webApi->readRequest();
$webApi->processRequest();

/**
 * TreeTopics Web API.
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
        $refmoodledb = new ReflectionObject($db);
        $refprop1 = $refmoodledb->getProperty('mysqli');
        $refprop1->setAccessible(true);
        $this->mysqli = $refprop1->getValue($db);
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
        global $CFG;
        try {
            $prefix = $CFG->prefix;

            $data = $this->request->data;
            $query = "insert into {$prefix}course_format_options (courseid, format, sectionid, name, value)
            values($data->courseId, 'treetopics', $data->sectionId, 'ttsectiondisplay', '$data->level')
            ON DUPLICATE KEY UPDATE value = '$data->level'";

            $this->mysqli->query($query);
            $this->reply(true);
        } catch (Exception $ex) {
            $this->reply(false, null, $ex->GetMessage());
        }
    }

    /**
     * @return void
     */
    protected function set_section_content_display() {
        global $CFG;
       
        try {
            $prefix = $CFG->prefix;

            $data = $this->request->data;
            $query = "insert into {$prefix}course_format_options (courseid, format, sectionid, name, value)
            values($data->courseId, 'treetopics', $data->sectionId, 'ttsectioncontentdisplay', '$data->value')
            ON DUPLICATE KEY UPDATE value = '$data->value'";

            $this->mysqli->query($query);
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

            $urlparams = array('id' => $data->courseid);
            $params = array('id' => $data->courseid);
            $course = $this->db->get_record('course', $params, '*', MUST_EXIST);

            $PAGE->set_context(context_course::instance($data->courseid, MUST_EXIST));
            $PAGE->set_course($course);
            $render = $PAGE->get_renderer('format_treetopics');
            //$render = new format_treetopics_renderer($PAGE, $course);
            $tt = new treetopics();
            $tt->load($render, course_get_format($course)->get_course());

            $this->reply(true, $tt->render_section_content($data->sectionid));

        } catch (Exception $ex) {
            $this->reply(false, null, $ex->GetMessage());
        }
    }

    /**
     * @return void
     */
    /*protected function get_section_content_editingmode() {
        global $PAGE;

        try {
            $data = $this->request->data;

            $urlparams = array('id' => $data->courseid);
            $params = array('id' => $data->courseid);
            $course = $this->db->get_record('course', $params, '*', MUST_EXIST);

            $PAGE->set_context(context_course::instance($data->courseid, MUST_EXIST));
            
            $render = new format_treetopics_renderer($PAGE, $course);
            $tt = new treetopics();
            $tt->load($render, course_get_format($course)->get_course());
            
            $this->reply(true, $tt->render_editing_mode_section_content($render, $data->sectionid));

        } catch (Exception $ex) {
            $this->reply(false, null, $ex->GetMessage());
        }
    }*/
}