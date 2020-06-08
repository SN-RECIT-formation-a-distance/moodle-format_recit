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

require_login();

$refmoodledb = new ReflectionObject($DB);
$refprop1 = $refmoodledb->getProperty('mysqli');
$refprop1->setAccessible(true);
$mysqli = $refprop1->getValue($DB);

$request = (object) json_decode(file_get_contents('php://input'), true);
$data = (object) $request->data;

if ($request->service == 'setSectionLevel') {
    $query = "insert into mdl_course_format_options (courseid, format, sectionid, name, value)
        values($data->courseId, 'treetopics', $data->sectionId, 'ttsectiondisplay', '$data->level')
        ON DUPLICATE KEY UPDATE value = '$data->level'";
} else if ($request->service == 'setSectionContentDisplay') {
    $query = "insert into mdl_course_format_options (courseid, format, sectionid, name, value)
    values($data->courseId, 'treetopics', $data->sectionId, 'ttsectioncontentdisplay', '$data->value')
    ON DUPLICATE KEY UPDATE value = '$data->value'";
}

$result = new stdClass();
$result->success = false;

try {
    $mysqli->query($query);
    $result->success = true;
} catch (Exception $ex) {
    $result->msg = $ex->GetMessage();
}

echo json_encode($result);
