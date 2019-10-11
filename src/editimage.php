<?php
// This file is part of a plugin written to be used on the free teaching platform : Moodle
// Copyright (C) 2019 recit
// 
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.
//
// @package    format_treetopics
// @subpackage RECIT
// @copyright  RECIT {@link https://recitfad.ca}
// @author     RECIT {@link https://recitfad.ca}
// @license    {@link http://www.gnu.org/licenses/gpl-3.0.html} GNU GPL v3 or later
// @developer  Studio XP : {@link https://www.studioxp.ca}

/* Imports */
require_once('../../../config.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/course/format/treetopics/editimage_form.php');
require_once($CFG->dirroot . '/course/format/treetopics/lib.php');

/* Page parameters */
$contextid = required_param('contextid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);
$id = optional_param('id', null, PARAM_INT);

/* No idea, copied this from an example. Sets form data options but I don't know what they all do exactly */
$formdata = new stdClass();
$formdata->userid = required_param('userid', PARAM_INT);
$formdata->offset = optional_param('offset', null, PARAM_INT);
$formdata->forcerefresh = optional_param('forcerefresh', null, PARAM_INT);
$formdata->mode = optional_param('mode', null, PARAM_ALPHA);

$url = new moodle_url('/course/format/treetopics/editimage.php', array(
    'contextid' => $contextid,
    'id' => $id,
    'offset' => $formdata->offset,
    'forcerefresh' => $formdata->forcerefresh,
    'userid' => $formdata->userid,
    'mode' => $formdata->mode));

/* Not exactly sure what this stuff does, but it seems fairly straightforward */
list($context, $course, $cm) = get_context_info_array($contextid);

require_login($course, true, $cm);
if (isguestuser()) {
    die();
}

$PAGE->set_url($url);
$PAGE->set_context($context);

/* Functional part. Create the form and display it, handle results, etc */
$options = array(
    'subdirs' => 0,
    'maxfiles' => 1,
    'accepted_types' => array('gif', 'jpe', 'jpeg', 'jpg', 'png'),
    'return_types' => FILE_INTERNAL);

$mform = new grid_image_form(null, array(
    'contextid' => $contextid,
    'userid' => $formdata->userid,
    'sectionid' => $sectionid,
    'options' => $options));

if ($mform->is_cancelled()) {
    // Someone has hit the 'cancel' button.
    redirect(new moodle_url($CFG->wwwroot . '/course/view.php?id=' . $course->id));
} else if ($formdata = $mform->get_data()) { // Form has been submitted.
    if ($formdata->deleteimage == 1) {
        // Delete the old images....
        $courseformat = course_get_format($course);
        $courseformat->delete_image($sectionid, $context->id);
    } else if ($newfilename = $mform->get_new_filename('imagefile')) {
        $fs = get_file_storage();

        // We have a new file so can delete the old....
        $courseformat = course_get_format($course);
        $sectionimage = $courseformat->get_image($course->id, $sectionid);
        if (isset($sectionimage->image)) {
            if ($file = $fs->get_file($context->id, 'course', 'section', $sectionid, '/', $sectionimage->image)) {
                $file->delete();
            }
        }

        // Resize the new image and save it...
        $storedfilerecord = $courseformat->create_original_image_record($contextid, $sectionid, $newfilename);

        $tempfile = $mform->save_stored_file(
                'imagefile',
                $storedfilerecord['contextid'],
                $storedfilerecord['component'],
                $storedfilerecord['filearea'],
                $storedfilerecord['itemid'],
                $storedfilerecord['filepath'],
                'temp.' . $storedfilerecord['filename'],
                true);

        $courseformat->create_section_image($tempfile, $storedfilerecord, $sectionimage);
    }
    redirect($CFG->wwwroot . "/course/view.php?id=" . $course->id);
}

/* Draw the form */
echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
